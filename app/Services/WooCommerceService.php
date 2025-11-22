<?php

namespace App\Services;

use Automattic\WooCommerce\Client;
use Illuminate\Support\Facades\Log;

class WooCommerceService
{
    protected $client;

    public function __construct()
    {
        $url = rtrim(env('WOOCOMMERCE_STORE_URL'), '/'); // Remove trailing slash
        
        $this->client = new Client(
            $url,
            env('WOOCOMMERCE_CONSUMER_KEY'),
            env('WOOCOMMERCE_CONSUMER_SECRET'),
            [
                'version' => 'wc/v3',
                'timeout' => 40,
                'query_string_auth' => true, // Force credentials in URL to bypass header stripping
                'verify_ssl' => false, // Disable SSL verification for compatibility
            ]
        );
    }

    /**
     * Fetch all products.
     *
     * @param array $params
     * @return array
     */
    public function getProducts($params = [])
    {
        try {
            Log::info('Fetching WooCommerce products', $params);
            $results = $this->client->get('products', $params);
            Log::info('Fetched ' . count($results) . ' products.');
            return $results;
        } catch (\Exception $e) {
            Log::error('WooCommerce Fetch Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new product.
     *
     * @param array $data
     * @return object
     */
    public function createProduct($data)
    {
        try {
            Log::info('Creating WooCommerce product', $data);
            $result = $this->client->post('products', $data);
            Log::info('Product created successfully. ID: ' . $result->id);
            return $result;
        } catch (\Exception $e) {
            Log::error('WooCommerce Create Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing product.
     *
     * @param int $id
     * @param array $data
     * @return object
     */
    public function updateProduct($id, $data)
    {
        try {
            Log::info("Updating WooCommerce product ID: {$id}", $data);
            $result = $this->client->put("products/{$id}", $data);
            Log::info('Product updated successfully.');
            return $result;
        } catch (\Exception $e) {
            Log::error("WooCommerce Update Error for ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a product.
     *
     * @param int $id
     * @param bool $force
     * @return object
     */
    public function deleteProduct($id, $force = true)
    {
        try {
            Log::info("Deleting WooCommerce product ID: {$id}");
            $result = $this->client->delete("products/{$id}", ['force' => $force]);
            Log::info('Product deleted successfully.');
            return $result;
        } catch (\Exception $e) {
            Log::error("WooCommerce Delete Error for ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single product by ID.
     *
     * @param int $id
     * @return object
     */
    public function getProduct($id)
    {
        try {
            Log::info("Fetching WooCommerce product ID: {$id}");
            $result = $this->client->get("products/{$id}");
            Log::info('Product fetched successfully.');
            return $result;
        } catch (\Exception $e) {
            Log::error("WooCommerce Fetch Error for ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get product categories.
     *
     * @param array $params
     * @return array
     */
    public function getCategories($params = [])
    {
        try {
            Log::info('Fetching WooCommerce categories', $params);
            $results = $this->client->get('products/categories', $params);
            Log::info('Fetched ' . count($results) . ' categories.');
            return $results;
        } catch (\Exception $e) {
            Log::error('WooCommerce Categories Fetch Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Batch create/update/delete products.
     *
     * @param array $data
     * @return object
     */
    public function batchProducts($data)
    {
        try {
            Log::info('Batch processing WooCommerce products');
            $result = $this->client->post('products/batch', $data);
            Log::info('Batch processing completed.');
            return $result;
        } catch (\Exception $e) {
            Log::error('WooCommerce Batch Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
