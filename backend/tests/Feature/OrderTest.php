<?php

namespace Tests\Feature;

use App\Actions\CreateOrderAction;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_and_reduces_stock(): void
    {
        $this->actingAsUser();
        $customer = Customer::factory()->create();
        $shampoo = Product::factory()->create(['stock' => 10, 'price' => 12000]);
        $soap = Product::factory()->create(['stock' => 5, 'price' => 5000]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $shampoo->id, 'quantity' => 2],
                ['product_id' => $soap->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_amount', '29000.00')
            ->assertJsonCount(2, 'data.items');

        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{4}$/', $response->json('data.order_number'));

        $this->assertDatabaseHas('order_items', [
            'product_id' => $shampoo->id,
            'quantity' => 2,
            'unit_price' => '12000.00',
            'subtotal' => '24000.00',
        ]);
        $this->assertSame(8, $shampoo->fresh()->stock);
        $this->assertSame(4, $soap->fresh()->stock);
    }

    public function test_it_fails_and_changes_nothing_when_stock_is_insufficient(): void
    {
        $this->actingAsUser();
        $customer = Customer::factory()->create();
        $enough = Product::factory()->create(['stock' => 10]);
        $low = Product::factory()->create(['stock' => 3, 'product_name' => 'Hydrococo 150ml']);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $enough->id, 'quantity' => 2],
                ['product_id' => $low->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Stok tidak mencukupi');

        $this->assertSame(
            'Stok Hydrococo 150ml tersisa 3, diminta 5',
            $response->json('errors')['items.1.quantity'][0],
        );

        // atomik: tidak ada order, tidak ada stok berkurang, nomor invoice tidak terpakai
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_sequences', 0);
        $this->assertSame(10, $enough->fresh()->stock);
        $this->assertSame(3, $low->fresh()->stock);
    }

    public function test_it_allows_ordering_exactly_the_remaining_stock(): void
    {
        $this->actingAsUser();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 3]);

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertCreated();

        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_it_rejects_invalid_input(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/orders', ['customer_id' => 'bukan-uuid', 'items' => []])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['customer_id', 'items']);
    }

    public function test_it_rejects_invalid_quantity_and_unknown_product(): void
    {
        $this->actingAsUser();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 0],
                ['product_id' => 999999, 'quantity' => 1],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity', 'items.1.product_id']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_rejects_duplicate_products_in_one_order(): void
    {
        $this->actingAsUser();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['items.1.product_id']);

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_price_and_total_from_client_are_ignored(): void
    {
        $this->actingAsUser();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 12000]);

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'total_amount' => 1,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 1]],
        ])->assertCreated()
            ->assertJsonPath('data.total_amount', '12000.00');

        $this->assertDatabaseHas('order_items', ['unit_price' => '12000.00']);
    }

    public function test_order_numbers_increment_within_the_same_day(): void
    {
        $this->actingAsUser();
        $this->travelTo(Carbon::parse('2026-10-27 10:00:00'));

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $payload = [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $first = $this->postJson('/api/orders', $payload);
        $second = $this->postJson('/api/orders', $payload);

        $this->assertSame('INV-20261027-0001', $first->json('data.order_number'));
        $this->assertSame('INV-20261027-0002', $second->json('data.order_number'));
    }

    public function test_invoice_number_is_not_consumed_when_order_fails_after_number_is_taken(): void
    {
        $user = $this->actingAsUser();
        $this->travelTo(Carbon::parse('2026-10-27 10:00:00'));

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $items = [['product_id' => $product->id, 'quantity' => 1]];
        $action = app(CreateOrderAction::class);

        try {
            // Customer tidak ada: gagal di foreign key, SETELAH nomor invoice diambil.
            $action->execute($user, (string) Str::uuid(), $items);
            $this->fail('Seharusnya gagal');
        } catch (QueryException) {
            // diharapkan
        }

        $this->assertDatabaseCount('order_sequences', 0);   // kenaikan nomor ikut dibatalkan
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);

        $order = $action->execute($user, $customer->id, $items);
        $this->assertSame('INV-20261027-0001', $order->order_number);   // tetap 0001, tidak loncat
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/orders', [])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
