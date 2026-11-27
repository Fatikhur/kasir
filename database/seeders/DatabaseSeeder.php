<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Toko KasirKita',
                'address' => 'Jl. Sudirman No. 88, Jakarta Pusat',
                'phone' => '0812-3456-7890',
                'email' => 'halo@kasirkita.id',
                'receipt_footer' => 'Terima kasih atas kunjungan Anda. Barang yang sudah dibeli tidak dapat ditukar.',
                'low_stock_threshold' => 5,
                'qris_enabled' => true,
            ]
        );

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@kasirkita.id'],
            [
                'name' => 'Budi Santoso (Pemilik)',
                'password' => Hash::make('password123'),
                'role' => UserRole::Owner,
                'phone' => '081111111111',
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'kasir@kasirkita.id'],
            [
                'name' => 'Siti Rahma (Kasir)',
                'password' => Hash::make('password123'),
                'role' => UserRole::Kasir,
                'phone' => '082222222222',
                'is_active' => true,
            ]
        );

        $categoriesData = [
            ['name' => 'Minuman', 'description' => 'Air mineral, teh, kopi, dan minuman kemasan'],
            ['name' => 'Makanan Ringan', 'description' => 'Keripik, biskuit, cokelat, dan camilan'],
            ['name' => 'Kebutuhan Pokok', 'description' => 'Beras, minyak goreng, gula, dan sembako'],
            ['name' => 'Perawatan Diri', 'description' => 'Sabun, sampo, pasta gigi, dan kebersihan'],
            ['name' => 'Alat Tulis', 'description' => 'Pulpen, buku tulis, dan perlengkapan kantor'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['name']] = Category::query()->updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'is_active' => true,
                ]
            );
        }

        $productsData = [
            ['cat' => 'Minuman', 'name' => 'Air Mineral 600ml', 'sku' => 'MIN-001', 'barcode' => '8990001000011', 'cost' => 2200, 'sell' => 4000, 'stock' => 48, 'min' => 10, 'unit' => 'botol'],
            ['cat' => 'Minuman', 'name' => 'Teh Botol Melati 350ml', 'sku' => 'MIN-002', 'barcode' => '8990001000028', 'cost' => 3200, 'sell' => 5500, 'stock' => 36, 'min' => 8, 'unit' => 'botol'],
            ['cat' => 'Minuman', 'name' => 'Kopi Susu Kaleng 240ml', 'sku' => 'MIN-003', 'barcode' => '8990001000035', 'cost' => 5500, 'sell' => 8500, 'stock' => 24, 'min' => 6, 'unit' => 'kaleng'],
            ['cat' => 'Minuman', 'name' => 'Susu UHT Cokelat 250ml', 'sku' => 'MIN-004', 'barcode' => '8990001000042', 'cost' => 4800, 'sell' => 7000, 'stock' => 4, 'min' => 6, 'unit' => 'kotak'],
            ['cat' => 'Makanan Ringan', 'name' => 'Keripik Kentang Original 68g', 'sku' => 'SNK-001', 'barcode' => '8990002000018', 'cost' => 7500, 'sell' => 11500, 'stock' => 20, 'min' => 5, 'unit' => 'pcs'],
            ['cat' => 'Makanan Ringan', 'name' => 'Biskuit Cokelat Krim 120g', 'sku' => 'SNK-002', 'barcode' => '8990002000025', 'cost' => 6000, 'sell' => 9500, 'stock' => 18, 'min' => 5, 'unit' => 'pcs'],
            ['cat' => 'Makanan Ringan', 'name' => 'Wafer Keju 145g', 'sku' => 'SNK-003', 'barcode' => '8990002000032', 'cost' => 8200, 'sell' => 12500, 'stock' => 3, 'min' => 5, 'unit' => 'pcs'],
            ['cat' => 'Kebutuhan Pokok', 'name' => 'Minyak Goreng Pouch 1L', 'sku' => 'SMB-001', 'barcode' => '8990003000015', 'cost' => 15500, 'sell' => 19000, 'stock' => 15, 'min' => 5, 'unit' => 'pouch'],
            ['cat' => 'Kebutuhan Pokok', 'name' => 'Gula Pasir Putih 1kg', 'sku' => 'SMB-002', 'barcode' => '8990003000022', 'cost' => 14500, 'sell' => 17500, 'stock' => 22, 'min' => 5, 'unit' => 'bungkus'],
            ['cat' => 'Kebutuhan Pokok', 'name' => 'Mie Instan Goreng', 'sku' => 'SMB-003', 'barcode' => '8990003000039', 'cost' => 2600, 'sell' => 3500, 'stock' => 60, 'min' => 12, 'unit' => 'bungkus'],
            ['cat' => 'Perawatan Diri', 'name' => 'Sabun Mandi Cair 250ml', 'sku' => 'PRW-001', 'barcode' => '8990004000012', 'cost' => 14000, 'sell' => 19500, 'stock' => 12, 'min' => 4, 'unit' => 'botol'],
            ['cat' => 'Perawatan Diri', 'name' => 'Pasta Gigi Herbal 150g', 'sku' => 'PRW-002', 'barcode' => '8990004000029', 'cost' => 11000, 'sell' => 15500, 'stock' => 0, 'min' => 4, 'unit' => 'pcs'],
            ['cat' => 'Alat Tulis', 'name' => 'Pulpen Gel Hitam 0.5mm', 'sku' => 'ATK-001', 'barcode' => '8990005000019', 'cost' => 2000, 'sell' => 4000, 'stock' => 35, 'min' => 10, 'unit' => 'pcs'],
            ['cat' => 'Alat Tulis', 'name' => 'Buku Tulis 58 Lembar', 'sku' => 'ATK-002', 'barcode' => '8990005000026', 'cost' => 3500, 'sell' => 6000, 'stock' => 28, 'min' => 8, 'unit' => 'pcs'],
        ];

        foreach ($productsData as $item) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'category_id' => $categories[$item['cat']]->id,
                    'name' => $item['name'],
                    'barcode' => $item['barcode'],
                    'cost_price' => $item['cost'],
                    'selling_price' => $item['sell'],
                    'stock' => $item['stock'],
                    'min_stock' => $item['min'],
                    'unit' => $item['unit'],
                    'is_active' => true,
                ]
            );

            if ($product->wasRecentlyCreated && $item['stock'] > 0) {
                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $owner->id,
                    'type' => InventoryMovementType::In,
                    'quantity_change' => $item['stock'],
                    'stock_before' => 0,
                    'stock_after' => $item['stock'],
                    'reason' => 'Stok awal produk',
                ]);
            }
        }
    }
}
