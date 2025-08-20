<?php

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Menggunakan layout admin untuk komponen ini
new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination, WithFileUploads;

    // Properti untuk state UI (filter dan sorting) yang tersinkronisasi dengan URL
    #[Url]
    public string $search = '';
    #[Url]
    public string $statusFilter = '';
    #[Url]
    public ?string $dateFilter = null;
    public ?int $expandedOrderId = null;

    // Properti boolean untuk kontrol modal murni Livewire
    public bool $showFormModal = false;
    public bool $showViewModal = false;
    public bool $showDeleteModal = false;

    // Properti untuk state form dan model yang sedang dioperasikan
    public array $state = [];
    public ?Order $editingOrder = null;
    public ?Order $viewingOrder = null;
    public ?int $deletingOrderId = null;
    public $payment_proof = null;

    // Koleksi data untuk dropdown, dimuat sekali saat komponen di-mount
    public Collection $customers;
    public Collection $products;

    /**
     * Method yang dijalankan saat komponen pertama kali di-load.
     */
    public function mount(): void
    {
        $this->customers = Customer::all(['customer_id', 'first_name', 'last_name']);
        $this->products = Product::all(['product_id', 'name', 'price']);
        $this->resetForm();
    }

    // --- Computed Properties ---

    #[Computed]
    public function statuses(): Collection
    {
        return collect(['pending', 'processing', 'completed', 'cancelled']);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->editingOrder !== null;
    }

    // --- Aturan Validasi ---

    protected function rules(): array
    {
        $orderId = $this->editingOrder?->order_id;
        return [
            'state.customer_id' => 'nullable|exists:customers,customer_id',
            'state.order_code' => ['required', 'string', Rule::unique('orders', 'order_code')->ignore($orderId, 'order_id')],
            'state.buyer_name' => 'required|string|max:255',
            'state.buyer_phone' => 'required|string|max:20',
            'state.shipping_address' => 'required|string',
            'state.order_date' => 'required|date',
            'state.status' => ['required', Rule::in($this->statuses())],
            'state.items' => 'required|array|min:1',
            'state.items.*.product_id' => 'required|exists:products,product_id',
            'state.items.*.quantity' => 'required|integer|min:1',
            'payment_proof' => 'nullable|image|max:2048',
        ];
    }

    // --- Manajemen Form dan Item ---

    public function updated($name): void
    {
        // Regex to match changes in product_id or quantity within the items array
        if (preg_match('/state\.items\.(\d+)\.(product_id|quantity)/', $name, $matches)) {
            $this->updateSubtotal($matches[1]);
        }
    }

    private function resetForm(): void
    {
        $this->state = [
            'customer_id' => '',
            'order_code' => 'ORD-' . strtoupper(Str::random(8)),
            'buyer_name' => '',
            'buyer_phone' => '',
            'shipping_address' => '',
            'order_date' => now()->format('Y-m-d\TH:i'),
            'status' => 'pending',
            'payment_proof_path' => null,
            'items' => [],
        ];
        $this->editingOrder = null;
        $this->payment_proof = null;
        $this->resetErrorBag();
    }

    public function addOrderItem(): void
    {
        $this->state['items'][] = ['product_id' => '', 'quantity' => 1, 'subtotal' => 0];
    }

    public function removeOrderItem(int $index): void
    {
        unset($this->state['items'][$index]);
        $this->state['items'] = array_values($this->state['items']);
    }

    public function updateSubtotal(int $index): void
    {
        $item = $this->state['items'][$index] ?? null;
        if (!$item || empty($item['product_id'])) {
            $this->state['items'][$index]['subtotal'] = 0;
            return;
        }
        
        $product = $this->products->find($item['product_id']);
        $quantity = max(1, (int)($item['quantity'] ?? 1));
        $this->state['items'][$index]['quantity'] = $quantity;
        $this->state['items'][$index]['subtotal'] = $product ? $product->price * $quantity : 0;
    }

    // --- Operasi CRUD ---

    public function create(): void
    {
        $this->resetForm();
        $this->addOrderItem();
        $this->showFormModal = true;
    }

    public function edit(Order $order): void
    {
        $this->resetForm();
        $this->editingOrder = $order;
        $this->state = $order->only(['customer_id', 'order_code', 'buyer_name', 'buyer_phone', 'shipping_address', 'status']);
        $this->state['order_date'] = $order->order_date->format('Y-m-d\TH:i');
        $this->state['payment_proof_path'] = $order->payment_proof;
        $this->state['items'] = $order->orderItems->map(fn($item) => $item->only(['product_id', 'quantity', 'subtotal']))->toArray();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validatedData = $this->validate();
        $orderData = $validatedData['state'];

        if ($this->payment_proof) {
            if ($this->isEditing() && $this->editingOrder->payment_proof) {
                Storage::disk('public')->delete($this->editingOrder->payment_proof);
            }
            $orderData['payment_proof'] = $this->payment_proof->store('payment-proofs', 'public');
        }

        $order = $this->isEditing()
            ? tap($this->editingOrder)->update($orderData)
            : Order::create($orderData);

        $order->orderItems()->delete();
        $itemsToCreate = collect($orderData['items'])->map(function ($item) {
            $product = $this->products->find($item['product_id']);
            return [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'subtotal' => $product ? $product->price * $item['quantity'] : 0,
            ];
        });
        $order->orderItems()->createMany($itemsToCreate);

        $this->closeFormModal();
        session()->flash('message', 'Pesanan berhasil ' . ($this->isEditing() ? 'diperbarui!' : 'ditambahkan!'));
        $this->resetPage();
    }

    public function view(Order $order): void
    {
        $this->viewingOrder = $order->load(['customer', 'orderItems.product']);
        $this->showViewModal = true;
    }

    public function confirmDelete(int $orderId): void
    {
        $this->deletingOrderId = $orderId;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $order = Order::with('orderItems')->findOrFail($this->deletingOrderId);

        if ($order->payment_proof) {
            Storage::disk('public')->delete($order->payment_proof);
        }

        $order->orderItems()->delete();
        $order->delete();

        $this->closeDeleteModal();
        session()->flash('message', 'Pesanan berhasil dihapus!');
        $this->resetPage();
    }
    
    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingOrder = null;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingOrderId = null;
    }

    // --- Helper UI ---

    public function toggleDetails(int $orderId): void
    {
        $this->expandedOrderId = $this->expandedOrderId === $orderId ? null : $orderId;
    }

    // --- Penyedia Data untuk View ---

    public function with(): array
    {
        $orders = Order::query()
            ->with(['customer', 'orderItems.product'])
            ->withSum('orderItems', 'subtotal')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_code', 'like', "%{$search}%")
                      ->orWhere('buyer_name', 'like', "%{$search}%");
                });
            })
            ->when($this->statusFilter, fn($query, $status) => $query->where('status', $status))
            ->when($this->dateFilter, fn($query, $date) => $query->whereDate('order_date', $date))
            ->latest('order_date')
            ->paginate(10);

        return ['orders' => $orders];
    }
}; ?>


<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <main class="flex-1 p-6">
        <h2 class="mb-6 text-xl font-bold text-[#0f172a]">Pengelolaan Pemesanan</h2>

        {{-- Flash Message --}}
        @if (session()->has('message'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition>
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <section class="max-w-full rounded-lg bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-[#0f172a]">Daftar Pesanan</h3>

            {{-- Filter section --}}
            <div class="mb-4 flex flex-col space-y-3 sm:flex-row sm:items-center sm:space-x-3 sm:space-y-0">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari kode pesanan atau nama pembeli..." class="w-full rounded border border-gray-300 py-2 pl-10 pr-3 text-sm text-[#475569] placeholder:text-gray-400 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
                </div>
                <select wire:model.live="statusFilter" class="rounded border border-gray-300 px-3 py-2 text-sm text-[#475569] focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600">
                    <option value="">Semua Status</option>
                    @foreach ($this->statuses as $status)
                        <option value="{{ $status }}">{{ Str::title($status) }}</option>
                    @endforeach
                </select>
                <input type="date" wire:model.live="dateFilter" class="rounded border border-gray-300 px-3 py-2 text-sm text-[#475569] focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
                <button wire:click="create" class="ml-auto inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-1 sm:ml-0" type="button">
                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Pesanan
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-100 text-left text-sm text-[#475569]">
                    <thead class="border-b border-gray-200 bg-[#f9fafb] text-xs font-bold uppercase text-[#475569]">
                        <tr>
                            <th scope="col" class="w-24 px-4 py-3 text-center">Detail</th>
                            <th scope="col" class="border-l border-r border-gray-200 px-4 py-3">Kode Pesanan</th>
                            <th scope="col" class="border-r border-gray-200 px-4 py-3">Pembeli</th>
                            <th scope="col" class="border-r border-gray-200 px-4 py-3">Tanggal</th>
                            <th scope="col" class="border-r border-gray-200 px-4 py-3">Total</th>
                            <th scope="col" class="border-r border-gray-200 px-4 py-3">Status</th>
                            <th scope="col" class="border-r border-gray-200 px-4 py-3">Bukti</th>
                            <th scope="col" class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    @forelse ($orders as $order)
                        <tbody class="border-b border-gray-100" wire:key="order-{{ $order->order_id }}">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleDetails({{ $order->order_id }})" class="rounded bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-100">
                                        {{ $expandedOrderId === $order->order_id ? 'Tutup' : 'Detail' }}
                                    </button>
                                </td>
                                <td class="border-l border-r border-gray-100 px-4 py-3 font-semibold">{{ $order->order_code }}</td>
                                <td class="border-r border-gray-100 px-4 py-3">{{ $order->buyer_name }}</td>
                                <td class="border-r border-gray-100 px-4 py-3">{{ $order->order_date->format('d M Y') }}</td>
                                <td class="border-r border-gray-100 px-4 py-3">Rp {{ number_format($order->order_items_sum_subtotal, 0, ',', '.') }}</td>
                                <td class="border-r border-gray-100 px-4 py-3">
                                    @php
                                        $statusClass = match (strtolower($order->status)) {
                                            'completed' => 'bg-green-100 text-green-800',
                                            'processing' => 'bg-yellow-100 text-yellow-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-200 text-gray-800',
                                        };
                                    @endphp
                                    <span class="select-none rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ Str::title($order->status) }}</span>
                                </td>
                                <td class="border-r border-gray-100 px-4 py-3">
                                    @if ($order->payment_proof)
                                        <a href="{{ asset('storage/' . $order->payment_proof) }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:underline">
                                            Lihat
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="flex items-center space-x-3 px-4 py-3 text-sm">
                                    <button wire:click="view({{ $order->order_id }})" class="text-blue-600 hover:text-blue-800" title="Lihat">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </button>
                                    <button wire:click="edit({{ $order->order_id }})" class="text-orange-500 hover:text-orange-700" title="Edit">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $order->order_id }})" class="text-red-500 hover:text-red-700" title="Hapus">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.067-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </td>
                            </tr>

                            {{-- Expanded Details Row --}}
                            @if ($expandedOrderId === $order->order_id)
                                <tr>
                                    <td colspan="8" class="bg-gray-50 p-4">
                                        <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                                            <div>
                                                <h4 class="mb-2 text-xs font-bold uppercase text-gray-600">Produk Dibeli</h4>
                                                <ul class="space-y-1 text-sm text-gray-800">
                                                    @foreach ($order->orderItems as $item)
                                                        <li><span class="font-semibold">{{ $item->quantity }}x</span> {{ $item->product->name ?? 'Produk dihapus' }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <div>
                                                <h4 class="mb-2 text-xs font-bold uppercase text-gray-600">Kontak Pembeli</h4>
                                                <p class="text-sm text-gray-800">
                                                    <strong>Nama:</strong> {{ $order->buyer_name }}<br>
                                                    <strong>No. WA:</strong> {{ $order->buyer_phone }}
                                                </p>
                                            </div>
                                            <div>
                                                <h4 class="mb-2 text-xs font-bold uppercase text-gray-600">Alamat Pengiriman</h4>
                                                <p class="whitespace-pre-line text-sm text-gray-800">
                                                    {{ $order->shipping_address ?? 'Tidak ada alamat' }}
                                                </p>
                                            </div>
                                            <div>
                                                <h4 class="mb-2 text-xs font-bold uppercase text-gray-600">Bukti Pembayaran</h4>
                                                @if ($order->payment_proof)
                                                    <a href="{{ asset('storage/' . $order->payment_proof) }}" target="_blank">
                                                        <img src="{{ asset('storage/' . $order->payment_proof) }}" alt="Bukti Pembayaran" class="h-auto w-full rounded-md border border-gray-200 object-cover">
                                                    </a>
                                                @else
                                                    <p class="italic text-sm text-gray-500">Belum diunggah.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="8" class="py-4 text-center text-gray-500">
                                    Tidak ada pesanan yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </section>
    </main>

    {{-- ================================================================= --}}
    {{-- ========================= MODALS SECTION ======================== --}}
    {{-- ================================================================= --}}

    {{-- Form Modal (Create/Edit) --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/75 p-4 pt-10" x-data="{ show: @entangle('showFormModal') }" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-4xl rounded-lg bg-white shadow-xl" @click.outside="show = false">
                <form wire:submit.prevent="save" class="flex h-full flex-col">
                    <div class="border-b px-6 py-4">
                        <h3 class="text-lg font-semibold">{{ $this->isEditing ? 'Edit Pesanan' : 'Tambah Pesanan Baru' }}</h3>
                    </div>
                    <div class="flex-1 space-y-6 overflow-y-auto p-6">
                        {{-- Form Fields --}}
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="order_code" class="mb-1 block text-sm font-medium text-gray-700">Kode Pesanan</label>
                                <input type="text" id="order_code" wire:model.defer="state.order_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('state.order_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="order_date" class="mb-1 block text-sm font-medium text-gray-700">Tanggal Pesanan</label>
                                <input type="datetime-local" id="order_date" wire:model.defer="state.order_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('state.order_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="buyer_name" class="mb-1 block text-sm font-medium text-gray-700">Nama Pembeli</label>
                                <input type="text" id="buyer_name" wire:model.defer="state.buyer_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('state.buyer_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="buyer_phone" class="mb-1 block text-sm font-medium text-gray-700">No. WA Pembeli</label>
                                <input type="tel" id="buyer_phone" wire:model.defer="state.buyer_phone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('state.buyer_phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="shipping_address" class="mb-1 block text-sm font-medium text-gray-700">Alamat Pengiriman</label>
                                <textarea id="shipping_address" wire:model.defer="state.shipping_address" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                @error('state.shipping_address') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                                <select id="status" wire:model.defer="state.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($this->statuses as $status)
                                        <option value="{{ $status }}">{{ Str::title($status) }}</option>
                                    @endforeach
                                </select>
                                @error('state.status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="payment_proof" class="mb-1 block text-sm font-medium text-gray-700">Bukti Pembayaran</label>
                                <input type="file" id="payment_proof" wire:model="payment_proof" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                                @error('payment_proof') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                @if ($payment_proof)
                                    <img src="{{ $payment_proof->temporaryUrl() }}" class="mt-2 h-20 w-20 rounded object-cover">
                                @elseif($state['payment_proof_path'])
                                    <img src="{{ asset('storage/' . $state['payment_proof_path']) }}" class="mt-2 h-20 w-20 rounded object-cover">
                                @endif
                            </div>
                        </div>

                        {{-- Order Items --}}
                        <div class="pt-4 border-t">
                            <h4 class="text-md mb-2 font-semibold">Item Pesanan</h4>
                            @error('state.items') <div class="mb-2 rounded border border-red-200 bg-red-50 p-2 text-sm text-red-700">{{ $message }}</div> @enderror
                            <div class="space-y-3">
                                @foreach ($state['items'] as $index => $item)
                                    <div class="flex items-center space-x-3 rounded border p-3" wire:key="item-{{ $index }}">
                                        <div class="flex-1">
                                            <select wire:model.live="state.items.{{ $index }}.product_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">Pilih Produk</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->product_id }}">{{ $product->name }} (Rp {{ number_format($product->price, 0, ',', '.') }})</option>
                                                @endforeach
                                            </select>
                                            @error('state.items.'.$index.'.product_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="w-24">
                                            <input type="number" wire:model.live="state.items.{{ $index }}.quantity" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @error('state.items.'.$index.'.quantity') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="w-32 text-right font-medium">
                                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                        </div>
                                        <button type="button" wire:click="removeOrderItem({{ $index }})" class="text-gray-400 hover:text-red-500">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" wire:click="addOrderItem" class="mt-4 inline-flex items-center rounded border border-dashed border-gray-400 px-3 py-1.5 text-sm font-medium text-gray-600 hover:border-gray-600 hover:text-gray-800">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Tambah Item
                            </button>
                        </div>

                    </div>
                    <div class="flex items-center justify-end space-x-4 border-t bg-gray-50 px-6 py-3">
                        <button type="button" wire:click="closeFormModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Batal</button>
                        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- View Modal --}}
    @if ($showViewModal && $viewingOrder)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/75 p-4 pt-10" x-data="{ show: @entangle('showViewModal') }" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl" @click.outside="show = false">
                <div class="border-b px-6 py-4">
                    <h3 class="text-lg font-semibold">Detail Pesanan: {{ $viewingOrder->order_code }}</h3>
                </div>
                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div class="font-semibold text-gray-600">Nama Pembeli:</div>
                        <div class="col-span-2">{{ $viewingOrder->buyer_name }}</div>

                        <div class="font-semibold text-gray-600">No. WA:</div>
                        <div class="col-span-2">{{ $viewingOrder->buyer_phone }}</div>

                        <div class="font-semibold text-gray-600">Tanggal Pesan:</div>
                        <div class="col-span-2">{{ $viewingOrder->order_date->format('d F Y, H:i') }}</div>

                        <div class="font-semibold text-gray-600">Status:</div>
                        <div class="col-span-2">{{ Str::title($viewingOrder->status) }}</div>

                        <div class="font-semibold text-gray-600">Alamat Pengiriman:</div>
                        <div class="col-span-2 whitespace-pre-line">{{ $viewingOrder->shipping_address }}</div>
                    </div>
                    <div class="pt-4 border-t">
                        <h4 class="mb-2 font-semibold">Item yang Dipesan</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-2">Produk</th>
                                    <th class="py-2 text-center">Jumlah</th>
                                    <th class="py-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($viewingOrder->orderItems as $item)
                                    <tr class="border-b">
                                        <td class="py-2">{{ $item->product->name ?? 'N/A' }}</td>
                                        <td class="py-2 text-center">{{ $item->quantity }}</td>
                                        <td class="py-2 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td colspan="2" class="py-2 text-right">Total</td>
                                    <td class="py-2 text-right">Rp {{ number_format($viewingOrder->orderItems->sum('subtotal'), 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if ($viewingOrder->payment_proof)
                        <div class="pt-4 border-t">
                            <h4 class="mb-2 font-semibold">Bukti Pembayaran</h4>
                             <a href="{{ asset('storage/' . $viewingOrder->payment_proof) }}" target="_blank">
                                <img src="{{ asset('storage/' . $viewingOrder->payment_proof) }}" alt="Bukti Pembayaran" class="max-h-80 w-auto rounded border">
                            </a>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end border-t bg-gray-50 px-6 py-3">
                    <button type="button" wire:click="closeViewModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75" x-data="{ show: @entangle('showDeleteModal') }" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="show = false">
                <div class="flex items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </div>
                    <div class="ml-4 text-left">
                        <h3 class="text-lg font-medium text-gray-900">Hapus Pesanan</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus pesanan ini? Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button wire:click="delete" type="button" class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                        <span wire:loading.remove wire:target="delete">Hapus</span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                    <button wire:click="closeDeleteModal" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
