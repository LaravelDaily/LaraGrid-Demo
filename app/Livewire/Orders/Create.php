<?php

namespace App\Livewire\Orders;

use App\Actions\CreateOrder as CreateOrderAction;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LaraGrid\Aggregate;
use LaraGrid\Columns\DecimalColumn;
use LaraGrid\Columns\FormulaColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\ReadonlyColumn;
use LaraGrid\Columns\SearchSelectColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Editing\RowContext;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Livewire\WithLaraGrid;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New order')]
class Create extends Component
{
    use WithLaraGrid;

    public ?int $customerId = null;

    public string $orderDate = '';

    public string $notes = '';

    /** @var list<array<string, mixed>> */
    public array $lines = [];

    public function mount(): void
    {
        $this->orderDate = now()->toDateString();
        $this->lines = $this->gridMountRows('lines');
    }

    /**
     * @return Collection<int, Customer>
     */
    #[Computed]
    public function customers(): Collection
    {
        return Customer::query()
            ->where('active', true)
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'segment']);
    }

    public function save(CreateOrderAction $createOrder): void
    {
        Gate::authorize('create', Order::class);

        $validated = $this->validate([
            'customerId' => ['required', 'integer', Rule::exists(Customer::class, 'id')->where('active', true)],
            'orderDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $lines = $this->gridRows('lines');

        $validatedLines = Validator::make(['lines' => $lines], [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')->where('active', true)],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'lines.*.discount_percent' => ['required', 'numeric', 'between:0,100'],
            'lines.*.tax_rate' => ['required', 'numeric', 'between:0,100'],
        ])->validate()['lines'];

        $customer = Customer::query()
            ->where('id', $validated['customerId'])
            ->firstOrFail();

        $createOrder->handle(
            Auth::user(),
            $customer,
            $validated['orderDate'],
            $validated['notes'],
            $validatedLines,
        );

        Flux::toast(variant: 'success', text: 'Order created.');

        $this->redirectRoute('orders.index', navigate: true);
    }

    /**
     * @return array<string, Grid>
     */
    protected function grids(): array
    {
        return [
            'lines' => Grid::make('lines')
                ->editable()
                ->rowsFrom('lines')
                ->authorize(fn () => Gate::authorize('create', Order::class))
                ->defaultRows(4)
                ->newRowUsing(fn (): array => [
                    'quantity' => 1,
                    'discount_percent' => '0.00',
                ])
                ->minRows(1)
                ->autoAppend()
                ->padRows(8)
                ->focusOnMount()
                ->focusOutTo('[data-save-order]')
                ->onCompleteFocus('[data-save-order]')
                ->keymap('entry')
                ->columns([
                    SerialColumn::make(),
                    SearchSelectColumn::make('product_id')->label('Product')
                        ->optionsUsing(fn (string $term): array => $this->productOptions($term))
                        ->onSelect(function (RowContext $row, mixed $value): void {
                            $product = Product::query()
                                ->where('active', true)
                                ->where('id', $value)
                                ->firstOrFail();

                            $row->set('sku', $product->sku);
                            $row->set('unit', $product->unit);
                            $row->set('unit_price', $product->unit_price);
                            $row->set('tax_rate', $product->tax_rate);
                        })
                        ->endOfListOption('— Finish order —')
                        ->required()
                        ->rules(['integer', 'exists:products,id'])
                        ->minChars(0)
                        ->limit(20)
                        ->grow(),
                    ReadonlyColumn::make('sku')->label('SKU')->width(105),
                    IntegerColumn::make('quantity')->label('Qty')->required()->rules(['integer', 'min:1', 'max:10000'])->width(80),
                    ReadonlyColumn::make('unit')->label('Unit')->width(75),
                    DecimalColumn::make('unit_price')->label('Price, €')->scale(2)->required()->rules(['numeric', 'min:0'])->width(110),
                    DecimalColumn::make('discount_percent')->label('Disc. %')->scale(2)->required()->rules(['numeric', 'between:0,100'])->width(100),
                    DecimalColumn::make('tax_rate')->label('VAT %')->scale(2)->required()->rules(['numeric', 'between:0,100'])->width(90),
                    FormulaColumn::make('line_total')->label('Line total, €')
                        ->formula('round(quantity * unit_price * (1 - discount_percent / 100) * (1 + tax_rate / 100), 2)')
                        ->width(140),
                ])
                ->footer([Aggregate::sum('line_total')->format('number', ['scale' => 2])])
                ->stickyHeader()
                ->striped()
                ->statusBar()
                ->density(GridDensity::Compact)
                ->theme('amber')
                ->maxHeight('58vh'),
        ];
    }

    public function render(): View
    {
        return view('livewire.orders.create');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function productOptions(string $term): array
    {
        $options = Product::query()
            ->where('active', true)
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('sku', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'sku', 'name'])
            ->map(fn (Product $product): array => [
                'value' => (string) $product->id,
                'label' => $product->name.' · '.$product->sku,
            ])
            ->all();

        return array_values($options);
    }
}
