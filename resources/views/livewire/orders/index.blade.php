<div class="min-h-full bg-stone-50 px-4 py-6 dark:bg-zinc-950 sm:px-6 lg:px-8">
    <div class="mx-auto flex max-w-[96rem] flex-col gap-6">
        <header class="flex flex-col justify-between gap-4 border-b border-stone-200 pb-5 dark:border-zinc-800 sm:flex-row sm:items-end">
            <div class="flex flex-col gap-1">
                <flux:text class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-400">
                    Northstar Wholesale / Sales desk
                </flux:text>
                <flux:heading size="xl">Orders</flux:heading>
                <flux:text>Search, filter, save a working view, or export the current register.</flux:text>
            </div>

            <flux:button variant="primary" icon="plus" :href="route('orders.create')" wire:navigate>
                New order
            </flux:button>
        </header>

        <section aria-label="Order register" class="min-w-0">
            <x-laragrid :grid="$this->gridDefinition('orders')" />
        </section>
    </div>
</div>
