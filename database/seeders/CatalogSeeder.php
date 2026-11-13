<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Database\Seeders\Concerns\ReadsDemoData;
use Illuminate\Database\Seeder;

/** Kategori, suplier, dan produk kasir aksesoris. */
class CatalogSeeder extends Seeder
{
    use ReadsDemoData;

    public function run(): void
    {
        $categoryIds = [];
        foreach ($this->demo('categories') as $row) {
            $categoryIds[$row['id']] = Category::create([
                'name' => $row['name'],
                'icon' => $row['icon'],
                'color' => $row['color'],
            ])->id;
        }

        foreach ($this->demo('suppliers') as $row) {
            Supplier::create([
                'name' => $row['name'],
                'contact_person' => $row['contactPerson'] ?? null,
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'categories' => $row['categories'] ?? [],
                'balance' => $row['balance'] ?? 0,
            ]);
        }

        foreach ($this->demo('products') as $row) {
            Product::create([
                'sku' => $row['sku'],
                'name' => $row['name'],
                'category_id' => $categoryIds[$row['categoryId']] ?? null,
                'unit' => $row['unit'],
                'buy_price' => $row['buyPrice'],
                'sell_price' => $row['sellPrice'],
                'stock' => $row['stock'],
                'min_stock' => $row['minStock'],
                'barcode' => $row['barcode'] ?? null,
                'color' => $row['color'] ?? null,
            ]);
        }
    }
}
