<?php

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule as ValidationRule; // 1. Import ValidationRule for unique checks

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;
    use WithFileUploads;

    // --- State for Product Modal and Editing ---
    public bool $showModal = false;
    public ?Product $editing;

    // --- State for Category Modal and Editing ---
    public bool $showCategoryModal = false;
    public ?Category $editingCategory;

    // --- Form Properties for Products ---
    #[Rule('required|string|min:3')]
    public string $name = '';

    #[Rule('required|string|max:1000')]
    public string $description = '';

    #[Rule('required|numeric|min:0')]
    public string $price = '';

    #[Rule('required|integer|min:0')]
    public string $stock_quantity = '';

    #[Rule('required|exists:categories,category_id')]
    public string $category_id = '';

    #[Rule('nullable|image|max:2048')]
    public $newImage;

    public ?string $existingImageUrl = null;

    // --- Form Properties for Categories ---
    public string $categoryName = '';


    /**
     * Initialize the component.
     */
    public function mount(): void
    {
        $this->editing = new Product();
        $this->editingCategory = new Category(); // Initialize editingCategory
    }

    // ===================================================
    // PRODUCT CRUD METHODS
    // ===================================================

    public function create(): void
    {
        $this->resetErrorBag();
        $this->editing = new Product();
        $this->name = '';
        $this->description = '';
        $this->price = '';
        $this->stock_quantity = '';
        $this->category_id = '';
        $this->newImage = null;
        $this->existingImageUrl = null;
        $this->showModal = true;
    }

    public function edit(Product $product): void
    {
        $this->resetErrorBag();
        $this->editing = $product;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->stock_quantity = $product->stock_quantity;
        $this->category_id = $product->category_id;
        $this->newImage = null;
        $this->existingImageUrl = $product->images->first()->url ?? null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|min:3',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,category_id',
            'newImage' => 'nullable|image|max:2048',
        ]);

        $productData = [
            'name' => $validated['name'],
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'category_id' => $validated['category_id'],
            'description' => $validated['description'],
        ];

        if ($this->editing->exists) {
            $this->editing->update($productData);
        } else {
            $this->editing = Product::create($productData);
        }

        if ($this->newImage) {
            $path = $this->newImage->store('product-images', 'public');
            $imageUrl = Storage::url($path);
            $this->editing->images()->updateOrCreate(
                ['product_id' => $this->editing->product_id],
                ['url' => $imageUrl]
            );
        }

        $this->showModal = false;
        session()->flash('success', 'Produk berhasil disimpan.');
    }

    public function delete(Product $product): void
    {
        if ($image = $product->images->first()) {
            $path = str_replace('/storage', 'public', $image->url);
            Storage::delete($path);
        }

        $product->delete();
        session()->flash('success', 'Produk berhasil dihapus.');
    }


    // ===================================================
    // CATEGORY CRUD METHODS
    // ===================================================

    public function createCategory(): void
    {
        $this->resetErrorBag();
        $this->editingCategory = new Category();
        $this->categoryName = '';
        $this->showCategoryModal = true;
    }

    public function editCategory(Category $category): void
    {
        $this->resetErrorBag();
        $this->editingCategory = $category;
        $this->categoryName = $category->name;
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        // Dynamically create the validation rule for uniqueness
        $rule = ValidationRule::unique('categories', 'name');
        if ($this->editingCategory->exists) {
            $rule->ignore($this->editingCategory->category_id, 'category_id');
        }

        $validated = $this->validate(['categoryName' => ['required', 'string', 'min:3', $rule]]);

        if ($this->editingCategory->exists) {
            $this->editingCategory->update(['name' => $validated['categoryName']]);
            session()->flash('success', 'Kategori berhasil diperbarui.');
        } else {
            Category::create(['name' => $validated['categoryName']]);
            session()->flash('success', 'Kategori baru berhasil ditambahkan.');
        }

        $this->showCategoryModal = false;
    }

    public function deleteCategory(Category $category): void
    {
        // Prevent deleting a category that has products
        if ($category->products()->count() > 0) {
            session()->flash('error', 'Kategori tidak dapat dihapus karena memiliki produk terkait.');
            return;
        }

        $category->delete();
        session()->flash('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Provide all necessary data to the view.
     */
    public function with(): array
    {
        return [
            'products' => Product::with(['category', 'images'])->latest()->paginate(10, ['*'], 'productPage'),
            'categories' => Category::all(),
            'paginatedCategories' => Category::withCount('products')->latest()->paginate(5, ['*'], 'categoryPage'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-col">
    <main class="flex-1 overflow-y-auto p-6 md:p-8">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-100 p-4 text-sm text-green-700" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-100 p-4 text-sm text-red-700" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{-- ================================================= --}}
        {{-- CATEGORY MANAGEMENT SECTION --}}
        {{-- ================================================= --}}
        <section id="category-management" class="mb-10">
            <header class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800">
                    Manajemen Kategori
                </h2>
            </header>

            <button wire:click="createCategory" class="mb-6 flex items-center gap-2 rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /> </svg>
                Tambah Kategori
            </button>

            {{-- Categories Table --}}
            <div class="overflow-x-auto rounded-lg bg-white shadow-md">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="border-b-2 border-gray-200 bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th class="px-6 py-3">Nama Kategori</th>
                            <th class="px-6 py-3">Jumlah Produk</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paginatedCategories as $category)
                            <tr class="border-b bg-white hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $category->name }}</td>
                                <td class="px-6 py-4">{{ $category->products_count }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-4">
                                        <button wire:click="editCategory({{ $category->category_id }})" class="text-blue-600 transition-colors hover:text-blue-800" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"> <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /> <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /> </svg>
                                        </button>
                                        <button wire:click="deleteCategory({{ $category->category_id }})" wire:confirm="Anda yakin ingin menghapus kategori '{{ $category->name }}'?" class="text-red-600 transition-colors hover:text-red-800" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"> <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" /> </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-gray-500">Tidak ada kategori yang ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $paginatedCategories->links() }}
            </div>
        </section>

        <hr class="my-10 border-gray-300">

        {{-- ================================================= --}}
        {{-- PRODUCT MANAGEMENT SECTION --}}
        {{-- ================================================= --}}
        <section id="product-management">
            <header class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800">
                    Manajemen Produk
                </h2>
            </header>

            <button wire:click="create" class="mb-6 flex items-center gap-2 rounded-md bg-[#4C22B9] px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#3a1a8a] focus:outline-none focus:ring-2 focus:ring-[#4C22B9] focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"> <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /> </svg>
                Tambah Produk
            </button>

            {{-- Products Table --}}
            <div class="overflow-x-auto rounded-lg bg-white shadow-md">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="border-b-2 border-gray-200 bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th class="px-6 py-3">Nama Produk</th>
                            <th class="px-6 py-3">Kategori</th>
                            <th class="px-6 py-3">Harga</th>
                            <th class="px-6 py-3">Stok</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-b bg-white hover:bg-gray-50">
                                <td class="flex items-center gap-4 px-6 py-4 font-medium text-gray-900">
                                    <img alt="Image of {{ $product->name }}" class="h-12 w-12 rounded-lg object-cover" src="{{ $product->images->first()->url ?? 'https://placehold.co/48x48/E2E8F0/4A5568?text=N/A' }}" />
                                    <span>{{ $product->name }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $product->category->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-800">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">{{ $product->stock_quantity }}</td>
                                <td class="px-6 py-4">
                                    @if ($product->stock_quantity > 20)
                                        <span class="inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">Tersedia</span>
                                    @elseif ($product->stock_quantity > 0)
                                        <span class="inline-block rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">Stok Sedikit</span>
                                    @else
                                        <span class="inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">Habis</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-4">
                                        <button wire:click="edit({{ $product->product_id }})" class="text-blue-600 transition-colors hover:text-blue-800" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"> <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /> <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /> </svg>
                                        </button>
                                        <button wire:click="delete({{ $product->product_id }})" wire:confirm="Anda yakin ingin menghapus produk '{{ $product->name }}'?" class="text-red-600 transition-colors hover:text-red-800" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"> <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" /> </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">Tidak ada produk yang ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </section>
    </main>

    {{-- ================================================= --}}
    {{-- MODALS --}}
    {{-- ================================================= --}}

    {{-- Create/Edit Category Modal --}}
    @if ($showCategoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60" x-data="{ show: @entangle('showCategoryModal') }" x-show="show" x-transition.opacity>
            <div class="w-full max-w-lg rounded-lg bg-white p-8 shadow-2xl" @click.away="show = false">
                <h3 class="mb-6 text-2xl font-semibold">{{ $editingCategory->exists ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</h3>
                <form wire:submit.prevent="saveCategory">
                    <div>
                        <label for="categoryName" class="mb-2 block text-sm font-medium text-gray-700">Nama Kategori</label>
                        <input type="text" wire:model="categoryName" id="categoryName" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('categoryName') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="mt-8 flex justify-end gap-4">
                        <button type="button" @click="show = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Batal</button>
                        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-slate-800 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-700">
                           <span wire:loading.remove wire:target="saveCategory">Simpan</span>
                           <span wire:loading wire:target="saveCategory">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif


    {{-- Create/Edit Product Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60" x-data="{ show: @entangle('showModal') }" x-show="show" x-transition.opacity>
            <div class="w-full max-w-2xl rounded-lg bg-white p-8 shadow-2xl" @click.away="show = false">
                <h3 class="mb-6 text-2xl font-semibold">{{ $editing->exists ? 'Edit Produk' : 'Tambah Produk Baru' }}</h3>
                <form wire:submit.prevent="save" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Nama Produk</label>
                            <input type="text" wire:model="name" id="name" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('name') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="price" class="mb-2 block text-sm font-medium text-gray-700">Harga</label>
                            <input type="number" wire:model="price" id="price" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: 50000">
                            @error('price') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="stock_quantity" class="mb-2 block text-sm font-medium text-gray-700">Stok</label>
                            <input type="number" wire:model="stock_quantity" id="stock_quantity" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: 100">
                            @error('stock_quantity') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="category_id" class="mb-2 block text-sm font-medium text-gray-700">Kategori</label>
                            <select wire:model="category_id" id="category_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Pilih Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->category_id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="newImage" class="mb-2 block text-sm font-medium text-gray-700">Gambar Produk</label>
                            <input type="file" wire:model="newImage" id="newImage" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                            <div wire:loading wire:target="newImage" class="mt-2 text-sm text-gray-500">Mengunggah...</div>
                            @error('newImage') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            <div class="mt-4">
                                @if ($newImage)
                                    <p class="text-sm font-medium text-gray-600">Preview Gambar Baru:</p>
                                    <img src="{{ $newImage->temporaryUrl() }}" class="mt-2 h-24 w-24 rounded-md object-cover">
                                @elseif ($existingImageUrl)
                                    <p class="text-sm font-medium text-gray-600">Gambar Saat Ini:</p>
                                    <img src="{{ $existingImageUrl }}" class="mt-2 h-24 w-24 rounded-md object-cover">
                                @endif
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label for="description" class="mb-2 block text-sm font-medium text-gray-700">Deskripsi</p>
                            <textarea wire:model="description" id="description" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('description') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-4">
                        <button type="button" @click="show = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Batal</button>
                        <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-[#4C22B9] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#3a1a8a]">
                           <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                           <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>