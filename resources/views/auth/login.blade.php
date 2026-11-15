@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <section class="a18-stripes">
        <div class="mx-auto flex max-w-md flex-col px-4 py-14 sm:py-20">
            <div class="mb-8">
                @include('partials.logo', ['size' => 'xl'])
                <h1 class="a18-heading mt-6 text-4xl leading-none">Masuk Panel Showroom</h1>
                <p class="mt-3 text-[15px] text-white/65">Khusus staf Auto18. Pembeli dan penjual tidak perlu masuk.</p>
            </div>

            <form method="POST" action="{{ route('login.attempt') }}" class="a18-card relative space-y-4 p-6">
                <div class="a18-cut-tag absolute -top-1.5 left-0 h-3 w-32 bg-brand-600" aria-hidden="true"></div>
                @csrf
                <div>
                    <label for="email" class="a18-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="a18-input">
                </div>
                <div>
                    <label for="password" class="a18-label">Kata Sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="a18-input">
                </div>
                <label class="flex min-h-[2.5rem] items-center gap-2.5 text-[15px] text-white/80">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-brand-600">
                    Ingat saya
                </label>
                @error('email')
                    <p class="border-l-4 border-brand-600 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">{{ $message }}</p>
                @enderror
                <button type="submit" class="a18-btn-primary h-12 w-full text-xl">Masuk <x-lucide-arrow-right class="h-5 w-5" /></button>
            </form>
        </div>
    </section>
@endsection
