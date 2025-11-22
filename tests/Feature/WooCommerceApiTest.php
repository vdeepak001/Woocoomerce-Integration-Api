<?php

namespace Tests\Feature;

use App\Services\WooCommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class WooCommerceApiTest extends TestCase
{
    protected $mockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockService = Mockery::mock(WooCommerceService::class);
        $this->app->instance(WooCommerceService::class, $this->mockService);
    }

    public function test_can_fetch_products()
    {
        $this->mockService->shouldReceive('getProducts')
            ->once()
            ->andReturn([
                ['id' => 1, 'name' => 'Test Product']
            ]);

        $response = $this->getJson('/api/woocommerce/products');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'fetched' => 1,
                'products' => [
                    ['id' => 1, 'name' => 'Test Product']
                ]
            ]);
    }

    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Sample Product',
            'sku' => 'SKU123',
            'price' => 199.99,
            'description' => 'Detailed product description',
            'short_description' => 'Short one-line info',
            'quantity' => 10,
            'weight' => '0.5',
            'woocommerce_category_id' => [15]
        ];

        $mockProduct = (object) ['id' => 125];

        $this->mockService->shouldReceive('createProduct')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['name'] === 'Sample Product' &&
                       $data['regular_price'] === '199.99' &&
                       $data['manage_stock'] === true;
            }))
            ->andReturn($mockProduct);

        $response = $this->postJson('/api/woocommerce/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'woocommerce_product_id' => 125,
                'message' => 'Product created successfully'
            ]);
    }

    public function test_can_update_product()
    {
        $updateData = [
            'price' => 249.00,
            'description' => 'Updated description',
            'quantity' => 15
        ];

        $this->mockService->shouldReceive('updateProduct')
            ->once()
            ->with(125, Mockery::on(function ($data) {
                return $data['regular_price'] === '249' &&
                       $data['stock_quantity'] === 15;
            }))
            ->andReturn((object) ['id' => 125]);

        $response = $this->putJson('/api/woocommerce/products/125', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Product updated successfully on WooCommerce'
            ]);
    }

    public function test_can_delete_product()
    {
        $this->mockService->shouldReceive('deleteProduct')
            ->once()
            ->with(125)
            ->andReturn((object) ['id' => 125]);

        $response = $this->deleteJson('/api/woocommerce/products/125');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product permanently deleted from WooCommerce.'
            ]);
    }
}
