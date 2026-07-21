<div class="min-h-full bg-stone-50 px-4 py-6 dark:bg-zinc-950 sm:px-6 lg:px-8">
    <form wire:submit="save" class="mx-auto flex max-w-[96rem] flex-col gap-6">
        <header class="flex flex-col justify-between gap-4 border-b border-stone-200 pb-5 dark:border-zinc-800 sm:flex-row sm:items-end">
            <div class="flex flex-col gap-1">
                <flux:text class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-400">
                    Northstar Wholesale / Order entry
                </flux:text>
                <flux:heading size="xl">New sales order</flux:heading>
                <flux:text>Choose a product, enter quantities, and let the grid calculate each line.</flux:text>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('orders.index')" wire:navigate>
                Back to orders
            </flux:button>
        </header>

        <section class="grid gap-4 border-b border-stone-200 pb-6 dark:border-zinc-800 lg:grid-cols-[minmax(18rem,1.5fr)_minmax(12rem,0.65fr)_minmax(18rem,1fr)]">
            <flux:select wire:model="customerId" label="Customer" placeholder="Choose a customer">
                @foreach ($this->customers as $customer)
                    <flux:select.option :value="$customer->id" wire:key="customer-{{ $customer->id }}">
                        {{ $customer->company_name }} · {{ $customer->segment }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="orderDate" label="Order date" type="date" />

            <flux:textarea wire:model="notes" label="Delivery note" placeholder="Optional instructions for the receiving team" rows="2" resize="none" />
        </section>

        <section aria-label="Order lines" class="min-w-0">
            <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                <div>
                    <flux:heading size="lg">Order lines</flux:heading>
                    <flux:text class="text-sm">Paste rows from Excel, use Ctrl+D to fill down, and Ctrl+Z to undo.</flux:text>
                </div>

                <div class="flex items-center gap-2 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="rounded border border-zinc-300 px-1.5 py-0.5 dark:border-zinc-700">Return</span>
                    advances
                    <span class="rounded border border-zinc-300 px-1.5 py-0.5 dark:border-zinc-700">fn&nbsp;F2</span>
                    edits · a new line appears automatically
                </div>
            </div>

            <x-laragrid :grid="$this->gridDefinition('lines')" :rows="$lines" />

            @error('lines')
                <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </section>

        <footer class="flex flex-col justify-between gap-4 border-t border-stone-200 pt-5 dark:border-zinc-800 sm:flex-row sm:items-center">
            <flux:text class="max-w-2xl text-sm">
                Prices may be adjusted per order. Product names, SKUs, and units are always copied from the current catalogue when you save.
            </flux:text>

            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('orders.index')" wire:navigate>Cancel</flux:button>
                <flux:button data-save-order variant="primary" type="submit" icon="check">
                    Create order
                </flux:button>
            </div>
        </footer>
    </form>
</div>
