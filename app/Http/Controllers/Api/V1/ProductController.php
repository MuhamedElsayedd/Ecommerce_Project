<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
            return $this->errorResponse(
                message: 'Failed to create product',
                errors: config('app.debug') ? $e->getMessage() : null,
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->errorResponse(
                message: 'Product not found',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse(
            data: $product,
            message: 'Product fetched successfully',
            statusCode: Response::HTTP_OK
        );
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (empty($validated)) {
            return $this->errorResponse(
                message: 'You must provide at least one field to update.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        $product->update($validated);

        return $this->successResponse(
            data: $product->fresh(),
            message: 'Product updated successfully',
            statusCode: Response::HTTP_OK
        );
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->successResponse(
            message: 'Product deleted successfully',
            statusCode: Response::HTTP_OK
        );
    }
}
