<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('user.landing') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo />
            </a>

            <flux:spacer />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item :href="route('user.landing')" :current="request()->routeIs('user.landing')" wire:navigate>
                    {{ __('Beranda') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('user.product')" :current="request()->routeIs('user.product')" wire:navigate>
                    {{ __('Katalog') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('user.bucket')" :current="request()->routeIs('user.bucket')" wire:navigate>
                    {{ __('Keranjang') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('user.form')" :current="request()->routeIs('user.form')" wire:navigate>
                    {{ __('Formulir') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('user.profile')" :current="request()->routeIs('user.profile')" wire:navigate>
                    {{ __('Tentang Kami') }}
                </flux:navbar.item>
            </flux:navbar>

            <!-- Desktop User Menu -->
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
