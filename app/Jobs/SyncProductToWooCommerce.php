<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\WooCommerceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncProductToWooCommerce implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * The product instance.
     *
     * @var Product
     */
    protected $product;

    /**
     * The operation type (create or update).
     *
     * @var string
     */
    protected $operation;

    /**
     * Create a new job instance.
     */
    public function __construct(Product $product, string $operation = 'create')
    {
        $this->product = $product;
        $this->operation = $operation;
    }

    /**
     * Execute the job.
     */
    public function handle(WooCommerceService $wooCommerceService): void
    {
        try {
            Log::info("Syncing product to WooCommerce", [
                'product_id' => $this->product->id,
                'operation' => $this->operation,
            ]);

            // Prepare data for WooCommerce
            $data = [
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'regular_price' => (string) $this->product->price,
                'description' => $this->product->description,
                'short_description' => $this->product->short_description,
            ];

            // Add weight if provided
            if ($this->product->weight) {
                $data['weight'] = (string) $this->product->weight;
            }

            // Add stock management if quantity is provided
            if ($this->product->quantity !== null) {
                $data['manage_stock'] = true;
                $data['stock_quantity'] = $this->product->quantity;
            }

            // Add categories if provided
            if ($this->product->categories) {
                $data['categories'] = $this->product->categories;
            }

            // Perform the operation
            if ($this->operation === 'create') {
                $result = $wooCommerceService->createProduct($data);
                
                // Update local product with WooCommerce ID
                $this->product->update([
                    'woocommerce_id' => $result->id,
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'last_synced_at' => now(),
                ]);
                
                Log::info("Product created in WooCommerce", [
                    'product_id' => $this->product->id,
                    'woocommerce_id' => $result->id,
                ]);
            } else {
                // Update operation
                if (!$this->product->woocommerce_id) {
                    throw new Exception('Cannot update product without WooCommerce ID');
                }

                $wooCommerceService->updateProduct($this->product->woocommerce_id, $data);
                
                // Update sync status
                $this->product->update([
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'last_synced_at' => now(),
                ]);
                
                Log::info("Product updated in WooCommerce", [
                    'product_id' => $this->product->id,
                    'woocommerce_id' => $this->product->woocommerce_id,
                ]);
            }
        } catch (Exception $e) {
            Log::error("Failed to sync product to WooCommerce", [
                'product_id' => $this->product->id,
                'operation' => $this->operation,
                'error' => $e->getMessage(),
            ]);

            // Update product with error status
            $this->product->update([
                'sync_status' => 'failed',
                'sync_error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job permanently failed for product", [
            'product_id' => $this->product->id,
            'operation' => $this->operation,
            'error' => $exception->getMessage(),
        ]);

        // Mark as permanently failed after all retries
        $this->product->update([
            'sync_status' => 'failed',
            'sync_error' => 'Permanently failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
