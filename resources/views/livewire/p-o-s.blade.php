<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column - Product Search & Cart -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Product Search -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Product Search</h2>
            <div class="relative">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by product name, SKU, or barcode..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                    autofocus
                >
                <div class="absolute right-3 top-3">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Search Results -->
            @if($this->searchResults->count() > 0)
                <div class="mt-4 border border-gray-200 rounded-lg max-h-64 overflow-y-auto">
                    @foreach($this->searchResults as $variant)
                        <div wire:click="addToCart({{ $variant->id }})" 
                             class="p-4 border-b border-gray-200 last:border-b-0 hover:bg-gray-50 cursor-pointer transition-colors">
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">
                                        {{ $variant->product->name }} - {{ $variant->variant_name }}
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        SKU: {{ $variant->sku }} | 
                                        Brand: {{ $variant->product->brand->name }} |
                                        Stock: {{ $variant->stock_quantity }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-gray-900">
                                        ৳{{ number_format($variant->selling_price, 2) }}
                                    </p>
                                    <p class="text-sm text-green-600">In Stock</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif(strlen($search) >= 2)
                <div class="mt-4 p-4 text-center text-gray-500 border border-gray-200 rounded-lg">
                    No products found matching "{{ $search }}"
                </div>
            @endif
        </div>

        <!-- Shopping Cart -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Shopping Cart</h2>
                @if(!empty($cart))
                    <button wire:click="clearCart" 
                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                        Clear Cart
                    </button>
                @endif
            </div>

            @if(empty($cart))
                <div class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 1.5M7 13l1.5-1.5M13 13v6a2 2 0 002 2h4a2 2 0 002-2v-6M9 13v6a2 2 0 01-2 2H3a2 2 0 01-2-2v-6" />
                    </svg>
                    <p>Cart is empty</p>
                    <p class="text-sm">Search and add products to start a sale</p>
                </div>
            @else
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($cart as $key => $item)
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">{{ $item['name'] }}</h4>
                                <p class="text-sm text-gray-600">{{ $item['sku'] }}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center space-x-2">
                                    <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})"
                                            class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <span class="w-8 text-center font-medium">{{ $item['quantity'] }}</span>
                                    <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})"
                                            class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-right min-w-0">
                                    <p class="font-semibold">৳{{ number_format($item['quantity'] * $item['price'], 2) }}</p>
                                    <p class="text-sm text-gray-600">৳{{ number_format($item['price'], 2) }} each</p>
                                </div>
                                <button wire:click="removeFromCart('{{ $key }}')"
                                        class="text-red-600 hover:text-red-800 ml-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @error('cart')
                <div class="mt-3 text-red-600 text-sm">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Right Column - Customer & Checkout -->
    <div class="space-y-6">
        <!-- Customer Selection -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer</h2>
            <div class="space-y-4">
                <div>
                    <input 
                        type="text" 
                        wire:model.blur="customerPhone"
                        wire:change="searchCustomer"
                        placeholder="Enter customer phone number..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                @if($selectedCustomer)
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-green-900">{{ $selectedCustomer->name }}</h4>
                                <p class="text-sm text-green-700">{{ $selectedCustomer->phone }}</p>
                                @if($selectedCustomer->email)
                                    <p class="text-sm text-green-700">{{ $selectedCustomer->email }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-green-700">Loyalty Points</p>
                                <p class="font-bold text-green-900">{{ $selectedCustomer->loyalty_points }}</p>
                            </div>
                        </div>
                    </div>
                @elseif($customerPhone && !$selectedCustomer)
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800 mb-3">Customer not found. Would you like to create a new customer?</p>
                        <button wire:click="showCreateCustomerModal" 
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            Create New Customer
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
            <div class="space-y-3">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal:</span>
                    <span>৳{{ number_format($this->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Tax ({{ $taxRate }}%):</span>
                    <span>৳{{ number_format($this->taxAmount, 2) }}</span>
                </div>
                <div class="border-t pt-3">
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total:</span>
                        <span>৳{{ number_format($this->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select wire:model="paymentMethod" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="digital">Digital</option>
                    </select>
                </div>

                @if($paymentMethod === 'cash')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cash Received</label>
                        <input 
                            type="number" 
                            wire:model.blur="cashReceived"
                            step="0.01" 
                            min="0"
                            placeholder="0.00"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('cashReceived')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        
                        @if($cashReceived > 0 && $cashReceived >= $this->total)
                            <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-green-700 font-medium">Change:</span>
                                    <span class="text-green-900 font-bold text-lg">৳{{ number_format($this->change, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <button 
                    wire:click="completeSale"
                    @disabled(empty($cart) || ($paymentMethod === 'cash' && $cashReceived < $this->total))
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold text-lg transition-colors"
                >
                    Complete Sale - ৳{{ number_format($this->total, 2) }}
                </button>
            </div>
        </div>
    </div>

    <!-- Create Customer Modal -->
    @if($showCustomerModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Create New Customer</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" value="{{ $customerPhone }}" disabled 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" wire:model="newCustomerName" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('newCustomerName')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                        <input type="email" wire:model="newCustomerEmail" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('newCustomerEmail')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button wire:click="createCustomer" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        Create Customer
                    </button>
                    <button wire:click="$set('showCustomerModal', false)" 
                            class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>