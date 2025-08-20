<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.user')] class extends Component {
    public array $cartItems = [];
    public ?string $orderCode = null;

    // Properti untuk detail pembeli dengan aturan validasi
    #[Rule('required|string|max:255', message: 'Nama pembeli wajib diisi.')]
    public string $buyer_name = '';

    #[Rule('required|string|max:20', message: 'Nomor WhatsApp wajib diisi.')]
    public string $buyer_phone = '';

    // Properti dan aturan untuk alamat pengiriman
    #[Rule('required|string|min:10', message: 'Alamat pengiriman wajib diisi.')]
    public string $shipping_address = '';

    /**
     * Menggunakan indeks array ($itemIndex) untuk menambah kuantitas.
     */
    public function increaseQuantity(int $itemIndex): void
    {
        if (isset($this->cartItems[$itemIndex])) {
            $this->cartItems[$itemIndex]['quantity']++;
            $this->updateCartStorage();
        }
    }

    /**
     * Menggunakan indeks array ($itemIndex) untuk mengurangi kuantitas.
     */
    public function decreaseQuantity(int $itemIndex): void
    {
        if (isset($this->cartItems[$itemIndex])) {
            $this->cartItems[$itemIndex]['quantity']--;

            if ($this->cartItems[$itemIndex]['quantity'] <= 0) {
                unset($this->cartItems[$itemIndex]);
                $this->cartItems = array_values($this->cartItems);
            }
            
            $this->updateCartStorage();
        }
    }

    /**
     * Mengirim event untuk memperbarui keranjang di localStorage.
     */
    private function updateCartStorage(): void
    {
        $this->dispatch('cart-updated', items: $this->cartItems);
    }

    /**
     * Membuat pesanan dan item-itemnya di database dari data keranjang.
     */
    public function createOrder(): void
    {
        $this->validate();

        if (empty($this->cartItems)) {
            return;
        }

        $this->orderCode = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        DB::transaction(function () {
            $order = Order::create([
                'customer_id'      => Auth::id(),
                'order_code'       => $this->orderCode,
                'buyer_name'       => $this->buyer_name,
                'buyer_phone'      => $this->buyer_phone,
                'shipping_address' => $this->shipping_address,
                'order_date'       => now(),
                'status'           => 'pending',
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id'   => $order->order_id,
                    'product_id' => $item['id'],
                    'quantity'   => $item['quantity'],
                    'subtotal'   => $item['price'] * $item['quantity'],
                ]);
            }
        });

        $this->dispatch('order-created', code: $this->orderCode);
    }

    #[Computed]
    public function subtotal()
    {
        return collect($this->cartItems)->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    /**
     * PERBAIKAN: Menghapus biaya pengiriman. Total sekarang sama dengan subtotal.
     */
    #[Computed]
    public function total()
    {
        return $this->subtotal();
    }
}; ?>

<div x-data="{
    init() {
        const cartData = localStorage.getItem('cart');
        if (cartData) {
            $wire.set('cartItems', JSON.parse(cartData));
        }
    },
    clearCart() {
        localStorage.removeItem('cart');
        location.reload();
    }
}"
    @cart-updated.window="localStorage.setItem('cart', JSON.stringify(event.detail.items))"
    @order-created.window="
    (event) => {
        navigator.clipboard.writeText(event.detail.code);
        localStorage.removeItem('cart');
        alert('Pesanan berhasil dibuat! Kode pesanan ' + event.detail.code + ' telah disalin ke clipboard.');
    }
">
    <main class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 py-10">
        <h1 class="font-extrabold text-2xl mb-6">
            Keranjang Belanja
        </h1>

        @if ($orderCode)
            {{-- PESAN SUKSES --}}
            <section class="mb-8 p-6 bg-green-50 border border-green-300 rounded-lg text-center">
                <h2 class="text-xl font-bold text-green-800">Pesanan Berhasil Dibuat! ✅</h2>
                <h3 class="text-xl font-bold text-red-600">Simpan Kode Pesanan Ini dengan baik</h3>
                <p class="text-gray-700 mt-2">Silakan selesaikan pembayaran Anda.</p>
                <p class="mt-4 text-sm text-gray-600">Kode Pesanan Anda:</p>
                <div class="mt-2 inline-block bg-green-100 text-green-900 font-mono text-lg px-4 py-2 rounded">
                    {{ $orderCode }}
                </div>
                <p class="text-xs text-gray-500 mt-3">(Kode ini telah disalin ke clipboard Anda)</p>
            </section>
        @elseif (empty($cartItems))
            {{-- PESAN KERANJANG KOSONG --}}
            <section class="text-center py-16">
                <p class="text-gray-600 text-lg">Keranjang belanja Anda kosong.</p>
                <a href="{{ route('user.product') }}" wire:navigate
                    class="mt-4 inline-block bg-black text-white text-sm font-bold rounded-md px-6 py-3 hover:bg-gray-900">
                    Mulai Belanja
                </a>
            </section>
        @else
            {{-- TABEL KERANJANG & AKSI --}}
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-300 rounded-lg text-xs text-gray-900 table-fixed">
                    <thead class="border-b border-gray-300 bg-gray-50">
                        <tr>
                            <th class="py-3 px-3 text-left w-[40%]">Produk</th>
                            <th class="py-3 px-3 text-left w-[20%]">Harga</th>
                            <th class="py-3 px-3 text-center w-[15%]">Qty</th>
                            <th class="py-3 px-3 text-left w-[25%]">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cartItems as $index => $item)
                            <tr class="border-b border-gray-300" wire:key="cart-item-{{ $index }}">
                                <td class="py-4 px-3 flex items-center space-x-3">
                                    <img alt="{{ $item['name'] }}" class="w-10 h-10 rounded-full object-cover"
                                        height="40" src="{{ $item['imageUrl'] }}" width="40" />
                                    <span>{{ $item['name'] }}</span>
                                </td>
                                <td class="py-4 px-3">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                                <td class="py-4 px-3">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button wire:click="decreaseQuantity({{ $index }})" type="button"
                                            class="w-6 h-6 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center hover:bg-gray-300">-</button>
                                        <span
                                            class="font-semibold w-4 text-center">{{ $item['quantity'] }}</span>
                                        <button wire:click="increaseQuantity({{ $index }})" type="button"
                                            class="w-6 h-6 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center hover:bg-gray-300">+</button>
                                    </div>
                                </td>
                                <td class="py-4 px-3 font-extrabold text-sm text-gray-900">
                                    Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <section class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-12">

                {{-- DETAIL PEMBELI & FORMULIR PENGIRIMAN --}}
                <div>
                    <h2 class="font-semibold text-base mb-4">Detail Pengiriman</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="buyer_name" class="block text-sm font-medium text-gray-700">Nama
                                Penerima</label>
                            <input type="text" id="buyer_name" wire:model="buyer_name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm"
                                placeholder="Masukkan nama lengkap Anda">
                            @error('buyer_name')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="buyer_phone" class="block text-sm font-medium text-gray-700">No.
                                WhatsApp</label>
                            <input type="text" id="buyer_phone" wire:model="buyer_phone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm"
                                placeholder="Contoh: 081234567890">
                            @error('buyer_phone')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="shipping_address" class="block text-sm font-medium text-gray-700">Alamat
                                Pengiriman</label>
                            <textarea id="shipping_address" wire:model="shipping_address" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm"
                                placeholder="Masukkan alamat lengkap, termasuk nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, dan kode pos."></textarea>
                            @error('shipping_address')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- RINGKASAN PESANAN & AKSI --}}
                <div class="max-w-sm justify-self-end w-full">
                    <div class="p-4 border rounded-lg bg-gray-50 text-sm mb-6">
                        <p class="font-semibold text-gray-800">Metode Pembayaran: Transfer Bank</p>
                        <div class="mt-3 space-y-1">
                            <p><span class="font-medium">Bank:</span> BCA</p>
                            <p><span class="font-medium">Nomor Rekening:</span> 1234567890</p>
                            <p><span class="font-medium">Atas Nama:</span> PT Batu Alam Sejahtera</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">
                            Sertakan Kode Pesanan Anda di berita transfer untuk mempercepat proses verifikasi.
                        </p>
                    </div>

                    <div class="flex justify-between text-xs text-gray-600 mb-2">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>
                    {{-- PERBAIKAN: Menghapus tampilan biaya pengiriman --}}
                    <div class="flex justify-between text-sm text-gray-900 font-semibold mb-8 pt-2 border-t">
                        <span>Total</span>
                        <span>Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <button x-on:click="clearCart()"
                            class="bg-red-100 text-red-700 rounded-md px-4 py-2 font-extrabold text-xs hover:bg-red-200"
                            type="button">
                            Hapus Keranjang
                        </button>
                        <button wire:click="createOrder" wire:loading.attr="disabled"
                            class="bg-black rounded-md px-5 py-2 font-extrabold text-xs text-white hover:bg-gray-900 disabled:bg-gray-400"
                            type="button">
                            <span wire:loading.remove wire:target="createOrder">Buat Pesanan & Salin Kode</span>
                            <span wire:loading wire:target="createOrder">Memproses...</span>
                        </button>
                    </div>
                </div>
            </section>
        @endif
    </main>
</div>
