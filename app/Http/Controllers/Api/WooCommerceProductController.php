<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WooCommerceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WooCommerceProductController extends Controller
{
    protected $wooCommerceService;

    public function __construct(WooCommerceService $wooCommerceService)
    {
        $this->wooCommerceService = $wooCommerceService;
    }

    /**
     * Fetch all products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $products = $this->wooCommerceService->getProducts();
            return response()->json([
                'status' => 'success',
                'fetched' => count($products),
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new product.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'sku' => 'required|string',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|string',
            'woocommerce_category_id' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'name', 'sku', 'price', 'description', 'short_description', 'quantity', 'weight'
            ]);

            // Map woocommerce_category_id to categories format
            if ($request->has('woocommerce_category_id')) {
                $data['categories'] = array_map(function ($id) {
                    return ['id' => $id];
                }, $request->input('woocommerce_category_id'));
            }
            
            // Ensure regular_price is set from price
            $data['regular_price'] = (string) $request->input('price');
            // Manage stock if quantity is provided
            if ($request->has('quantity')) {
                $data['manage_stock'] = true;
                $data['stock_quantity'] = $request->input('quantity');
            }

            $product = $this->wooCommerceService->createProduct($data);

            return response()->json([
                'status' => 'success',
                'woocommerce_product_id' => $product->id,
                'message' => 'Product created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing product.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Basic validation - fields are optional for update
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'quantity' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
             return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            
            if ($request->has('price')) {
                $data['regular_price'] = (string) $request->input('price');
            }
             if ($request->has('quantity')) {
                $data['manage_stock'] = true;
                $data['stock_quantity'] = $request->input('quantity');
            }

            $this->wooCommerceService->updateProduct($id, $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully on WooCommerce'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->wooCommerceService->deleteProduct($id);

            return response()->json([
                'success' => true,
                'message' => 'Product permanently deleted from WooCommerce.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single product by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = $this->wooCommerceService->getProduct($id);
            return response()->json([
                'status' => 'success',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        try {
            $categories = $this->wooCommerceService->getCategories();
            return response()->json([
                'status' => 'success',
                'count' => count($categories),
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch create/update/delete products.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batch(Request $request)
    {
        try {
            $data = $request->all();
            $result = $this->wooCommerceService->batchProducts($data);
            
            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to batch process products: ' . $e->getMessage()
            ], 500);
        }
    }
}
