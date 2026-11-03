<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateOrderAction
{
    /**
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $items
     */
    public function execute(User $user, string $customerId, array $items): Order
    {
        return DB::transaction(function () use ($user, $customerId, $items) {
            // 1. Kunci baris produk, diurutkan berdasarkan id agar tidak deadlock
            $products = Product::query()
                ->whereIn('id', array_column($items, 'product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 2. Validasi stok semua item, kumpulkan semua error sekaligus
            $errors = [];
            $rows = [];
            $total = BigDecimal::zero();

            foreach ($items as $index => $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];

                if ($product === null) {
                    throw ValidationException::withMessages([
                        "items.$index.product_id" => 'Produk tidak ditemukan',
                    ]);
                }

                if ($product->stock < $quantity) {
                    $errors["items.$index.quantity"][] = sprintf(
                        'Stok %s tersisa %d, diminta %d',
                        $product->product_name,
                        $product->stock,
                        $quantity,
                    );

                    continue;
                }

                // 3. Harga dan subtotal dihitung di server dari data produk
                $subtotal = BigDecimal::of($product->price)->multipliedBy($quantity)->toScale(2);
                $total = $total->plus($subtotal);

                $rows[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => (string) $subtotal,
                ];
            }

            if ($errors !== []) {
                throw new InsufficientStockException($errors); // transaction otomatis rollback
            }

            // 4. Simpan order dan item
            $orderDate = now();

            $order = Order::create([
                'order_number' => $this->nextOrderNumber($orderDate),
                'customer_id' => $customerId,
                'created_by' => $user->id,
                'order_date' => $orderDate,
                'total_amount' => (string) $total->toScale(2),
            ]);

            $order->items()->createMany($rows);

            // 5. Kurangi stok (baris sudah terkunci, jadi aman)
            foreach ($items as $item) {
                $products->get($item['product_id'])->decrement('stock', (int) $item['quantity']);
            }

            return $order->load(['customer', 'creator', 'items.product']);
        });
    }

    private function nextOrderNumber(CarbonInterface $date): string
    {
        $row = DB::selectOne(
            'INSERT INTO order_sequences (order_date, last_number) VALUES (?, 1)
             ON CONFLICT (order_date)
             DO UPDATE SET last_number = order_sequences.last_number + 1
             RETURNING last_number',
            [$date->toDateString()],
        );

        return sprintf('INV-%s-%04d', $date->format('Ymd'), $row->last_number);
    }
}
