<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Jobs\SyncProductToWooCommerce;
use App\Models\Product;
use App\Services\WooCommerceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;


class WooCommerceProductController extends Controller
{
    protected $wooCommerceService;

    public function __construct(WooCommerceService $wooCommerceService)
    {
        $this->wooCommerceService = $wooCommerceService;
    }

    /**
     * Fetch all products with pagination and search.
     * Use ?source=live to fetch directly from WooCommerce API
     * Default: fetches from local database
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $source = $request->input('source', 'local'); // 'local' or 'live'
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $sku = $request->input('sku');
            
            // Fetch from live WooCommerce API
            if ($source === 'live') {
                $params = [
                    'per_page' => $perPage,
                    'page' => $page,
                ];
                
                // Add search parameter if provided
                if ($search) {
                    $params['search'] = $search;
                }
                
                // Add SKU filter if provided
                if ($sku) {
                    $params['sku'] = $sku;
                }
                
                $products = $this->wooCommerceService->getProducts($params);
                
                return response()->json([
                    'status' => 'success',
                    'source' => 'live',
                    'fetched' => count($products),
                    'data' => $products
                ]);
            }
            
            // Fetch from local database (default)
            $query = Product::query();
            
            // Apply search filter
            if ($search) {
                $query->search($search);
            }
            
            // Apply SKU filter
            if ($sku) {
                $query->where('sku', 'like', "%{$sku}%");
            }
            
            // Get paginated results
            $products = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'source' => 'local',
                'data' => $products->items(),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new product (dispatches background job).
     *
     * @param StoreProductRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
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
            
            // Create local product record
            $product = Product::create($data);
            
            // Dispatch job to sync with WooCommerce
            SyncProductToWooCommerce::dispatch($product, 'create');

            return response()->json([
                'status' => 'success',
                'product_id' => $product->id,
                'sync_status' => $product->sync_status,
                'message' => 'Product created locally and queued for WooCommerce sync'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing product (dispatches background job).
     *
     * @param UpdateProductRequest $request
     * @param int $id (WooCommerce product ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            // Find local product by WooCommerce ID
            $product = Product::where('woocommerce_id', $id)->firstOrFail();
            
            $data = $request->only([
                'name', 'sku', 'price', 'description', 'short_description', 'quantity', 'weight'
            ]);

            // Map woocommerce_category_id to categories format
            if ($request->has('woocommerce_category_id')) {
                $data['categories'] = array_map(function ($id) {
                    return ['id' => $id];
                }, $request->input('woocommerce_category_id'));
            }
            
            // Update local product
            $product->update($data);
            
            // Dispatch job to sync with WooCommerce
            SyncProductToWooCommerce::dispatch($product, 'update');

            return response()->json([
                'status' => 'success',
                'product_id' => $product->id,
                'woocommerce_id' => $product->woocommerce_id,
                'sync_status' => $product->sync_status,
                'message' => 'Product updated locally and queued for WooCommerce sync'
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
     * @param int $id (WooCommerce product ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Find and delete local product
            $product = Product::where('woocommerce_id', $id)->first();
            
            if ($product) {
                $product->delete();
            }
            
            // Delete from WooCommerce
            $this->wooCommerceService->deleteProduct($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted from local database and WooCommerce.'
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
     * Use ?source=live to fetch directly from WooCommerce API
     * Default: fetches from local database
     *
     * @param Request $request
     * @param int $id (WooCommerce product ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $source = $request->input('source', 'local');
            
            if ($source === 'live') {
                // Fetch from WooCommerce API
                $product = $this->wooCommerceService->getProduct($id);
                return response()->json([
                    'status' => 'success',
                    'source' => 'live',
                    'product' => $product
                ]);
            }
            
            // Fetch from local database
            $product = Product::where('woocommerce_id', $id)->firstOrFail();
            return response()->json([
                'status' => 'success',
                'source' => 'local',
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

    /**
     * Synchronize all products from WooCommerce to local database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request)
    {
        try {
            $page = 1;
            $perPage = 100;
            $totalSynced = 0;
            $newProducts = 0;
            $updatedProducts = 0;
            
            Log::info('Starting WooCommerce product synchronization');
            
            do {
                // Fetch products from WooCommerce with pagination
                $products = $this->wooCommerceService->getProducts([
                    'page' => $page,
                    'per_page' => $perPage,
                ]);
                
                foreach ($products as $wooProduct) {
                    // Check if product already exists locally
                    $localProduct = Product::where('woocommerce_id', $wooProduct->id)->first();
                    
                    // Prepare product data
                    $productData = [
                        'woocommerce_id' => $wooProduct->id,
                        'name' => $wooProduct->name,
                        'sku' => !empty($wooProduct->sku) ? $wooProduct->sku : null,
                        'price' => !empty($wooProduct->price) ? $wooProduct->price : null,
                        'description' => !empty($wooProduct->description) ? $wooProduct->description : null,
                        'short_description' => !empty($wooProduct->short_description) ? $wooProduct->short_description : null,
                        'quantity' => !empty($wooProduct->stock_quantity) ? $wooProduct->stock_quantity : null,
                        'weight' => !empty($wooProduct->weight) ? $wooProduct->weight : null,
                        'categories' => !empty($wooProduct->categories) ? $wooProduct->categories : null,
                        'sync_status' => 'synced',
                        'last_synced_at' => now(),
                    ];
                    
                    if ($localProduct) {
                        // Update existing product
                        $localProduct->update($productData);
                        $updatedProducts++;
                    } else {
                        // Create new product
                        Product::create($productData);
                        $newProducts++;
                    }
                    
                    $totalSynced++;
                }
                
                $page++;
                
            } while (count($products) === $perPage);
            
            Log::info('WooCommerce synchronization completed', [
                'total' => $totalSynced,
                'new' => $newProducts,
                'updated' => $updatedProducts,
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Products synchronized successfully',
                'statistics' => [
                    'total_synced' => $totalSynced,
                    'new_products' => $newProducts,
                    'updated_products' => $updatedProducts,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('WooCommerce sync failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to synchronize products: ' . $e->getMessage()
            ], 500);
        }
    }
}
