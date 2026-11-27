<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjustStock(
        Product $product,
        int $quantityChange,
        InventoryMovementType $type,
        string $reason,
        ?User $user = null,
        ?Model $reference = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $quantityChange, $type, $reason, $user, $reference): InventoryMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $stockBefore = $lockedProduct->stock;
            $stockAfter = $stockBefore + $quantityChange;

            if ($stockAfter < 0) {
                throw new DomainException(
                    "Stok produk {$lockedProduct->name} tidak mencukupi (tersedia: {$stockBefore} {$lockedProduct->unit})."
                );
            }

            $lockedProduct->update(['stock' => $stockAfter]);

            $movement = InventoryMovement::query()->create([
                'product_id' => $lockedProduct->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity_change' => $quantityChange,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $reason,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
            ]);

            if (in_array($type, [InventoryMovementType::In, InventoryMovementType::Out, InventoryMovementType::Adjustment], true)) {
                AuditLog::record(
                    action: 'stock.adjusted',
                    auditable: $lockedProduct,
                    oldValues: ['stock' => $stockBefore],
                    newValues: ['stock' => $stockAfter, 'change' => $quantityChange, 'type' => $type->value],
                    description: "Penyesuaian stok {$lockedProduct->name}: {$reason}",
                    userId: $user?->id,
                );
            }

            return $movement;
        });
    }
}
