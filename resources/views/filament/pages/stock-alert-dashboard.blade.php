<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Alert Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <x-heroicon-s-x-circle class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Out of Stock</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ \App\Models\ProductVariant::outOfStock()->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                            <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Low Stock</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ \App\Models\ProductVariant::lowStock()->where('stock_quantity', '>', 0)->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <x-heroicon-s-cube class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Products</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ \App\Models\ProductVariant::where('is_active', true)->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <x-heroicon-s-check-circle class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Well Stocked</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ \App\Models\ProductVariant::where('is_active', true)->whereColumn('stock_quantity', '>', 'min_stock_level')->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Banner -->
        @php
            $criticalCount = \App\Models\ProductVariant::outOfStock()->count();
            $lowStockCount = \App\Models\ProductVariant::lowStock()->where('stock_quantity', '>', 0)->count();
        @endphp

        @if($criticalCount > 0)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                            Critical Stock Alert
                        </h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <p>
                                You have <strong>{{ $criticalCount }}</strong> product{{ $criticalCount > 1 ? 's' : '' }} completely out of stock.
                                Immediate action required to avoid lost sales.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($lowStockCount > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            Low Stock Warning
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>
                                You have <strong>{{ $lowStockCount }}</strong> product{{ $lowStockCount > 1 ? 's' : '' }} running low on stock.
                                Consider reordering soon to maintain inventory levels.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>

