<?php

namespace Tests\Feature;

use App\Actions\CreateOrderAction;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_totals_top_products_and_low_stock(): void
    {
        $user = $this->actingAsUser();
        $customer = Customer::factory()->create();
        $x = Product::factory()->create(['stock' => 100, 'price' => 10000]);
        $y = Product::factory()->create(['stock' => 100, 'price' => 5000]);
        $low = Product::factory()->create(['stock' => 4]);
        Product::factory()->create(['stock' => 5]); // tepat di batas, tidak termasuk

        $create = app(CreateOrderAction::class);
        $create->execute($user, $customer->id, [
            ['product_id' => $x->id, 'quantity' => 3],
        ]);
        $create->execute($user, $customer->id, [
            ['product_id' => $x->id, 'quantity' => 2],
            ['product_id' => $y->id, 'quantity' => 1],
        ]);

        $data = $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data');

        $this->assertSame(2, $data['total_orders']);
        $this->assertSame('55000.00', $data['total_sales']);

        $this->assertCount(2, $data['top_products']);
        $this->assertSame($x->id, $data['top_products'][0]['product_id']);
        $this->assertSame(5, $data['top_products'][0]['total_quantity']);

        $this->assertCount(1, $data['low_stock_products']);
        $this->assertSame($low->id, $data['low_stock_products'][0]['id']);
    }

    public function test_it_handles_empty_data(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 0)
            ->assertJsonPath('data.total_sales', '0.00')
            ->assertJsonPath('data.top_products', [])
            ->assertJsonPath('data.low_stock_products', []);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/dashboard/summary')->assertStatus(401);
    }
}
