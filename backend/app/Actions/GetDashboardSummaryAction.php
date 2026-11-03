<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Brick\Math\BigDecimal;

final class GetDashboardSummaryAction
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        $totals = Order::query()
            ->selectRaw('COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_sales')
            ->first();

        $topProducts = OrderItem::query()
            ->select('product_id')
            ->selectRaw('SUM(quantity) AS total_quantity, SUM(subtotal) AS total_revenue')
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->orderBy('product_id')
            ->limit(5)
            ->get()
            ->map(fn (OrderItem $item) => [
                'product_id' => (int) $item->product_id,
                'product_code' => $item->product->product_code,
                'product_name' => $item->product->product_name,
                'total_quantity' => (int) $item->total_quantity,
                'total_revenue' => (string) BigDecimal::of($item->total_revenue)->toScale(2),
            ])
            ->all();

        $lowStockProducts = Product::query()
            ->where('stock', '<', Product::LOW_STOCK_THRESHOLD)
            ->orderBy('stock')
            ->orderBy('product_name')
            ->get(['id', 'product_code', 'product_name', 'stock'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'product_code' => $product->product_code,
                'product_name' => $product->product_name,
                'stock' => $product->stock,
            ])
            ->all();

        return [
            'total_orders' => (int) $totals->total_orders,
            'total_sales' => (string) BigDecimal::of($totals->total_sales)->toScale(2),
            'top_products' => $topProducts,
            'low_stock_threshold' => Product::LOW_STOCK_THRESHOLD,
            'low_stock_products' => $lowStockProducts,
        ];
    }
}
