<?php

use App\Livewire\Orders\Create;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('an authenticated user can create an order from grid lines', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $product = Product::factory()->create([
        'sku' => 'DRY-2042',
        'name' => 'Wildflower honey jars',
        'unit' => 'case',
        'unit_price' => 25,
        'tax_rate' => 21,
    ]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('customerId', $customer->id)
        ->set('orderDate', '2026-07-17')
        ->set('notes', 'Deliver before noon.')
        ->set('lines', [[
            '_k' => 'line-test-1',
            'product_id' => $product->id,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'unit' => $product->unit,
            'quantity' => 4,
            'unit_price' => '25.00',
            'discount_percent' => '10.00',
            'tax_rate' => '21.00',
            'line_total' => '108.90',
        ]])
        ->call('save')
        ->assertRedirectToRoute('orders.index');

    $order = Order::query()->with('items')->sole();

    expect($order)
        ->customer_id->toBe($customer->id)
        ->user_id->toBe($user->id)
        ->status->toBe('pending')
        ->order_number->toBe('SO-2026-0001')
        ->subtotal->toBe('100.00')
        ->discount_total->toBe('10.00')
        ->tax_total->toBe('18.90')
        ->total->toBe('108.90')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first())
        ->product_id->toBe($product->id)
        ->quantity->toBe(4)
        ->total->toBe('108.90');
});

test('guests are redirected from order entry', function () {
    $this->get('/orders/create')
        ->assertRedirect(route('login'));
});

test('order entry requires a customer and at least one valid line', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('lines', [])
        ->call('save')
        ->assertHasErrors(['customerId' => 'required']);

    expect(Order::query()->count())->toBe(0);
});

test('selecting a product enriches the editable row through the grid rpc', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'sku' => 'PAN-1001',
        'unit' => 'case',
        'unit_price' => '68.40',
        'tax_rate' => '21.00',
    ]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->tap(function ($component) use ($product): void {
            $rowKey = $component->get('lines')[0]['_k'];

            $component->call('gridOps', 'lines', ['ops' => [[
                't' => 'set',
                'seq' => 1,
                'row' => $rowKey,
                'col' => 'product_id',
                'v' => (string) $product->id,
            ]]]);

            $line = $component->get('lines')[0];

            expect($line)
                ->product_id->toBe((string) $product->id)
                ->sku->toBe('PAN-1001')
                ->unit->toBe('case')
                ->unit_price->toBe('68.40')
                ->tax_rate->toBe('21.00')
                ->line_total->toBe(82.76);
        });
});
