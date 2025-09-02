<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $sale->sale_number }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 p-4">
    <div class="max-w-md mx-auto bg-white shadow-lg">
        <!-- Header with print button -->
        <div class="bg-blue-600 text-white p-4 print:hidden">
            <div class="flex justify-between items-center">
                <h1 class="text-lg font-semibold">Receipt Preview</h1>
                <div class="space-x-2">
                    <button onclick="window.print()" 
                            class="bg-white text-blue-600 px-4 py-2 rounded hover:bg-gray-100">
                        Print Receipt
                    </button>
                    <a href="{{ url()->previous() }}" 
                       class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">
                        Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Receipt Content -->
        <div class="p-6" id="receipt-content">
            <!-- Store Header -->
            <div class="text-center border-b border-gray-300 pb-4 mb-4">
                <h1 class="text-xl font-bold">{{ $storeSettings['name'] }}</h1>
                @if($storeSettings['address'])
                    <p class="text-sm text-gray-600">{{ $storeSettings['address'] }}</p>
                @endif
                @if($storeSettings['phone'])
                    <p class="text-sm text-gray-600">Phone: {{ $storeSettings['phone'] }}</p>
                @endif
            </div>

            <!-- Sale Info -->
            <div class="mb-4 text-sm">
                <div class="flex justify-between">
                    <span>Receipt #:</span>
                    <span class="font-mono">{{ $sale->sale_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Date:</span>
                    <span>{{ $sale->sale_date->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Cashier:</span>
                    <span>{{ $sale->user->name }}</span>
                </div>
                @if($sale->customer)
                    <div class="flex justify-between">
                        <span>Customer:</span>
                        <span>{{ $sale->customer->name ?? $sale->customer->phone }}</span>
                    </div>
                    @if($sale->points_earned > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Points Earned:</span>
                            <span>{{ $sale->points_earned }}</span>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Items -->
            <div class="border-t border-b border-gray-300 py-4 mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-1">Item</th>
                            <th class="text-center py-1">Qty</th>
                            <th class="text-right py-1">Price</th>
                            <th class="text-right py-1">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->saleItems as $item)
                            <tr>
                                <td class="py-1">
                                    <div class="font-medium">{{ $item->productVariant->product->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->productVariant->variant_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $item->productVariant->sku }}</div>
                                </td>
                                <td class="text-center py-1">{{ $item->quantity }}</td>
                                <td class="text-right py-1">৳{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right py-1">৳{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="text-sm space-y-1 mb-4">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span>৳{{ number_format($sale->subtotal, 2) }}</span>
                </div>
                @if($sale->tax_amount > 0)
                    <div class="flex justify-between">
                        <span>Tax:</span>
                        <span>৳{{ number_format($sale->tax_amount, 2) }}</span>
                    </div>
                @endif
                @if($sale->discount_amount > 0)
                    <div class="flex justify-between text-red-600">
                        <span>Discount:</span>
                        <span>-৳{{ number_format($sale->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-bold border-t pt-2">
                    <span>Total:</span>
                    <span>৳{{ number_format($sale->total_amount, 2) }}</span>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="text-sm mb-4">
                <div class="flex justify-between">
                    <span>Payment Method:</span>
                    <span class="capitalize">{{ $sale->payment_method }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Status:</span>
                    <span class="capitalize text-green-600">{{ $sale->payment_status }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-600 border-t pt-4">
                <p>{{ $storeSettings['footer'] }}</p>
                <p class="mt-2">Generated on {{ now()->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body { 
                margin: 0; 
                padding: 0; 
                background: white; 
            }
            .max-w-md { 
                max-width: none; 
                margin: 0; 
                box-shadow: none; 
            }
            #receipt-content { 
                padding: 20px; 
            }
        }
    </style>
</body>
</html>
