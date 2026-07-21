# YouTube Video Scenario: Excel-Style Order Entry with Laragrid

## Video concept

**Working title:** Build an Excel-Style Order Entry Grid with Laravel, Livewire and Laragrid

**Estimated length:** 15–17 minutes

The core story is: begin with the finished result, demonstrate the readonly register, and then show how little code is required to create it. Repeat that pattern for editable order entry.

## Recording preparation

Before recording:

- Run `php artisan migrate:fresh --seed`.
- Sign in with `avery@northstar.test` / `password`.
- Open [http://laragrid.test/orders](http://laragrid.test/orders).
- Use a keystroke overlay so viewers can see `Enter`, `Ctrl+Z`, arrows, and `F2`.
- Set browser zoom around 110% so the grid remains readable on YouTube.
- Clear any previously saved Laragrid views.

## Timeline

| Time | On screen | What to explain |
|---|---|---|
| 0:00 | Fast montage of filtering orders, editing a line, formula recalculation, undo, and saving | “Today we're building an Excel-style, keyboard-first order system using Laravel 13, Livewire 4, and Laragrid.” |
| 0:25 | Orders register | Briefly introduce the demo domain and the difference between a normal table and Laragrid. |
| 1:10 | Readonly grid interactions | Search, filter, sort, resize columns, save a view, export, and run a row action. |
| 4:00 | Readonly grid code | Show the fluent `Grid::make()` definition and the one-line Blade mount. |
| 6:30 | New order screen | Create an order almost entirely with the keyboard. |
| 10:30 | Editable grid code | Show product lookup, enrichment, formulas, auto-append, validation, and completion behavior. |
| 13:30 | Persistence code | Show server-side validation and transactional recalculation. |
| 15:00 | Tests and conclusion | Run focused tests and summarize the package's strengths. |

## 1. Cold open

Start directly on the order register—do not begin in the terminal.

Quick montage:

1. Type `Juniper` into the grid search.
2. Choose `Confirmed` from the Status filter.
3. Open the saved-views menu.
4. Cut to order entry and select a product.
5. Change its quantity and show the total updating.
6. Press `Ctrl+Z`.
7. Select `— Finish order —`.
8. Press Enter on “Create order.”

Suggested opening:

> This looks like a spreadsheet, but every edit is authorized, validated, and recalculated by Laravel. There's no custom JavaScript and no frontend grid library to configure.

## 2. Demonstrate the readonly register

Reset the register so all orders are visible.

### Search and filtering

1. Click the Laragrid search field.
2. Type `Juniper`.
3. Open the Status filter and choose `Confirmed`.

You should now have a small, focused result set.

Explain:

- Search and filters run server-side.
- The client can only use fields declared in the grid definition.
- Pagination and totals are also server-authoritative.

### Sorting and layout

1. Clear the search but keep `Confirmed`.
2. Click the `Total, €` header.
3. Click it again to reverse direction.
4. Drag the boundary of the Customer column to resize it.
5. Open the column chooser and hide `Owner`.
6. Reload the page to show that column widths and visibility persist.

### Saved views

1. Set Status to `Confirmed`.
2. Search for `Juniper`.
3. Open the `Views` control.
4. Choose “Save current view.”
5. Name it `Confirmed · Juniper`.
6. Clear the filters.
7. Reopen Views and select the saved view.

Explain that saved views include search, filters, sort, page size, widths, and hidden columns—and follow the authenticated user across devices.

### Export

With the filtered view active:

1. Click `Export`.
2. Select XLSX or CSV.

Explain that the export contains the entire filtered result set, not only the visible page.

### Row action

1. Find a confirmed order.
2. Click the `✓ Fulfill` action in its right-hand action column.
3. Accept “Mark this order as fulfilled?”

If the Confirmed filter is active, the row should disappear after fulfillment. This demonstrates that the page refetched automatically.

## 3. Show the readonly code

Open `app/Livewire/Orders/Index.php` around line 34.

First highlight:

```php
Grid::make('orders')
    ->query(fn (): Builder => $this->ordersQuery())
    ->authorize(fn () => Gate::authorize('viewAny', Order::class))
```

Main talking point:

> A server-side grid is fail-closed. If I omit authorization, the grid refuses to expose its data.

Then scroll through:

- `paginate()`
- `defaultSort()`
- `searchable()`
- `filters()`
- Typed columns
- Footer aggregate
- Exports
- Saved views
- Actions
- Theme and layout

Pause on the fulfillment action around line 66. Point out that visibility is checked server-side and authorization is repeated before mutation.

Finally show `resources/views/livewire/orders/index.blade.php`:

```blade
<x-laragrid :grid="$this->gridDefinition('orders')" />
```

Emphasize that there are no table loops, Alpine components, or custom JavaScript.

## 4. Demonstrate editable order entry

Click `New order`.

### Fill the order header

1. Choose `North Quay Kitchen`.
2. Keep today's date.
3. Enter: `Deliver before 12:00 — receiving desk notified.`

### Enter the first product

The first Product cell should already be focused.

1. Type `coffee`.
2. Select `Single-origin coffee beans · PAN-1001`.
3. Press Enter.

Point out that SKU, unit, price, and VAT appear automatically.

Continue mostly with the keyboard:

1. Set quantity to `6`.
2. Accept the populated price.
3. Set discount to `10`.
4. Accept VAT.
5. Show the calculated line total and footer total.

Explain that the product picker performed a server request and the server returned additional values for the row.

### Add a second product

On the next blank row:

1. Search for `napkins`.
2. Select `Recycled paper napkins`.
3. Set quantity to `4`.
4. Leave discount at zero.

### Show undo

1. Change the second product's discount to `15`.
2. Point out the recalculated line and footer totals.
3. Press `Ctrl+Z`.
4. Show the discount and totals reverting.

Also mention:

- `Ctrl+Y` redoes the change.
- `Insert` adds a row.
- `Ctrl+D` fills down.
- `Ctrl+C` copies selections as spreadsheet-compatible TSV.

### End the order

Move to the Product cell of the next blank row.

1. Open its product picker with Enter, typing, F2, or double-click.
2. Select `— Finish order —`.

Focus should move to the `Create order` button.

Explain:

> This doesn't insert a fake product. It sends a semantic completion signal and moves focus to the save button.

Press Enter to save.

You'll return to the register. Search for the newly created order—likely `SO-2026-0033` if the original seed data was untouched.

## 5. Show the editable grid code

Open `app/Livewire/Orders/Create.php` around line 105.

Begin with the mode switch:

```php
Grid::make('lines')
    ->editable()
    ->rowsFrom('lines')
    ->authorize(...)
```

Then highlight the row lifecycle around lines 112–123:

```php
->defaultRows(4)
->newRowUsing(...)
->minRows(1)
->autoAppend()
->focusOnMount()
->focusOutTo('[data-save-order]')
->onCompleteFocus('[data-save-order]')
->keymap('entry')
```

Explain that `newRowUsing()` provides defaults for both initial and automatically inserted rows.

### Product enrichment

Show the product column around line 126:

```php
SearchSelectColumn::make('product_id')
    ->optionsUsing(...)
    ->onSelect(function (RowContext $row, mixed $value): void {
        // Resolve the selected product on the server...
        $row->set('sku', $product->sku);
        $row->set('unit', $product->unit);
        $row->set('unit_price', $product->unit_price);
        $row->set('tax_rate', $product->tax_rate);
    })
```

Talking point:

> The browser selects only a product ID. Laravel resolves the real product and sends authoritative values back into the row.

Show `endOfListOption()` immediately afterward.

### Formula column

Show the formula around line 151:

```php
FormulaColumn::make('line_total')
    ->formula(
        'round(quantity * unit_price * '
        .'(1 - discount_percent / 100) * '
        .'(1 + tax_rate / 100), 2)'
    )
```

Explain that it calculates immediately in the browser, then Laravel recomputes it authoritatively during synchronization.

## 6. Show saving and persistence

First show `app/Livewire/Orders/Create.php` around line 64.

Focus on:

```php
$lines = $this->gridRows('lines');
```

Explain that this removes Laragrid bookkeeping and untouched trailing rows.

Then show:

- Header validation
- Line validation
- Authorization
- Redirect and toast

Open `app/Actions/CreateOrder.php` around line 18.

Highlight:

- Database transaction
- Product records fetched from the database
- SKU, name, and unit copied from authoritative products
- Server-side subtotal, discount, VAT, and total calculations
- Order and order-item creation

Suggested explanation:

> I deliberately don't trust the formula value sent by the browser. The action calculates every monetary amount again before saving.

## 7. Show the tests

Open `tests/Feature/Orders/CreateOrderTest.php` around line 10.

Show the first test:

- Set customer and grid rows
- Call `save`
- Assert the redirect
- Assert exact persisted monetary values

Then show the enrichment RPC test around line 76.

Finally run:

```bash
php artisan test --compact tests/Feature/Orders
```

Close with:

> The test drives the same grid RPC used by the browser, so we're testing the integration rather than duplicating its internal implementation.

## Closing summary

End back on the finished order register.

Summarize the three differentiators:

1. Spreadsheet-like keyboard interaction.
2. Laravel remains authoritative for authorization, validation, and calculations.
3. The entire grid is configured from one fluent PHP definition, with almost no Blade markup.

Suggested final sentence:

> If your users already think in spreadsheets but your application needs Laravel-level security and business rules, Laragrid offers a very interesting middle ground.
