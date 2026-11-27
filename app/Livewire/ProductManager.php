<?php

namespace App\Livewire;

use App\Enums\InventoryMovementType;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Manajemen Produk')]
class ProductManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?int $filterCategoryId = null;

    public string $filterStatus = 'all';

    // Product Modal
    public bool $showProductModal = false;

    public ?int $editingProductId = null;

    public string $name = '';

    public string $sku = '';

    public string $barcode = '';

    public ?int $category_id = null;

    public int $cost_price = 0;

    public int $selling_price = 0;

    public int $stock = 0;

    public int $min_stock = 5;

    public string $unit = 'pcs';

    public bool $is_active = true;

    public $image = null;

    // Category Modal
    public bool $showCategoryModal = false;

    public string $categoryName = '';

    public string $categoryDescription = '';

    // CSV Import Modal
    public bool $showImportModal = false;

    public $csvFile = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('manage-products');
        $this->resetProductForm();
        $this->showProductModal = true;
    }

    public function openEditModal(int $productId): void
    {
        $this->authorize('manage-products');
        $product = Product::query()->findOrFail($productId);

        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->barcode = (string) ($product->barcode ?? '');
        $this->category_id = $product->category_id;
        $this->cost_price = $product->cost_price;
        $this->selling_price = $product->selling_price;
        $this->stock = $product->stock;
        $this->min_stock = $product->min_stock;
        $this->unit = $product->unit;
        $this->is_active = $product->is_active;
        $this->image = null;

        $this->showProductModal = true;
    }

    public function saveProduct(StockService $stockService): void
    {
        $this->authorize('manage-products');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($this->editingProductId)],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($this->editingProductId)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'selling_price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'sku.required' => 'SKU wajib diisi.',
            'sku.unique' => 'SKU sudah digunakan oleh produk lain.',
            'barcode.unique' => 'Barcode sudah digunakan oleh produk lain.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'image.image' => 'File harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        $barcode = trim((string) ($validated['barcode'] ?? '')) ?: null;

        $data = [
            'name' => $validated['name'],
            'sku' => strtoupper(trim($validated['sku'])),
            'barcode' => $barcode,
            'category_id' => $validated['category_id'],
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'min_stock' => $validated['min_stock'],
            'unit' => trim($validated['unit']),
            'is_active' => $validated['is_active'],
        ];

        if ($this->image) {
            $data['image_path'] = $this->image->store('products', 'public');
        }

        if ($this->editingProductId) {
            $product = Product::query()->findOrFail($this->editingProductId);
            $oldPrices = ['cost_price' => $product->cost_price, 'selling_price' => $product->selling_price];

            $product->update($data);

            if ($oldPrices['cost_price'] !== $data['cost_price'] || $oldPrices['selling_price'] !== $data['selling_price']) {
                AuditLog::record(
                    action: 'product.price_updated',
                    auditable: $product,
                    oldValues: $oldPrices,
                    newValues: ['cost_price' => $data['cost_price'], 'selling_price' => $data['selling_price']],
                    description: "Perubahan harga produk {$product->name}",
                );
            }

            session()->flash('success', "Produk {$product->name} berhasil diperbarui.");
        } else {
            $initialStock = (int) $validated['stock'];
            $data['stock'] = 0;
            $product = Product::query()->create($data);

            if ($initialStock > 0) {
                $stockService->adjustStock(
                    product: $product,
                    quantityChange: $initialStock,
                    type: InventoryMovementType::In,
                    reason: 'Stok awal produk baru',
                    user: Auth::user(),
                );
            }

            AuditLog::record(
                action: 'product.created',
                auditable: $product,
                newValues: ['sku' => $product->sku, 'selling_price' => $product->selling_price, 'stock' => $initialStock],
                description: "Produk baru {$product->name} ditambahkan.",
            );

            session()->flash('success', "Produk {$product->name} berhasil ditambahkan.");
        }

        $this->showProductModal = false;
        $this->resetProductForm();
    }

    public function toggleActive(int $productId): void
    {
        $this->authorize('manage-products');
        $product = Product::query()->findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);

        session()->flash('success', "Status produk {$product->name} berhasil diubah.");
    }

    public function saveCategory(): void
    {
        $this->authorize('manage-products');

        $validated = $this->validate([
            'categoryName' => ['required', 'string', 'max:100'],
            'categoryDescription' => ['nullable', 'string', 'max:255'],
        ], [
            'categoryName.required' => 'Nama kategori wajib diisi.',
        ]);

        Category::query()->updateOrCreate(
            ['slug' => Str::slug($validated['categoryName'])],
            [
                'name' => $validated['categoryName'],
                'description' => $validated['categoryDescription'] ?: null,
                'is_active' => true,
            ]
        );

        $this->categoryName = '';
        $this->categoryDescription = '';
        session()->flash('success', 'Kategori berhasil disimpan.');
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('manage-products');

        $products = Product::query()->with('category')->orderBy('name')->get();

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Harga Modal', 'Harga Jual', 'Stok', 'Min Stok', 'Satuan', 'Status']);

            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->sku,
                    $p->barcode ?? '',
                    $p->name,
                    $p->category?->name ?? '',
                    $p->cost_price,
                    $p->selling_price,
                    $p->stock,
                    $p->min_stock,
                    $p->unit,
                    $p->is_active ? 'Aktif' : 'Nonaktif',
                ]);
            }
            fclose($handle);
        }, 'produk-'.now()->format('Ymd-His').'.csv');
    }

    public function importCsv(StockService $stockService): void
    {
        $this->authorize('manage-products');

        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6 || trim((string) $row[0]) === '') {
                continue;
            }

            $sku = strtoupper(trim((string) $row[0]));
            $barcode = trim((string) ($row[1] ?? '')) ?: null;
            $name = trim((string) $row[2]);
            $categoryName = trim((string) ($row[3] ?? ''));
            $costPrice = max(0, (int) $row[4]);
            $sellingPrice = max(0, (int) $row[5]);
            $stock = max(0, (int) ($row[6] ?? 0));
            $minStock = max(0, (int) ($row[7] ?? 5));
            $unit = trim((string) ($row[8] ?? 'pcs')) ?: 'pcs';

            $categoryId = null;
            if ($categoryName !== '') {
                $cat = Category::query()->firstOrCreate(
                    ['slug' => Str::slug($categoryName)],
                    ['name' => $categoryName, 'is_active' => true]
                );
                $categoryId = $cat->id;
            }

            $existing = Product::query()->where('sku', $sku)->first();
            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'category_id' => $categoryId,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'min_stock' => $minStock,
                    'unit' => $unit,
                ]);
            } else {
                $product = Product::query()->create([
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'name' => $name,
                    'category_id' => $categoryId,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'stock' => 0,
                    'min_stock' => $minStock,
                    'unit' => $unit,
                    'is_active' => true,
                ]);

                if ($stock > 0) {
                    $stockService->adjustStock($product, $stock, InventoryMovementType::In, 'Import CSV produk', Auth::user());
                }
            }
            $imported++;
        }

        fclose($handle);
        $this->csvFile = null;
        $this->showImportModal = false;
        session()->flash('success', "{$imported} produk berhasil diimpor dari CSV.");
    }

    private function resetProductForm(): void
    {
        $this->editingProductId = null;
        $this->name = '';
        $this->sku = '';
        $this->barcode = '';
        $this->category_id = null;
        $this->cost_price = 0;
        $this->selling_price = 0;
        $this->stock = 0;
        $this->min_stock = 5;
        $this->unit = 'pcs';
        $this->is_active = true;
        $this->image = null;
        $this->resetValidation();
    }

    public function render()
    {
        $categories = Category::query()->orderBy('name')->get();

        $products = Product::query()
            ->with('category')
            ->when($this->filterCategoryId, fn ($q) => $q->where('category_id', $this->filterCategoryId))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->orderByDesc('id')
            ->paginate(12);

        return view('livewire.product-manager', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
