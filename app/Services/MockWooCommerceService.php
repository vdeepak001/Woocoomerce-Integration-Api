<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockWooCommerceService extends WooCommerceService
{
    public function __construct()
    {
        // No need to initialize the real client
    }

    public function getProducts($params = [])
    {
        Log::info('Mock Mode: Fetching products', $params);
        return [
            (object) [
                'id' => 101,
                'name' => 'Mock Product 1',
                'sku' => 'MOCK-001',
                'price' => '19.99',
                'regular_price' => '19.99',
                'status' => 'publish',
            ],
            (object) [
                'id' => 102,
                'name' => 'Mock Product 2',
                'sku' => 'MOCK-002',
                'price' => '29.99',
                'regular_price' => '29.99',
                'status' => 'publish',
            ],
        ];
    }

    public function createProduct($data)
    {
        Log::info('Mock Mode: Creating product', $data);
        return (object) [
            'id' => rand(1000, 9999),
            'name' => $data['name'] ?? 'Mock Product',
            'sku' => $data['sku'] ?? 'MOCK-NEW',
            'price' => $data['regular_price'] ?? '0.00',
            'status' => 'publish',
        ];
    }

    public function updateProduct($id, $data)
    {
        Log::info("Mock Mode: Updating product ID: {$id}", $data);
        return (object) [
            'id' => $id,
            'updated' => true,
        ];
    }

    public function deleteProduct($id, $force = true)
    {
        Log::info("Mock Mode: Deleting product ID: {$id}");
        return (object) [
            'id' => $id,
            'deleted' => true,
        ];
    }
}
