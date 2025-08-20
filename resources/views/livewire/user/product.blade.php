<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Product;

new #[Layout('components.layouts.user')] class extends Component {
    /**
     * Use the WithPagination trait to enable pagination functionality.
     */
    use WithPagination;

    /**
     * Pass the paginated products data to the view.
     *
     * @return array
     */
    public function with(): array
    {
        return [
            // Fetch products, eager-load the 'images' relationship to prevent N+1 queries,
            // order by the newest, and paginate the results, showing 10 per page.
            'products' => Product::with('images')->latest()->paginate(10),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        <h1 class="font-extrabold text-2xl leading-7 mb-4 text-black">
            Katalog Produk
        </h1>

        <section aria-label="Produk Batu Alam" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-x-6 gap-y-6">
            {{-- Loop through the paginated products from the database --}}
            @foreach ($products as $product)
                {{-- Each product is now a link to its detail page --}}
                <a href="{{ route('user.productdetail', $product) }}" wire:navigate>
                    <article class="max-w-[140px] group">
                        {{-- Use the product's first image, with a fallback if none exists --}}
                        <img alt="{{ $product->name }}" class="w-full h-[140px] rounded-md object-cover"
                            src="{{ $product->images->first()->url ?? 'https://storage.googleapis.com/a1aa/image/980d1fd1-c39a-4d4e-7b10-ec7a769220fb.jpg' }}" />
                        <h2 class="font-semibold text-sm leading-5 mt-2 text-black truncate group-hover:underline">
                            {{ $product->name }}
                        </h2>
                        <p class="text-xs leading-5 text-gray-500 mt-1">
                            {{-- Limit the description to 50 characters to keep the layout clean --}}
                            {{ Illuminate\Support\Str::limit($product->description, 50) }}
                        </p>
                    </article>
                </a>
            @endforeach
        </section>

        {{-- Render the Livewire pagination links --}}
        <nav aria-label="Pagination" class="mt-16">
            {{ $products->links() }}
        </nav>
    </main>
</div>