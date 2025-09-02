<x-filament-panels::page>
    <div class="max-w-7xl mx-auto">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl shadow-lg p-8 text-center border border-blue-200">
            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Point of Sale System</h1>
            <p class="text-gray-600 mb-8 text-lg">
                Launch the fast POS interface for quick sales processing and customer checkout.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="p-6 border border-gray-200 rounded-lg">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Fast Product Search</h3>
                    <p class="text-sm text-gray-600">Search by product name, SKU, or barcode for quick product selection.</p>
                </div>
                
                <div class="p-6 border border-gray-200 rounded-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Customer Management</h3>
                    <p class="text-sm text-gray-600">Quick customer lookup and new customer creation with loyalty points.</p>
                </div>
                
                <div class="p-6 border border-gray-200 rounded-lg">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Multiple Payment Methods</h3>
                    <p class="text-sm text-gray-600">Accept cash, card, and digital payments with automatic change calculation.</p>
                </div>
            </div>
            
            <div class="space-y-4">
                <a href="/pos" 
                   target="_blank"
                   class="inline-flex items-center px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl text-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg class="w-7 h-7 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Launch POS System
                </a>
                <p class="text-sm text-gray-600 font-medium">
                    🚀 Opens in a new tab for dedicated POS operations
                </p>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl shadow-md p-6 text-center border border-green-200 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-3xl font-bold text-green-700">{{ \App\Models\Sale::whereDate('sale_date', today())->count() }}</div>
                <div class="text-sm text-green-600 font-medium">Today's Sales</div>
            </div>
            <div class="bg-gradient-to-br from-blue-50 to-cyan-100 rounded-xl shadow-md p-6 text-center border border-blue-200 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="text-3xl font-bold text-blue-700">৳{{ number_format(\App\Models\Sale::whereDate('sale_date', today())->sum('total_amount'), 0) }}</div>
                <div class="text-sm text-blue-600 font-medium">Today's Revenue</div>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-violet-100 rounded-xl shadow-md p-6 text-center border border-purple-200 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div class="text-3xl font-bold text-purple-700">{{ \App\Models\ProductVariant::where('stock_quantity', '>', 0)->count() }}</div>
                <div class="text-sm text-purple-600 font-medium">Products Available</div>
            </div>
            <div class="bg-gradient-to-br from-red-50 to-rose-100 rounded-xl shadow-md p-6 text-center border border-red-200 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="text-3xl font-bold text-red-700">{{ \App\Models\ProductVariant::lowStock()->count() }}</div>
                <div class="text-sm text-red-600 font-medium">Low Stock Alerts</div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
