<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponses;

class OrderController extends Controller
{
    use ApiResponses;

    /**
     * إنشاء طلب جديد من الكارت (Checkout)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->errorResponse(
                message: 'Your cart is empty!',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        DB::beginTransaction();
        try {
            $totalAmount = $cart->items->sum(fn($item) => $item->quantity * $item->product->price);

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'payment_status' => 'pending',
            ]);

            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price,
                ]);
            }

            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return $this->successResponse(
                message: 'Order created successfully',
                data: $order->load('items.product'),
                statusCode: Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                message: 'Failed to create order: ' . $e->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * عرض كل الطلبات للمستخدم الحالي
     */
    public function index(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->successResponse(
            message: 'Orders retrieved successfully',
            data: $orders,
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * عرض تفاصيل طلب واحد
     */
    public function show($id, Request $request)
    {
        $order = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$order) {
            return $this->errorResponse(
                message: 'Order not found',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            message: 'Order details retrieved',
            data: $order,
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * تحديث حالة الطلب أو بياناته
     */
    public function update(Request $request, $id)
    {
        $order = Order::where('user_id', $request->user()->id)->find($id);

        if (!$order) {
            return $this->errorResponse(
                message: 'Order not found',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        $request->validate([
            'payment_status' => 'in:pending,paid,failed',
        ]);

        $order->update([
            'payment_status' => $request->payment_status ?? $order->payment_status,
        ]);

        return $this->successResponse(
            message: 'Order updated successfully',
            data: $order,
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * حذف طلب (إلغاء)
     */
    public function destroy(Request $request, $id)
    {
        $order = Order::where('user_id', $request->user()->id)->find($id);

        if (!$order) {
            return $this->errorResponse(
                message: 'Order not found',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });

        return $this->successResponse(
            message: 'Order deleted successfully',
            statusCode: Response::HTTP_OK
        );
    }


    public function cancel(Request $request, $id)
    {
        $order = Order::where('user_id', $request->user()->id)->find($id);

        if (!$order) {
            return $this->errorResponse(
                message: 'Order not found',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        // لو الطلب مدفوع خلاص، مينفعش يتكنسل
        if ($order->payment_status === 'paid') {
            return $this->errorResponse(
                message: 'Cannot cancel a paid order.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        // تحديث الحالة
        $order->update(['payment_status' => 'cancelled']);

        return $this->successResponse(
            message: 'Order cancelled successfully',
            data: $order,
            statusCode: Response::HTTP_OK
        );
    }
}
