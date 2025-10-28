<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use ApiResponses;
    public function index(Request $request)
    {
        try {
            $query = Product::query();

            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }

            $products = $query->paginate(10);

            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (\Throwable $e) {
            $this->logError('Fetching products failed', $e);
            return $this->errorResponse('Unable to fetch products', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(ProductRequest $request)
    {
        try {
            $validated = $request->validated();

            $product = Product::create($validated);

            return $this->successResponse(
                data: $product,
                message: 'Product created successfully',
                statusCode: Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            $this->logError('Product creation failed', $e);

            return $this->errorResponse(
                message: 'Failed to create product',
                errors: config('app.debug') ? $e->getMessage() : null,
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(Product $product)
    {
        return response()->json([
            'status' => true,
            'message' => 'Product fetched successfully',
            'data' => $product,
        ], Response::HTTP_OK);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ], Response::HTTP_OK);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully',
        ], Response::HTTP_OK);
    }
}
