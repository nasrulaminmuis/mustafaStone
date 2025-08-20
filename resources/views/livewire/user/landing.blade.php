<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

new #[Layout('components.layouts.user')] class extends Component {
    /**
     * Holds the collection of featured products.
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public Collection $featuredProducts;

    /**
     * Holds the product for the promotion.
     * Can be null if there are no products.
     * @var \App\Models\Product|null
     */
    public ?Product $promotionProduct;

    /**
     * Runs when the component is initialized.
     * Fetches all necessary data from the database.
     */
    public function mount(): void
    {
        $this->getFeaturedProducts();
        $this->getPromotionProduct();
    }

    /**
     * Fetches featured products.
     * Logic: Gets the 3 best-selling products. If there are no sales,
     * it falls back to the 3 newest products.
     */
    public function getFeaturedProducts(): void
    {
        if (OrderItem::exists()) {
            $topProductIds = OrderItem::query()
                ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('product_id')
                ->orderByDesc('total_quantity')
                ->limit(3)
                ->pluck('product_id');

            if ($topProductIds->isEmpty()) {
                $this->featuredProducts = Product::with('images')->latest()->limit(3)->get();
                return;
            }

            // PERBAIKAN DI SINI: Ganti FIELD() dengan CASE statement
            // 1. Buat urutan CASE secara dinamis
            $orderCases = $topProductIds->map(function ($id, $index) {
                return "WHEN {$id} THEN " . ($index + 1);
            })->implode(' ');

            $orderClause = "CASE product_id {$orderCases} END";

            // 2. Gunakan klausa yang sudah dibuat di orderByRaw
            $this->featuredProducts = Product::with('images')
                ->whereIn('product_id', $topProductIds)
                ->orderByRaw($orderClause)
                ->get();
        } else {
            $this->featuredProducts = Product::with('images')->latest()->limit(3)->get();
        }
    }

    /**
     * Fetches the newest product for the promotion section.
     */
    public function getPromotionProduct(): void
    {
        $this->promotionProduct = Product::with('images')->latest()->first();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <main class="max-w-6xl mx-auto px-4 py-8">
        <section class="mt-10">
            <h2 class="font-bold text-gray-900 text-base mb-4">
                Produk Unggulan
            </h2>
            {{-- Loop through featured products from the database --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($featuredProducts as $product)
                    <div>
                        <img alt="{{ $product->name }}" class="rounded-md w-full object-cover" height="200"
                            src="{{ $product->images->first()->url ?? 'https://storage.googleapis.com/a1aa/image/0ee836f7-7240-44f6-5433-ccf8f7b1aae2.jpg' }}"
                            width="300" />
                        <h3 class="font-semibold text-sm mt-2 text-gray-900">
                            {{ $product->name }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{-- Use Str::limit to shorten the description --}}
                            {{ Illuminate\Support\Str::limit($product->description, 70) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-10 max-w-4xl">
            <h2 class="font-bold text-gray-900 text-base mb-2">
                Tentang Kami
            </h2>
            <p class="text-xs text-gray-900 leading-relaxed">
                Mustafa Stone adalah perusahaan yang menyediakan batu alam berkualitas tinggi
                untuk berbagai kebutuhan konstruksi dan dekorasi. Dengan pengalaman bertahun-tahun,
                kami berkomitmen untuk memberikan produk terbaik dan layanan yang memuaskan.
                Pelajari lebih lanjut tentang sejarah dan keunggulan kami.
            </p>
        </section>

        {{-- Only show the promotion section if a promotion product exists --}}
        @if ($promotionProduct)
            <section class="mt-10 max-w-4xl">
                <h2 class="font-bold text-gray-900 text-base mb-4">
                    Promosi
                </h2>
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                    <img alt="{{ $promotionProduct->name }}" class="rounded-md w-full sm:w-80 object-cover"
                        height="160"
                        src="{{ $promotionProduct->images->first()->url ?? 'https://storage.googleapis.com/a1aa/image/715b7d3a-797e-4803-9c97-bac6bbbe7b2a.jpg' }}"
                        width="320" />
                    <div class="flex flex-col justify-center space-y-1 text-xs text-gray-900 max-w-xs">
                        <span class="font-bold text-gray-900">
                            Diskon Spesial: {{ $promotionProduct->name }}
                        </span>
                        <span class="text-gray-500">
                            Nikmati diskon hingga 20% untuk semua produk batu alam. Penawaran terbatas!
                        </span>
                        <button class="mt-2 bg-black text-white text-xs font-semibold px-3 py-1 rounded-md w-max"
                            type="button">
                            Lihat Penawaran
                        </button>
                    </div>
                </div>
            </section>
        @endif
        <!-- Contact Us Section -->
        <section class="text-center bg-white p-8 rounded-lg shadow-md mt-8 ">
            <h2 class="font-semibold text-2xl mb-4 text-gray-800">
                Hubungi Kami
            </h2>
            <p class="mb-6 text-gray-600 max-w-lg mx-auto">
                Punya pertanyaan atau ingin konsultasi mengenai kebutuhan batu alam Anda? Tim kami siap membantu. Hubungi kami langsung melalui WhatsApp untuk respon yang lebih cepat.
            </p>
            <!-- WhatsApp Button -->
            <!-- Ganti dengan nomor WhatsApp Anda -->
            <a href="https://wa.me/6281234567890"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center justify-center bg-green-500 text-white font-semibold px-6 py-3 rounded-lg hover:bg-green-600 transition-colors shadow-sm">
                <i class="fab fa-whatsapp mr-2 text-xl"></i>
                Hubungi via WhatsApp
            </a>
        </section>
    </main>
    

    <footer class="bg-gray-50 mt-16 py-8 text-center text-gray-500 text-xs">
        <div class="max-w-6xl mx-auto px-4 space-y-4">
            <div class="flex justify-center space-x-4 text-gray-400 text-sm">
                <a aria-label="Instagram" class="hover:text-gray-600" href="#">
                    <i class="fab fa-instagram">
                    </i>
                </a>
                <a aria-label="Facebook" class="hover:text-gray-600" href="#">
                    <i class="fab fa-facebook-f">
                    </i>
                </a>
                <a aria-label="Twitter" class="hover:text-gray-600" href="#">
                    <i class="fab fa-twitter">
                    </i>
                </a>
            </div>
            <div class="text-gray-400 text-xs">
                Jl. Batu Alam Indah No. 1, Jakarta, Indonesia
            </div>
            <div class="text-gray-400 text-xs">
                Telepon: (021) 123-4567 | Email: info@mustafastone.com
            </div>
            <div class="pt-4 border-t border-gray-200 text-gray-400 text-xs">
                © 2024 Mustafa Stone. All rights reserved.
            </div>
        </div>
    </footer>
</div>