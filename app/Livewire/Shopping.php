<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderProduct;
use Livewire\Component;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Livewire\Attributes\On;
use App\Models\TemporaryDeliveryOrder;

class Shopping extends Component
{
    // public ?Client $client;
    public TemporaryDeliveryOrder $temporaryDeliveryOrder;
    //public $lastDeliveryOrders = [];
    public $orderChannels = [];
    public $deliveryAgentOptions = [];
    public $disabledAgentIds = [];
    public $order_channel_id;
    public $delivery_agent_id;
    public $price;
    public $delivery_fees;
    public $details;

    //other
    public ?Supplier $supplier;

    public ?int $supplierId;
    public $supplierProducts = [];


    public $clientLastSupplierProducts;

    public $cartProducts = [];

    public  $cartSupplierProducts = [];

    public function mount(
        // ?Client $client,
        TemporaryDeliveryOrder $temporaryDeliveryOrder,
        //$lastDeliveryOrders,
        $orderChannels = [],
        $deliveryAgentOptions = [],
        $disabledAgentIds = []
    ) {
        // $this->client = $client;
        $this->temporaryDeliveryOrder = $temporaryDeliveryOrder;
        //$this->lastDeliveryOrders = $lastDeliveryOrders;
        $this->orderChannels = $orderChannels;
        $this->deliveryAgentOptions = $deliveryAgentOptions;
        $this->disabledAgentIds = $disabledAgentIds;
        $this->order_channel_id = $temporaryDeliveryOrder->order_channel_id ?? null;
        $this->delivery_agent_id = $temporaryDeliveryOrder->delivery_agent_id ?? null;
        $this->price = $temporaryDeliveryOrder->price;
        $this->delivery_fees = $temporaryDeliveryOrder->delivery_fees;
        $this->details = $temporaryDeliveryOrder->details ?? null;
        $this->cartProducts = $temporaryDeliveryOrder->product_data ?? [];
        if ($temporaryDeliveryOrder->supplier_id) {
            $this->supplierProducts = SupplierProduct::where('supplier_id', $temporaryDeliveryOrder->supplier_id)->get();
        }

        if ($temporaryDeliveryOrder->client && $temporaryDeliveryOrder->supplier) {
            $supplierProductIds = collect($this->cartProducts)->pluck('supplier_product_id')->toArray();
            $this->cartSupplierProducts = SupplierProduct::whereIn('id', $supplierProductIds)->pluck('name', 'id')->toArray();

            $this->clientLastSupplierProducts = DeliveryOrderProduct::with('deliveryOrder')
                ->whereHas('deliveryOrder', function ($query) use ($temporaryDeliveryOrder) {
                    $query->where('client_id', $temporaryDeliveryOrder->client->user_id)
                        ->where('supplier_id', $temporaryDeliveryOrder->supplier->user_id);
                })
                ->whereIn('supplier_product_id', $supplierProductIds)
                ->orderByDesc('id') // assumes latest = highest id
                ->get()
                ->unique('supplier_product_id')->keyBy('supplier_product_id');
        }



        $this->cartProducts = $temporaryDeliveryOrder->product_data ?? [];
        $this->temporaryDeliveryOrder->product_data = $this->cartProducts;
    }

    public function updateCartProducts($products)
    {
        $this->temporaryDeliveryOrder->product_data = $products;
        $this->temporaryDeliveryOrder->save();
        $this->cartProducts = $products;
        $supplierProductIds = collect($this->cartProducts)->pluck('supplier_product_id')->toArray();
        $this->cartSupplierProducts = SupplierProduct::whereIn('id', $supplierProductIds)->pluck('name', 'id')->toArray();
    }

    public function addProductToCart($productId)
    {
        $product = SupplierProduct::findOrFail($productId);

        $cart = $this->cartProducts;

        $isExists = collect($cart)->search(fn($item) => $item['supplier_product_id'] == $product->id);
        if ($isExists === false) {
            $cart[] = [
                'supplier_product_id' => $product->id,
                'price' => $product->price,
                'quantity' => 0,
            ];
            $this->updateCartProducts($cart);

            if ($this->temporaryDeliveryOrder->client && $this->temporaryDeliveryOrder->supplier) {
                $supplierProductIds = collect($this->cartProducts)->pluck('supplier_product_id')->toArray();
                $this->cartSupplierProducts = SupplierProduct::whereIn('id', $supplierProductIds)->pluck('name', 'id')->toArray();

                $temporaryDeliveryOrder = $this->temporaryDeliveryOrder;
                $this->clientLastSupplierProducts = DeliveryOrderProduct::with('deliveryOrder')
                    ->whereHas('deliveryOrder', function ($query) use ($temporaryDeliveryOrder) {
                        $query->where('client_id', $temporaryDeliveryOrder->client->user_id)
                            ->where('supplier_id', $temporaryDeliveryOrder->supplier->user_id);
                    })
                    ->whereIn('supplier_product_id', $supplierProductIds)
                    ->orderByDesc('id') // assumes latest = highest id
                    ->get()
                    ->unique('supplier_product_id')->keyBy('supplier_product_id');
            }
        } else {
            $this->dispatch('prdocutExists', name: $product->name);
        }
    }

    public function removeProductFromCart($index)
    {
        $cart = $this->cartProducts;
        unset($cart[$index]);
        $cart = array_values($cart);
        $this->updateCartProducts($cart);
    }

    public function updateCartQuantity($index)
    {
        $this->validateOnly("cartProducts.{$index}.quantity", [
            "cartProducts.{$index}.quantity" => 'required|integer|min:1',
        ], [
            "cartProducts.{$index}.quantity.required" => __("Quantity is required."),
            "cartProducts.{$index}.quantity.integer" => __("Quantity must be an integer."),
            "cartProducts.{$index}.quantity.min" => __("Quantity must be at least 1."),
        ]);

        // Save if valid
        $this->updateCartProducts($this->cartProducts);
    }

    public function updatedDetails($value)
    {
        $this->temporaryDeliveryOrder->details = $value !== '' ? $value : null;
        $this->temporaryDeliveryOrder->save();
    }

    public function updatedPrice($value)
    {
        $rules = [
            'price' => 'nullable|numeric|min:0',
        ];
        $messages = [
            'price.numeric' => __('Price must be a number.'),
            'price.min' => __('Price must be at least 0.'),
        ];
        $this->validateOnly('price', $rules, $messages);
        $this->temporaryDeliveryOrder->price = $value !== '' ? $value : 0;
        $this->temporaryDeliveryOrder->save();
    }
    public function updatedDeliveryFees($value)
    {
        $rules = [
            'delivery_fees' => 'nullable|numeric|min:0',
        ];
        $messages = [
            'delivery_fees.numeric' => __('Delivery fees must be a number.'),
            'delivery_fees.min' => __('Delivery fees must be at least 0.'),
        ];
        $this->validateOnly('delivery_fees', $rules, $messages);
        $this->temporaryDeliveryOrder->delivery_fees = $value !== '' ? $value : 0;
        $this->temporaryDeliveryOrder->save();
    }

    public function updatedDeliveryAgentId($value)
    {
        $this->temporaryDeliveryOrder->delivery_agent_id = $value !== '' ? $value : 0;
        $this->temporaryDeliveryOrder->save();
    }

    public function updatedOrderChannelId($value)
    {
        $this->temporaryDeliveryOrder->order_channel_id = $value !== '' ? $value : 0;
        $this->temporaryDeliveryOrder->save();
    }

    #[On('supplierSelected')]
    public function supplierProducts(?int $id)
    {
        if (!$id) {
            return;
        }
        $supplier = Supplier::findOrFail($id);
        $currentSupplierId = $this->temporaryDeliveryOrder->supplier_id;

        if ($currentSupplierId !== $supplier->id) {
            $this->temporaryDeliveryOrder->supplier_id = $supplier->id;
            $this->temporaryDeliveryOrder->save();
            $this->supplier = $supplier;
            $this->supplierProducts = SupplierProduct::where('supplier_id', $supplier->id)->get();
            $this->cartProducts = [];
            $this->updateCartProducts($this->cartProducts);
        }
    }

    #[On('clientSelected')]
    public function clientSelected(?int $id)
    {
        if (!$id) {
            return;
        }
        $client = Client::findOrFail($id);
        $currentClientId = $this->temporaryDeliveryOrder->client_id;

        if ($currentClientId !== $client->id) {
            $this->temporaryDeliveryOrder->client_id = $client->id;
            $this->temporaryDeliveryOrder->save();
            // $this->cartProducts = [];
            // $this->updateCartProducts($this->cartProducts);

            if ($this->temporaryDeliveryOrder->client && $this->temporaryDeliveryOrder->supplier) {
                $supplierProductIds = collect($this->cartProducts)->pluck('supplier_product_id')->toArray();
                $this->cartSupplierProducts = SupplierProduct::whereIn('id', $supplierProductIds)->pluck('name', 'id')->toArray();

                $temporaryDeliveryOrder = $this->temporaryDeliveryOrder;
                $this->clientLastSupplierProducts = DeliveryOrderProduct::with('deliveryOrder')
                    ->whereHas('deliveryOrder', function ($query) use ($temporaryDeliveryOrder) {
                        $query->where('client_id', $temporaryDeliveryOrder->client->user_id)
                            ->where('supplier_id', $temporaryDeliveryOrder->supplier->user_id);
                    })
                    ->whereIn('supplier_product_id', $supplierProductIds)
                    ->orderByDesc('id') // assumes latest = highest id
                    ->get()
                    ->unique('supplier_product_id')->keyBy('supplier_product_id');
            }
        }
    }

    public function validateCartBeforeSubmit()
    {
        $rules = [];
        $messages = [];

        foreach ($this->cartProducts as $index => $item) {
            $rules["cartProducts.{$index}.quantity"] = 'required|integer|min:1';
            $messages["cartProducts.{$index}.quantity.required"] = __("Quantity is required.");
            $messages["cartProducts.{$index}.quantity.integer"] = __("Quantity must be an integer.");
            $messages["cartProducts.{$index}.quantity.min"] = __("Quantity must be at least 1.");
        }

        if (empty($this->cartProducts)) {
            $this->addError('cartProducts', __('Cart cannot be empty.'));
        }

        $this->validate($rules, $messages);
    }

    public function submitOrder()
    {
        $this->validateCartBeforeSubmit();

        //If the verification is successful and there are no errors, execute JS to actually submit the form.
         $this->js('document.getElementById("cartSubmitForm").submit();');
    }

    public function render()
    {
        return view('livewire.shopping');
    }
}
