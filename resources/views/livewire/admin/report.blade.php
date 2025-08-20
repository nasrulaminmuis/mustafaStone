<?php

use App\Models\Order;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Mengatur layout utama untuk halaman ini
new #[Layout('components.layouts.admin')]
class extends Component
{
    // Properti publik yang akan terikat dengan input di view
    public string $reportType = 'sales';
    public string $startDate = '';
    public string $endDate = '';

    /**
     * Method yang dijalankan saat komponen pertama kali di-mount.
     * Mengatur tanggal awal dan akhir default ke bulan berjalan.
     */
    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    /**
     * Method utama untuk menghasilkan dan mengunduh laporan PDF.
     */
    public function generateReport(): StreamedResponse
    {
        // Validasi input tanggal dari pengguna
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        // Query untuk mengambil data pesanan berdasarkan rentang tanggal
        $orders = Order::query()
            ->where('status', 'completed')
            ->whereDate('order_date', '>=', $this->startDate)
            ->whereDate('order_date', '<=', $this->endDate)
            ->with(['orderItems.product']) // Menghapus 'customer' dari eager loading
            ->withSum('orderItems as total_sales', 'subtotal')
            ->orderBy('order_date', 'asc')
            ->get();

        // Menyiapkan data yang akan dikirim ke view PDF
        $reportData = [
            'title' => 'Laporan Penjualan',
            'startDate' => Carbon::parse($this->startDate)->translatedFormat('d F Y'),
            'endDate' => Carbon::parse($this->endDate)->translatedFormat('d F Y'),
            'generatedDate' => Carbon::now()->translatedFormat('d F Y, H:i'),
            'orders' => $orders->map(fn($order) => [
                'id' => $order->order_id,
                'date' => Carbon::parse($order->order_date)->translatedFormat('d M Y'),
                // === PERUBAHAN UTAMA DI SINI ===
                // Menggunakan 'buyer_name' langsung dari objek $order
                'customer' => $order->buyer_name,
                'total' => (float) $order->total_sales,
                'items' => $order->orderItems->map(fn($item) => [
                    'name' => $item->product->name ?? 'Produk Dihapus',
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ])->all(),
            ])->all(),
            'totalRevenue' => $orders->sum('total_sales'),
            'totalOrders' => $orders->count(),
        ];

        // Membuat instance PDF dan memuat view dengan data laporan
        $pdf = Pdf::loadView('reports.sales-pdf', $reportData);
        $fileName = 'laporan-penjualan-' . $this->startDate . '-sampai-' . $this->endDate . '.pdf';

        // Mengirimkan response untuk mengunduh file PDF di browser
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $fileName);
    }
}; ?>


{{-- Bagian View / Tampilan HTML dari komponen --}}
<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <main class="flex-1 p-6">
            <header class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-7 text-gray-900">
                    Cetak Laporan
                </h2>
            </header>

            {{-- Form untuk memilih rentang tanggal dan jenis laporan --}}
            <form wire:submit="generateReport" class="flex flex-col gap-4 rounded-md bg-white p-4 shadow-sm sm:flex-row sm:items-center">
                
                {{-- Input Jenis Laporan --}}
                <div class="flex w-full flex-col text-xs text-gray-700 sm:w-auto">
                    <label for="jenis-laporan" class="mb-1 font-normal">Jenis Laporan</label>
                    <select id="jenis-laporan" wire:model="reportType" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:w-48">
                        <option value="sales">Laporan Penjualan</option>
                        {{-- Opsi laporan lain bisa ditambahkan di sini --}}
                    </select>
                </div>

                {{-- Input Tanggal Mulai --}}
                <div class="flex w-full flex-col text-xs text-gray-700 sm:w-auto">
                    <label for="dari-tanggal" class="mb-1 font-normal">Dari Tanggal</label>
                    <input type="date" id="dari-tanggal" wire:model="startDate" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:w-40" />
                    @error('startDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Input Tanggal Selesai --}}
                <div class="flex w-full flex-col text-xs text-gray-700 sm:w-auto">
                    <label for="sampai-tanggal" class="mb-1 font-normal">Sampai Tanggal</label>
                    <input type="date" id="sampai-tanggal" wire:model="endDate" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:w-40" />
                    @error('endDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Tombol Submit --}}
                <button type="submit" class="flex mt-4 sm:mt-0 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" style="min-width: 140px;">
                    {{-- State ketika sedang loading --}}
                    <div wire:loading wire:target="generateReport" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    
                    {{-- State normal --}}
                    <span wire:loading.remove wire:target="generateReport">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block -mt-1 mr-1" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Unduh Laporan
                    </span>
                    
                    {{-- Teks saat loading --}}
                    <span wire:loading wire:target="generateReport">Membuat...</span>
                </button>
            </form>
        </main>
    </div>
</div>