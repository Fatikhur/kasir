<?php

namespace App\Livewire;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\StockService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Manajemen Stok')]
class StockManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $stockFilter = 'all';

    public bool $showAdjustModal = false;

    public ?int $selectedProductId = null;

    public string $selectedProductName = '';

    public int $currentStock = 0;

    public string $adjustType = 'in';

    public int $adjustQuantity = 1;

    public string $adjustReason = '';

    public ?string $errorMessage = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function openAdjustModal(int $productId): void
    {
        $this->authorize('manage-stock');
        $product = Product::query()->findOrFail($productId);

        $this->selectedProductId = $product->id;
        $this->selectedProductName = $product->name;
        $this->currentStock = $product->stock;
        $this->adjustType = 'in';
        $this->adjustQuantity = 1;
        $this->adjustReason = '';
        $this->errorMessage = null;
        $this->showAdjustModal = true;
    }

    public function saveAdjustment(StockService $stockService): void
    {
        $this->authorize('manage-stock');
        $this->errorMessage = null;

        $this->validate([
            'adjustType' => ['required', 'in:in,out,adjustment'],
            'adjustQuantity' => ['required', 'integer', 'min:1'],
            'adjustReason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'adjustQuantity.min' => 'Jumlah minimal adalah 1.',
            'adjustReason.required' => 'Alasan perubahan stok wajib diisi.',
            'adjustReason.min' => 'Alasan perubahan stok minimal 3 karakter.',
        ]);

        $product = Product::query()->findOrFail($this->selectedProductId);
        $delta = $this->adjustType === 'out' ? -$this->adjustQuantity : $this->adjustQuantity;
        $type = InventoryMovementType::from($this->adjustType);

        try {
            $stockService->adjustStock(
                product: $product,
                quantityChange: $delta,
                type: $type,
                reason: $this->adjustReason,
                user: Auth::user(),
            );

            $this->showAdjustModal = false;
            session()->flash('success', "Stok {$product->name} berhasil disesuaikan.");
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $lowStockCount = Product::query()->active()->lowStock()->count();
        $outOfStockCount = Product::query()->active()->outOfStock()->count();

        $products = Product::query()
            ->with('category')
            ->when($this->stockFilter === 'low', fn ($q) => $q->lowStock())
            ->when($this->stockFilter === 'out', fn ($q) => $q->outOfStock())
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($sub) => $sub->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->orderBy('stock')
            ->paginate(10, pageName: 'productsPage');

        $movements = InventoryMovement::query()
            ->with(['product', 'user'])
            ->latest()
            ->paginate(12, pageName: 'movementsPage');

        return view('livewire.stock-manager', [
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'products' => $products,
            'movements' => $movements,
        ]);
    }
}
