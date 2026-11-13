@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <section class="a18-stripes-soft">
        <div class="mx-auto flex max-w-md flex-col px-4 py-14 sm:py-20">
            <div class="mb-8 text-center">
                @include('partials.logo', ['size' => 'xl'])
                <h1 class="a18-heading mt-6 text-2xl">Masuk Panel Showroom</h1>
                <p class="mt-2 text-sm text-neutral-400">Khusus staf Auto18. Pembeli dan penjual tidak perlu masuk.</p>
            </div>

            <form method="POST" action="{{ route('login.attempt') }}" class="a18-card space-y-4 p-6">
                @csrf
                <div>
                    <label for="email" class="a18-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="a18-input">
                </div>
                <div>
                    <label for="password" class="a18-label">Kata Sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="a18-input">
                </div>
                <label class="flex items-center gap-2 text-sm text-neutral-300">
                    <input type="checkbox" name="remember" value="1" class="rounded border-white/20 bg-ink-900 text-brand-600 focus:ring-brand-500">
                    Ingat saya
                </label>
                @error('email')
                    <p class="rounded-lg border border-brand-600/40 bg-brand-600/10 px-3 py-2 text-sm text-brand-300" role="alert">{{ $message }}</p>
                @enderror
                <button type="submit" class="a18-btn-primary w-full">Masuk</button>
            </form>
        </div>
    </section>
@endsection
