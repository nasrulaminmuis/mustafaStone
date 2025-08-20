<?php

use App\Models\Order;
use Illuminate\Support\Number; // <-- Ditambahkan untuk format mata uang
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.user')] class extends Component {
    use WithFileUploads;

    // ===================================================
    // Properti untuk Konfirmasi Pembayaran
    // ===================================================

    #[Rule('required|string|exists:orders,order_code', message: [
        'required' => 'Kode pesanan wajib diisi.',
        'exists' => 'Kode pesanan tidak ditemukan.',
    ])]
    public string $order_code = '';

    #[Rule('required|image|max:2048', message: [
        'required' => 'Bukti pembayaran wajib diunggah.',
        'image' => 'File harus berupa gambar.',
        'max' => 'Ukuran gambar maksimal 2MB.',
    ])]
    public $payment_proof;

    public ?string $successMessage = null;

    // ===================================================
    // Properti untuk Cek Status Pesanan
    // ===================================================

    #[Rule('required|string|exists:orders,order_code', as: 'kode pesanan', message: [
        'required' => 'Kode pesanan wajib diisi.',
        'exists' => 'Kode pesanan tidak ditemukan.',
    ])]
    public string $status_check_code = ''; // <-- Properti baru untuk input cek status

    public ?Order $foundOrder = null; // <-- Properti baru untuk menyimpan hasil pencarian

    /**
     * Menghapus hasil pencarian sebelumnya saat pengguna mengetik kode baru.
     */
    public function updatedStatusCheckCode(): void
    {
        $this->foundOrder = null;
    }

    /**
     * Konfirmasi pembayaran.
     */
    public function confirmPayment(): void
    {
        $this->validateOnly('order_code');
        $this->validateOnly('payment_proof');

        $order = Order::where('order_code', $this->order_code)->first();

        if (!$order || $order->payment_proof) {
            $this->addError('order_code', 'Pesanan ini sudah dikonfirmasi atau tidak valid.');
            return;
        }

        $path = $this->payment_proof->store('proofs', 'public');

        $order->update([
            'payment_proof' => $path,
            'status' => 'diproses',
        ]);

        $this->successMessage = 'Terima kasih! Konfirmasi pembayaran Anda untuk pesanan #' . $this->order_code . ' telah berhasil dikirim.';

        $this->reset(['order_code', 'payment_proof']);
    }

    /**
     * Fungsi baru untuk mencari dan menampilkan status pesanan.
     */
    public function checkStatus(): void
    {
        // Validasi input kode pesanan untuk cek status
        $validated = $this->validateOnly('status_check_code');

        // Cari pesanan berdasarkan kode dan simpan hasilnya
        $this->foundOrder = Order::where('order_code', $validated['status_check_code'])->first();
    }

    /**
     * Helper untuk mendapatkan kelas warna badge status.
     */
    public function getStatusClass(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'diproses' => 'bg-blue-100 text-blue-800',
            'dikirim' => 'bg-cyan-100 text-cyan-800',
            'selesai' => 'bg-green-100 text-green-800',
            'dibatalkan' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

        {{-- ============================================= --}}
        {{-- BAGIAN KONFIRMASI PEMBAYARAN --}}
        {{-- ============================================= --}}
        <section id="payment-confirmation">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2 text-center">Konfirmasi Pembayaran</h1>
            <p class="text-center text-gray-600 mb-8">
                Silakan isi form di bawah ini untuk mengonfirmasi pembayaran Anda.
            </p>

            @if ($successMessage)
                <div class="mb-6 p-4 bg-green-50 border border-green-300 rounded-lg text-center">
                    <p class="font-semibold text-green-800">{{ $successMessage }}</p>
                </div>
            @else
                <form wire:submit="confirmPayment" class="space-y-6 bg-white p-8 border border-gray-200 rounded-lg shadow-sm">
                    <div>
                        <label for="order_code" class="block font-semibold mb-1 text-gray-800">Kode Pesanan</label>
                        <input wire:model="order_code" id="order_code" type="text"
                            placeholder="Contoh: INV-20230101-ABCDEF"
                            class="w-full rounded-md border border-gray-300 px-4 py-3 placeholder-gray-400 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black" />
                        @error('order_code')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_proof" class="block font-semibold mb-1 text-gray-800">Unggah Bukti Pembayaran</label>
                        <div class="relative mt-1">
                            <input wire:model="payment_proof" id="payment_proof" type="file"
                                class="w-full rounded-md border border-gray-300 px-4 py-3 text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100 cursor-pointer focus:outline-none focus:ring-2 focus:ring-black" />
                        </div>
                        @error('payment_proof')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror

                        <div wire:loading wire:target="payment_proof" class="text-sm text-gray-500 mt-2">
                            Mengunggah...
                        </div>

                        @if ($payment_proof && !$errors->has('payment_proof'))
                            <div class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Pratinjau:</p>
                                <img src="{{ $payment_proof->temporaryUrl() }}" alt="Pratinjau Bukti Pembayaran"
                                    class="w-full max-w-xs h-auto rounded-md border border-gray-200">
                            </div>
                        @endif
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full bg-black text-white font-semibold py-3 rounded-md hover:bg-gray-900 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="confirmPayment">
                            Kirim Konfirmasi
                        </span>
                        <span wire:loading wire:target="confirmPayment">
                            Memproses...
                        </span>
                    </button>
                </form>
            @endif

            <p class="text-center text-gray-500 text-xs mt-6 max-w-md mx-auto">
                Setelah konfirmasi diterima, kami akan segera memverifikasi pembayaran dan memproses pesanan Anda dalam 1x24 jam.
            </p>
        </section>

        {{-- PEMISAH ANTAR BAGIAN --}}
        <div class="my-16 border-t border-gray-200"></div>

        {{-- ============================================= --}}
        {{-- BAGIAN BARU: CEK STATUS PESANAN               --}}
        {{-- ============================================= --}}
        <section id="status-check">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-2 text-center">Cek Status Pesanan</h2>
            <p class="text-center text-gray-600 mb-8">
                Masukkan kode pesanan Anda untuk melihat status terbaru.
            </p>

            <form wire:submit="checkStatus" class="space-y-6 bg-white p-8 border border-gray-200 rounded-lg shadow-sm">
                <div>
                    <label for="status_check_code" class="block font-semibold mb-1 text-gray-800">Kode Pesanan</label>
                    <input wire:model="status_check_code" id="status_check_code" type="text"
                        placeholder="Masukkan kode pesanan Anda di sini"
                        class="w-full rounded-md border border-gray-300 px-4 py-3 placeholder-gray-400 text-gray-700 focus:outline-none focus:ring-2 focus:ring-black" />
                    @error('status_check_code')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full bg-black text-white font-semibold py-3 rounded-md hover:bg-gray-900 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="checkStatus">
                        <i class="fas fa-search mr-2"></i> Cek Status
                    </span>
                    <span wire:loading wire:target="checkStatus">
                        Mencari...
                    </span>
                </button>
            </form>

            {{-- HASIL PENCARIAN STATUS --}}
            @if ($foundOrder)
                <div class="mt-8 bg-gray-50 p-6 border border-gray-200 rounded-lg">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Detail Pesanan</h3>
                    <div class="space-y-3 text-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Status:</span>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $this->getStatusClass($foundOrder->status) }}">
                                {{ ucfirst($foundOrder->status) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-200 pt-3">
                            <span class="font-semibold">Kode Pesanan:</span>
                            <span class="font-mono text-gray-900">{{ $foundOrder->order_code }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-200 pt-3">
                            <span class="font-semibold">Tanggal Pesanan:</span>
                            <span>{{ $foundOrder->created_at->format('d F Y, H:i') }}</span>
                        </div>
                         {{-- Uncomment baris ini jika model Order Anda memiliki properti total_amount --}}
                        {{-- <div class="flex justify-between items-center border-t border-gray-200 pt-3">
                            <span class="font-semibold">Total Pembayaran:</span>
                            <span class="font-bold text-lg text-gray-900">{{ Number::currency($foundOrder->total_amount, 'IDR') }}</span>
                        </div> --}}
                    </div>
                </div>
            @endif

        </section>

    </main>
</div>