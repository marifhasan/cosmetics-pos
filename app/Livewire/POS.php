<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class POS extends Component
{
    public string $search = '';
    public string $customerPhone = '';
    public ?Customer $selectedCustomer = null;
    public array $cart = [];
    public string $paymentMethod = 'cash';
    public float $cashReceived = 0;
    public float $taxRate = 8.5; // Default tax rate
    
    public bool $showCustomerModal = false;
    public string $newCustomerName = '';
    public string $newCustomerEmail = '';

    protected $rules = [
        'customerPhone' => 'nullable|string|max:20',
        'newCustomerName' => 'required_if:showCustomerModal,true|string|max:255',
        'newCustomerEmail' => 'nullable|email|max:255',
        'paymentMethod' => 'required|in:cash,card,digital',
        'cashReceived' => 'required_if:paymentMethod,cash|numeric|min:0',
    ];

    public function mount()
    {
        $taxRateSetting = \App\Models\Setting::getValue('tax_rate', ['value' => 8.5]);
        $this->taxRate = is_array($taxRateSetting) ? $taxRateSetting['value'] : $taxRateSetting;
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        return ProductVariant::with(['product.brand'])
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where(function($query) {
                $query->where('sku', 'like', '%' . $this->search . '%')
                      ->orWhere('barcode', 'like', '%' . $this->search . '%')
                      ->orWhere('variant_name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('product', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('barcode', 'like', '%' . $this->search . '%');
                      });
            })
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function subtotal()
    {
        return collect($this->cart)->sum(function($item) {
            return $item['quantity'] * $item['price'];
        });
    }

    #[Computed]
    public function taxAmount()
    {
        return $this->subtotal * ($this->taxRate / 100);
    }

    #[Computed]
    public function total()
    {
        return $this->subtotal + $this->taxAmount;
    }

    #[Computed]
    public function change()
    {
        if ($this->paymentMethod === 'cash') {
            return max(0, $this->cashReceived - $this->total);
        }
        return 0;
    }

    public function addToCart($variantId)
    {
        $variant = ProductVariant::with('product')->find($variantId);
        
        if (!$variant || $variant->stock_quantity <= 0) {
            $this->addError('cart', 'Product not available or out of stock');
            return;
        }

        $cartKey = 'variant_' . $variantId;
        
        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['quantity'] >= $variant->stock_quantity) {
                $this->addError('cart', 'Not enough stock available');
                return;
            }
            $this->cart[$cartKey]['quantity']++;
        } else {
            $this->cart[$cartKey] = [
                'variant_id' => $variant->id,
                'name' => $variant->product->name . ' - ' . $variant->variant_name,
                'sku' => $variant->sku,
                'price' => $variant->selling_price,
                'quantity' => 1,
                'max_quantity' => $variant->stock_quantity,
            ];
        }

        $this->search = '';
    }

    public function updateQuantity($cartKey, $quantity)
    {
        if ($quantity <= 0) {
            unset($this->cart[$cartKey]);
            return;
        }

        if ($quantity > $this->cart[$cartKey]['max_quantity']) {
            $this->addError('cart', 'Not enough stock available');
            return;
        }

        $this->cart[$cartKey]['quantity'] = $quantity;
    }

    public function removeFromCart($cartKey)
    {
        unset($this->cart[$cartKey]);
    }

    public function searchCustomer()
    {
        if (empty($this->customerPhone)) {
            $this->selectedCustomer = null;
            return;
        }

        $this->selectedCustomer = Customer::where('phone', $this->customerPhone)->first();
    }

    public function showCreateCustomerModal()
    {
        $this->showCustomerModal = true;
        $this->newCustomerName = '';
        $this->newCustomerEmail = '';
    }

    public function createCustomer()
    {
        $this->validate([
            'newCustomerName' => 'required|string|max:255',
            'newCustomerEmail' => 'nullable|email|max:255',
        ]);

        $this->selectedCustomer = Customer::create([
            'phone' => $this->customerPhone,
            'name' => $this->newCustomerName,
            'email' => $this->newCustomerEmail,
        ]);

        $this->showCustomerModal = false;
        $this->newCustomerName = '';
        $this->newCustomerEmail = '';
    }

    public function completeSale()
    {
        if (empty($this->cart)) {
            $this->addError('cart', 'Cart is empty');
            return;
        }

        $this->validate();

        if ($this->paymentMethod === 'cash' && $this->cashReceived < $this->total) {
            $this->addError('cashReceived', 'Cash received is less than total amount');
            return;
        }

        DB::transaction(function () {
            // Create sale
            $sale = Sale::create([
                'customer_id' => $this->selectedCustomer?->id,
                'user_id' => auth()->id() ?? 1,
                'subtotal' => $this->subtotal,
                'tax_amount' => $this->taxAmount,
                'total_amount' => $this->total,
                'payment_method' => $this->paymentMethod,
                'payment_status' => 'completed',
                'sale_date' => now(),
            ]);

            // Create sale items and update stock
            foreach ($this->cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['quantity'] * $item['price'],
                ]);

                // Update stock
                $variant = ProductVariant::find($item['variant_id']);
                $variant->updateStock(
                    quantityChange: -$item['quantity'],
                    movementType: 'sale',
                    referenceId: $sale->id,
                    userId: auth()->id() ?? 1,
                    notes: "Sale #{$sale->sale_number}"
                );
            }

            // Add loyalty points if customer exists
            if ($this->selectedCustomer) {
                $pointsEarned = floor($this->total); // 1 point per dollar
                $sale->update(['points_earned' => $pointsEarned]);
                $this->selectedCustomer->addLoyaltyPoints($pointsEarned, $sale->id);
            }

            // Clear cart and reset form
            $this->reset(['cart', 'customerPhone', 'search', 'cashReceived']);
            $this->selectedCustomer = null;

            session()->flash('sale_completed', [
                'sale_number' => $sale->sale_number,
                'total' => $this->total,
                'change' => $this->change,
            ]);
        });
    }

    public function clearCart()
    {
        $this->cart = [];
    }

    public function render()
    {
        return view('livewire.p-o-s')->layout('layouts.pos');
    }
}