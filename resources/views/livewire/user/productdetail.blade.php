<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

new #[Layout('components.layouts.user')] class extends Component {
    public Product $product;
    public Collection $relatedProducts;

    public function mount(Product $product): void
    {
        $this->product = $product->load('images', 'category');
        $this->relatedProducts = Product::with('images')
            ->where('category_id', $this->product->category_id)
            ->where('product_id', '!=', $this->product->product_id)
            ->take(3)
            ->get();
    }
}; ?>

{{-- The addToCart function is now defined directly inside x-data --}}
<main x-data="{
    addToCart(id, name, price, description, imageUrl) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        let existingProduct = cart.find(item => item.id === id);

        if (existingProduct) {
            existingProduct.quantity++;
        } else {
            cart.push({ id, name, price, description, imageUrl, quantity: 1 });
        }

        localStorage.setItem('cart', JSON.stringify(cart));
        alert(name + ' telah ditambahkan ke keranjang!');
    }
}" class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <nav aria-label="Breadcrumb" class="text-sm text-gray-600 mb-4 select-none">
        <ol class="list-reset flex space-x-1">
            <li>
                <a href="{{ route('user.product') }}" class="hover:underline" wire:navigate>Produk</a>
            </li>
            <li>
                <span class="mx-2 select-none">/</span>
            </li>
            <li class="font-semibold text-gray-900">
                {{ $product->name }}
            </li>
        </ol>
    </nav>

    <!-- Change: Image is wrapped in a div for better centering and size control -->
    <!-- The image height is now constrained and responsive for different screen sizes -->
    <div class="flex justify-center mb-8">
        <img alt="{{ $product->name }}"
            class="rounded-lg object-contain max-h-80 sm:max-h-96 md:max-h-[500px] w-auto"
            src="{{ $product->images->first()->url ?? 'https://storage.googleapis.com/a1aa/image/980d1fd1-c39a-4d4e-7b10-ec7a769220fb.jpg' }}" />
    </div>

    <section>
        <h1 class="font-bold text-base mb-2 select-text">
            {{ $product->name }}
        </h1>
        <p class="text-xs text-gray-800 mb-6 leading-relaxed select-text">
            {{ $product->description }}
        </p>
    </section>

    <section class="mb-8">
        <h2 class="font-semibold text-sm mb-4 select-text">
            Spesifikasi
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-12 gap-y-6 text-xs text-gray-600 select-text">
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-400 mb-1">
                    Kategori
                </p>
                <p class="text-gray-700">
                    {{ $product->category->name ?? 'Tidak ada kategori' }}
                </p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-400 mb-1">
                    Stok Tersedia
                </p>
                <p class="text-gray-700">
                    {{ $product->stock_quantity }}
                </p>
            </div>
        </div>
    </section>

    <section class="mb-10 max-w-xs">
        <h3 class="font-semibold text-sm mb-2 select-text">
            Harga
        </h3>
        <div class="text-xl font-bold text-gray-900">
            Rp {{ number_format($product->price, 0, ',', '.') }}
        </div>
        <p class="text-[9px] text-gray-400 mt-1 select-text">
            Harga per meter persegi.
        </p>
        
        {{-- This @click call will now work correctly --}}
        <button
            @click="addToCart(
                {{ $product->product_id }},
                {{ json_encode($product->name) }},
                {{ $product->price }},
                {{ json_encode($product->description) }},
                {{ json_encode($product->images->first()->url ?? 'default-image-url.jpg') }}
            )"
            class="mt-4 bg-black text-white text-[10px] font-bold rounded-md px-4 py-2 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-700"
            type="button">
            Tambah ke Keranjang
        </button>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section>
            <h4 class="font-semibold text-sm mb-4 select-text">
                Produk Terkait
            </h4>
            <div class="flex space-x-4">
                @foreach ($relatedProducts as $related)
                    <a href="{{ route('user.productdetail', $related) }}" wire:navigate>
                        <article class="w-24 flex-shrink-0 group">
                            <img alt="{{ $related->name }}" class="rounded-md mb-2 object-cover w-24 h-24"
                                height="96"
                                src="{{ $related->images->first()->url ?? 'https://storage.googleapis.com/a1aa/image/bf0896b2-b674-4314-b691-480c13e18449.jpg' }}"
                                width="96" />
                            <h5 class="font-semibold text-[11px] select-text group-hover:underline">
                                {{ $related->name }}
                            </h5>
                            <p class="text-[9px] text-gray-600 select-text">
                                Rp {{ number_format($related->price, 0, ',', '.') }}
                            </p>
                        </article>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</main>
