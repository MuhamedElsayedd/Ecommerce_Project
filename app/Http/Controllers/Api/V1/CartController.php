<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\{Cart, CartItem, Product};
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class CartController extends Controller
{
    use ApiResponses;
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse(
                message: 'You must be logged in to view the cart.',
                statusCode: Response::HTTP_UNAUTHORIZED
            );
        }

        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->errorResponse(
                message: 'Cart is empty!',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        $total = $cart->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return $this->successResponse(
            data: [
                'cart' => $cart,
                'total_price' => $total,
            ],
            message: 'Cart retrieved successfully',
            statusCode: Response::HTTP_OK
        );
    }

    public function add(AddToCartRequest $request)
    {
        $request->validated();

        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $product = Product::findOrFail($request->product_id);
        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price, // أضف السطر ده
            ]);
        }

        $total = $cart->total;

        return $this->successResponse(
            message: 'Product added to cart',
            data: [
                'cart' => $cart->load('items.product'),
                'total' => $total,
            ],
            statusCode: Response::HTTP_OK
        );
    }

    public function update(UpdateCartRequest $request, $itemId)
    {
        $request->validated();

        $user = $request->user();

        if (!$user) {
            return $this->errorResponse(
                message: 'Unauthorized - Please log in first',
                statusCode: Response::HTTP_UNAUTHORIZED
            );
        }

        $item = CartItem::find($itemId);

        if (!$item) {
            return $this->errorResponse(
                message: 'Cart item not found',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        if ($item->cart->user_id !== $user->id) {
            return $this->errorResponse(
                message: 'Forbidden - You cannot modify another user’s cart',
                statusCode: Response::HTTP_FORBIDDEN
            );
        }

        $item->update(['quantity' => $request->quantity]);

        $cart = $item->cart;
        $total = $cart->items()->sum(DB::raw('quantity * price'));

        return $this->successResponse(
            data: [
                'cart' => $cart->load('items.product'),
                'total' => $total
            ],
            message: 'Cart updated successfully',
            statusCode: Response::HTTP_OK
        );
    }

    public function remove($itemId)
    {
        $item = CartItem::findOrFail($itemId);
        $cart = $item->cart;

        $item->delete();

        $cart->total = $cart->items()->sum(DB::raw('quantity * price'));
        $cart->save();

        return $this->successResponse(
            message: 'Item removed',
            data: $cart->load('items.product'),
            statusCode: Response::HTTP_OK
        );
    }
}
