<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.user')] class extends Component {
   // Assuming Font Awesome is loaded in your user layout for the icons to work.
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl bg-gray-50">
    <main class="flex-grow max-w-4xl mx-auto px-6 py-10">
        <!-- Header Section -->
        <div class="text-center mb-10">
            <h1 class="font-playfair font-bold text-3xl md:text-4xl mb-4 text-gray-800">
                Tentang Mustafa Stone
            </h1>
            <!-- Logo Image -->
            <img src="{{ asset('images/logo.webp') }}" alt="Logo Mustafa Stone" class="mx-auto h-24 w-24 md:h-32 md:w-32 object-contain"/>
            <p class="mt-4 text-gray-600 max-w-2xl mx-auto">
                Penyedia batu alam terkemuka di Yogyakarta selama lebih dari 20 tahun.
            </p>
        </div>

        <p class="mb-8 text-base leading-relaxed text-gray-700">
            Mustafa Stone telah menjadi penyedia batu alam terkemuka di Yogyakarta selama lebih dari 20 tahun. Komitmen kami terhadap kualitas dan kepuasan pelanggan telah menjadikan kami pilihan utama bagi pemilik rumah, arsitek, dan desainer. Kami mengambil batu kami dari tambang terbaik di seluruh dunia, memastikan bahwa setiap bagian memenuhi standar ketat kami. Misi kami adalah menyediakan produk batu alam berkualitas tinggi dan layanan luar biasa kepada pelanggan kami, membantu mereka menciptakan ruang yang indah dan tahan lama.
        </p>

        <!-- History, Mission, and Team Sections -->
        <div class="grid md:grid-cols-3 gap-8 mb-12 text-center">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h2 class="font-semibold text-lg mb-2 text-gray-900">Sejarah Kami</h2>
                <p class="text-sm leading-relaxed text-gray-600">
                    Didirikan pada tahun 2003, kami memulai sebagai bisnis keluarga kecil dan telah berkembang menjadi salah satu pemasok terbesar di wilayah ini, yang terkenal dengan kualitas dan keandalan.
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h2 class="font-semibold text-lg mb-2 text-gray-900">Misi dan Nilai-Nilai Kami</h2>
                <p class="text-sm leading-relaxed text-gray-600">
                    Misi kami adalah menginspirasi pelanggan dengan batu alam berkualitas tinggi dan layanan yang luar biasa, berlandaskan integritas, keunggulan, dan inovasi.
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h2 class="font-semibold text-lg mb-2 text-gray-900">Tim Kami</h2>
                <p class="text-sm leading-relaxed text-gray-600">
                    Tim kami terdiri dari para profesional berpengalaman yang bersemangat tentang batu alam dan berkomitmen untuk memberikan layanan terbaik bagi Anda.
                </p>
            </div>
        </div>

        <!-- Customer Testimonials Section -->
        <section class="mb-16">
            <h2 class="font-semibold text-2xl mb-8 text-center text-gray-800">
                Testimoni Pelanggan
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Testimonial Card 1 -->
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                    <div class="flex items-center space-x-3 mb-4">
                        <img alt="Foto profil Siti Aminah" class="w-12 h-12 rounded-full object-cover" src="https://storage.googleapis.com/a1aa/image/c91c0d00-1596-49f5-1140-b80fed200105.jpg" />
                        <div>
                            <p class="font-semibold text-sm text-black">Siti Aminah</p>
                            <p class="text-xs text-gray-500">2023-08-15</p>
                        </div>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-700 flex-grow">
                        "Saya sangat senang dengan meja dapur batu alam yang saya beli. Kualitasnya luar biasa, dan itu telah sepenuhnya mengubah dapur saya. Staf sangat membantu dan berpengetahuan."
                    </p>
                </div>

                <!-- Testimonial Card 2 -->
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                    <div class="flex items-center space-x-3 mb-4">
                        <img alt="Foto profil Budi Santoso" class="w-12 h-12 rounded-full object-cover" src="https://storage.googleapis.com/a1aa/image/c6b8c5c8-be53-4eb8-b4a8-5bc68227b02c.jpg" />
                        <div>
                            <p class="font-semibold text-sm text-black">Budi Santoso</p>
                            <p class="text-xs text-gray-500">2023-07-22</p>
                        </div>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-700 flex-grow">
                        "Lantai batu alam yang saya pesan indah dan tahan lama. Itu telah menambahkan sentuhan keanggunan ke ruang tamu saya. Pengiriman cepat, dan pemasangan lancar."
                    </p>
                </div>

                <!-- Testimonial Card 3 -->
                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col">
                    <div class="flex items-center space-x-3 mb-4">
                        <img alt="Foto profil Dewi Lestari" class="w-12 h-12 rounded-full object-cover" src="https://storage.googleapis.com/a1aa/image/6e4cff94-0688-4b75-5a31-d83b4af08aa1.jpg" />
                        <div>
                            <p class="font-semibold text-sm text-black">Dewi Lestari</p>
                            <p class="text-xs text-gray-500">2023-06-10</p>
                        </div>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-700 flex-grow">
                        "Saya sangat merekomendasikan Mustafa Stone untuk pilihan produk mereka yang sangat baik dan layanan pelanggan yang luar biasa. Saya senang dengan hasilnya!"
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact Us Section -->
        <section class="text-center bg-white p-8 rounded-lg shadow-md">
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
</div>
