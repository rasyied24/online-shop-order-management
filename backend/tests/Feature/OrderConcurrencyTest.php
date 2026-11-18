<?php

namespace Tests\Feature;

use App\Actions\CreateOrderAction;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderConcurrencyTest extends TestCase
{
    // Data harus di-commit agar terlihat oleh proses lain, jadi RefreshDatabase
    // (yang membungkus test dalam satu transaksi) tidak bisa dipakai di sini.
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        // Data test ini di-commit, jadi harus dibersihkan agar test lain tidak terpengaruh.
        DB::statement('TRUNCATE TABLE order_items, orders, order_sequences, products, customers, personal_access_tokens, users RESTART IDENTITY CASCADE');

        parent::tearDown();
    }

    public function test_concurrent_orders_never_oversell_stock(): void
    {
        $userId = User::factory()->create()->id;
        $customerId = Customer::factory()->create()->id;
        $product = Product::factory()->create(['stock' => 3, 'price' => 10000]);

        $workers = 8;
        $startAt = microtime(true) + 2.0;
        $items = [['product_id' => $product->id, 'quantity' => 1]];

        $tasks = [];
        for ($i = 0; $i < $workers; $i++) {
            $tasks[] = self::worker($startAt, $userId, $customerId, $items);
        }

        $results = Concurrency::run($tasks);
        $counts = array_count_values($results);
        $detail = 'Hasil tiap proses: '.json_encode($results);

        $this->assertSame(3, $counts['success'] ?? 0, $detail);
        $this->assertSame(5, $counts['insufficient_stock'] ?? 0, $detail);

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 3);
        $this->assertDatabaseCount('order_items', 3);
        $this->assertCount(3, Order::pluck('order_number')->unique());
        $this->assertSame(3, (int) DB::table('order_sequences')->sum('last_number'));
    }

    public function test_orders_with_opposite_item_order_do_not_deadlock(): void
    {
        $userId = User::factory()->create()->id;
        $customerId = Customer::factory()->create()->id;
        $a = Product::factory()->create(['stock' => 100]);
        $b = Product::factory()->create(['stock' => 100]);

        $aThenB = [
            ['product_id' => $a->id, 'quantity' => 1],
            ['product_id' => $b->id, 'quantity' => 1],
        ];
        $bThenA = array_reverse($aThenB);

        $workers = 8;
        $startAt = microtime(true) + 2.0;

        $tasks = [];
        for ($i = 0; $i < $workers; $i++) {
            // Separuh proses memesan A lalu B, separuh lagi B lalu A.
            $tasks[] = self::worker($startAt, $userId, $customerId, $i % 2 === 0 ? $aThenB : $bThenA);
        }

        $results = Concurrency::run($tasks);
        $detail = 'Hasil tiap proses: '.json_encode($results);

        $this->assertSame($workers, array_count_values($results)['success'] ?? 0, $detail);
        $this->assertSame(92, $a->fresh()->stock);
        $this->assertSame(92, $b->fresh()->stock);
        $this->assertDatabaseCount('orders', $workers);
    }

    /**
     * Satu "pekerja": menunggu waktu mulai bersama, lalu membuat order.
     * Dibuat static agar closure tidak membawa $this (test case) saat diserialisasi.
     */
    private static function worker(float $startAt, int $userId, string $customerId, array $items): Closure
    {
        return static function () use ($startAt, $userId, $customerId, $items) {
            $wait = $startAt - microtime(true);
            if ($wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }

            try {
                app(CreateOrderAction::class)->execute(User::findOrFail($userId), $customerId, $items);

                return 'success';
            } catch (InsufficientStockException) {
                return 'insufficient_stock';
            } catch (\Throwable $e) {
                return 'error: '.$e::class.': '.$e->getMessage();
            }
        };
    }
}
