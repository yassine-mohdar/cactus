<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProductBulkActionService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProductMediaService $productMedia,
        private readonly ProductVariantService $productVariants,
    ) {}

    /**
     * @param  Collection<int, Product>  $products
     */
    public function run(Collection $products, string $action, callable $authorize): int
    {
        $affectedCount = 0;

        foreach ($products as $product) {
            if ($action === 'delete') {
                $authorize('delete', $product);

                $snapshot = $this->stateSnapshot($product);
                $pricing = $this->pricingSnapshot($product);
                $this->productVariants->purge($product);
                $this->productMedia->purge($product);
                $product->tags()->detach();
                $product->categories()->detach();
                $product->delete();

                $this->audit->log(
                    action: 'catalog.product.bulk_deleted',
                    target: $product,
                    oldValues: array_merge($snapshot, $pricing),
                    newValues: [],
                    notes: 'Product deleted through bulk action',
                    context: [
                        'module' => 'catalog',
                        'source' => 'product_bulk_action_service',
                        'bulk_action' => true,
                    ],
                );

                $affectedCount++;

                continue;
            }

            $authorize('update', $product);

            $newStatus = $this->resolveStatusAction($action);

            if ($product->status === $newStatus) {
                continue;
            }

            $oldStatus = $product->status;
            $product->status = $newStatus;
            $product->save();

            $this->audit->log(
                action: 'catalog.product.bulk_status_changed',
                target: $product,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
                notes: 'Product status changed through bulk action',
                context: [
                    'module' => 'catalog',
                    'source' => 'product_bulk_action_service',
                    'bulk_action' => true,
                    'requested_action' => $action,
                ],
            );

            $affectedCount++;
        }

        return $affectedCount;
    }

    public function supports(string $action): bool
    {
        return in_array($action, ['publish', 'draft', 'archive', 'delete', 'activate', 'deactivate'], true);
    }

    public function normalizeAction(string $action): string
    {
        return match ($action) {
            'activate' => 'publish',
            'deactivate' => 'draft',
            default => $action,
        };
    }

    private function resolveStatusAction(string $action): string
    {
        return match ($this->normalizeAction($action)) {
            'publish' => Product::STATUS_PUBLISHED,
            'draft' => Product::STATUS_DRAFT,
            'archive' => Product::STATUS_ARCHIVED,
            default => throw new \InvalidArgumentException("Unsupported product bulk action [{$action}]."),
        };
    }

    /**
     * @return array<string, float|null>
     */
    private function pricingSnapshot(Product $product): array
    {
        return [
            'price' => $product->price !== null ? (float) $product->price : null,
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stateSnapshot(Product $product): array
    {
        return Arr::only($product->fresh()->toArray(), [
            'name',
            'slug',
            'type',
            'status',
            'sku',
            'barcode',
            'quantity',
            'is_featured',
        ]);
    }
}
