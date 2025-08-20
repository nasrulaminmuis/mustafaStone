<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use Carbon\Carbon;

new #[Layout('components.layouts.admin')] class extends Component {
    public function with(): array
    {
        // Ambil data untuk card statistik
        $totalRevenue = OrderItem::sum('subtotal');
        $totalSales = Order::count();

        return [
            'totalRevenue' => $totalRevenue,
            'totalSales' => $totalSales,
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
  <main class="flex-1 p-6">
    <header class="mb-6 flex items-center justify-between">
      <h2 class="text-xl font-extrabold text-gray-900">Dashboard</h2>
    </header>

    <section class="mb-6 flex flex-col gap-4 sm:flex-row">
      <div class="flex-1 rounded-lg bg-white p-5 flex items-center gap-4 shadow-sm">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-200 text-blue-600">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500">Total Pendapatan</p>
          {{-- Menampilkan data pendapatan dari database --}}
          <p class="text-lg font-extrabold text-gray-900">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
      </div>
      <div class="flex-1 rounded-lg bg-white p-5 flex items-center gap-4 shadow-sm">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-200 text-green-600">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500">Total Penjualan</p>
          {{-- Menampilkan data total penjualan dari database --}}
          <p class="text-lg font-extrabold text-gray-900">{{ number_format($totalSales, 0, ',', '.') }}</p>
        </div>
      </div>
    </section>
  </main>
</div>