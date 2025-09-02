<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - POS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name', 'Cosmetics POS') }}</h1>
                        <span class="ml-4 px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                            Point of Sale
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Cashier:</span> {{ auth()->user()->name ?? 'Guest' }}
                        </div>
                        <div class="text-sm text-gray-600">
                            {{ now()->format('M d, Y - H:i') }}
                        </div>
                        <a href="/admin" 
                           class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition-colors">
                            Admin Panel
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    
    <!-- Success Modal for Sale Completion -->
    @if(session('sale_completed'))
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-data="{ show: true }" x-show="show">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4" @click.away="show = false">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Sale Completed Successfully!</h3>
                <div class="text-sm text-gray-600 space-y-2">
                    <p><span class="font-medium">Sale #:</span> {{ session('sale_completed')['sale_number'] }}</p>
                    <p><span class="font-medium">Total:</span> ৳{{ number_format(session('sale_completed')['total'], 2) }}</p>
                    @if(session('sale_completed')['change'] > 0)
                        <p class="text-lg font-bold text-green-600">
                            <span class="font-medium">Change:</span> ৳{{ number_format(session('sale_completed')['change'], 2) }}
                        </p>
                    @endif
                </div>
                <button @click="show = false" 
                        class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Continue
                </button>
            </div>
        </div>
    </div>
    @endif
</body>
</html>
