<?php

use App\Models\User;
use App\Livewire\Orders\Index;
use App\Models\Customer;
use App\Models\Order;
use Livewire\Livewire;

test('guests are redirected from the order register', function () {
    $this->get('/orders')
        ->assertRedirect(route('login'));
});

test('authenticated users can view the order register', function () {
    $this->actingAs(User::factory()->create())
        ->get('/orders')
        ->assertSuccessful()
        ->assertSee('Orders')
        ->assertSee('New order');
});

test('the order register searches customers and filters statuses server side', function () {
    $user = User::factory()->create();
    $aster = Customer::factory()->create(['company_name' => 'Aster & Pine Hotels']);
    $juniper = Customer::factory()->create(['company_name' => 'Juniper Pantry']);

    Order::factory()->for($aster)->for($user)->create([
        'order_number' => 'SO-2026-0101',
        'customer_name' => 'Aster & Pine Hotels',
        'status' => 'confirmed',
    ]);
    Order::factory()->for($juniper)->for($user)->create([
        'order_number' => 'SO-2026-0102',
        'customer_name' => 'Juniper Pantry',
        'status' => 'pending',
    ]);

    $component = Livewire::actingAs($user)->test(Index::class);
    $result = $component->instance()->gridFetch('orders', [
        'search' => 'Aster',
        'filters' => ['status' => 'confirmed'],
    ]);

    expect($result['total'])->toBe(1)
        ->and($result['rows'][0]['order_number'])->toBe('SO-2026-0101')
        ->and($result['rows'][0]['customer_name'])->toBe('Aster & Pine Hotels');
});

test('a confirmed order can be fulfilled through its grid action', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['status' => 'confirmed']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('gridAction', 'orders', 'fulfill', [(string) $order->id]);

    expect($order->refresh()->status)->toBe('fulfilled');
});
