<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAndRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_product_with_unique_sku_validation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Snack', 'slug' => 'snack', 'is_active' => true]);

        $this->actingAs($admin);

        Livewire::test(ProductManager::class)
            ->call('openCreateModal')
            ->set('name', 'Keripik Singkong')
            ->set('sku', 'SNK-100')
            ->set('barcode', '8999990001')
            ->set('category_id', $category->id)
            ->set('cost_price', 5000)
            ->set('selling_price', 8000)
            ->set('stock', 25)
            ->set('min_stock', 5)
            ->set('unit', 'pcs')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product = Product::query()->where('sku', 'SNK-100')->firstOrFail();
        $this->assertEquals('Keripik Singkong', $product->name);
        $this->assertEquals(25, $product->stock);

        // Edit produk & ubah harga -> harus tercatat di audit log
        Livewire::test(ProductManager::class)
            ->call('openEditModal', $product->id)
            ->set('selling_price', 9000)
            ->call('saveProduct')
            ->assertHasNoErrors();

        $this->assertEquals(9000, $product->fresh()->selling_price);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.price_updated',
            'auditable_id' => $product->id,
        ]);
    }

    public function test_kasir_cannot_access_admin_or_superadmin_routes(): void
    {
        $kasir = User::factory()->create(['role' => UserRole::Kasir, 'is_active' => true]);

        $this->actingAs($kasir);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('pos.index'))->assertOk();
        $this->get(route('transactions.index'))->assertOk();

        $this->get(route('products.index'))->assertForbidden();
        $this->get(route('stock.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('settings.index'))->assertForbidden();
    }

    public function test_admin_can_access_operations_but_not_superadmin_routes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->actingAs($admin);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('pos.index'))->assertOk();
        $this->get(route('transactions.index'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('stock.index'))->assertOk();
        $this->get(route('reports.index'))->assertOk();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('settings.index'))->assertForbidden();
    }

    public function test_superadmin_can_access_all_routes(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin, 'is_active' => true]);

        $this->actingAs($superadmin);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('pos.index'))->assertOk();
        $this->get(route('transactions.index'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('stock.index'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
        $this->get(route('users.index'))->assertOk();
        $this->get(route('settings.index'))->assertOk();
    }
}
