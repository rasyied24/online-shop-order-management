<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['Hydrococo 150ml', 12000], ['Teh Botol Sosro 450ml', 6000],
            ['Indomie Goreng 85g', 3500], ['Aqua Botol 600ml', 4000],
            ['Kopi Kapal Api 165g', 15000], ['Gula Pasir 1kg', 17000],
            ['Minyak Goreng 1L', 18000], ['Beras Premium 5kg', 72000],
            ['Susu UHT Cokelat 200ml', 6500], ['Roti Tawar Gandum', 15000],
            ['Sabun Mandi Cair 450ml', 24000], ['Shampoo Anti Ketombe 170ml', 27000],
            ['Pasta Gigi 120g', 14000], ['Detergen Bubuk 800g', 22000],
            ['Tisu Wajah 250 sheet', 12500], ['Indomie Soto 75g', 3200],
            ['Biskuit Kelapa 300g', 9500], ['Kecap Manis 275ml', 13500],
            ['Saus Sambal 335ml', 11000], ['Cokelat Batang 40g', 8500],
        ];

        foreach ($items as $i => [$name, $price]) {
            Product::factory()->create([
                'product_code' => sprintf('P%04d', $i + 1),
                'product_name' => $name,
                'price' => $price,
                'stock' => fake()->numberBetween(100, 200),
            ]);
        }
    }
}
