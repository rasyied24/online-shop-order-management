<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@azatech.id')->firstOrFail();
        $customers = Customer::all();
        $products = Product::all();

        // 30 tanggal acak dalam 30 hari terakhir, diurutkan dari yang terlama
        $dates = collect(range(1, 30))
            ->map(fn () => now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 600)))
            ->sortBy(fn ($date) => $date->timestamp)
            ->values();

        $dailyCounter = [];

        foreach ($dates as $orderDate) {
            $day = $orderDate->toDateString();
            $dailyCounter[$day] = ($dailyCounter[$day] ?? 0) + 1;

            $total = 0;
            $rows = [];

            foreach ($products->random(random_int(1, 4)) as $product) {
                $qty = random_int(1, 5);
                $subtotal = $qty * (float) $product->price;
                $total += $subtotal;

                $rows[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ];

                $product->decrement('stock', $qty);
            }

            $order = Order::create([
                'order_number' => sprintf('INV-%s-%04d', $orderDate->format('Ymd'), $dailyCounter[$day]),
                'customer_id' => $customers->random()->id,
                'created_by' => $admin->id,
                'order_date' => $orderDate,
                'total_amount' => $total,
            ]);

            $order->items()->createMany($rows);
        }

        // sinkronkan penghitung nomor order agar order baru tidak bentrok dengan nomor seed
        foreach ($dailyCounter as $day => $last) {
            DB::table('order_sequences')->insert(['order_date' => $day, 'last_number' => $last]);
        }

        // beberapa produk dibuat berstok menipis supaya dashboard punya data peringatan
        Product::whereIn('product_code', ['P0003', 'P0010', 'P0015'])
            ->each(fn ($product) => $product->update(['stock' => random_int(0, 4)]));
    }
}
