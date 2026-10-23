<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun Pembeli - La Mamora</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/la-mamora.css') }}" rel="stylesheet">
    <link href="{{ asset('css/la-mamora-shop.css') }}" rel="stylesheet">
    @include('layouts.theme')
</head>
<body class="lm-auth">
    <div class="lm-auth-card">
        <div class="lm-auth-brand">
            @include('layouts.lamamora-logo', ['height' => 40])<span>La Mamora.</span>
        </div>
        <h1 class="lm-auth-title">Daftar Akun Baru</h1>
        <p class="lm-auth-lead">Khusus pendaftaran pembeli baru di La Mamora.</p>

        @if($errors->any())
            <div class="lm-auth-alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="lm-auth-group">
                <label class="lm-auth-label" for="name">Nama Lengkap</label>
                <input type="text" name="name" id="name" class="lm-auth-input" placeholder="Nama sesuai identitas" value="{{ old('name') }}" required autofocus>
            </div>
            <div class="lm-auth-group">
                <label class="lm-auth-label" for="email">Email Gmail</label>
                <input type="email" name="email" id="email" class="lm-auth-input" placeholder="nama@gmail.com" value="{{ old('email') }}" required>
            </div>
            <div class="lm-auth-group">
                <label class="lm-auth-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="lm-auth-input" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="lm-auth-group">
                <label class="lm-auth-label" for="password_confirmation">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="lm-auth-input" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="lm-auth-submit">Daftar Sekarang</button>
        </form>

        <p class="lm-auth-foot">
            Sudah punya akun? <a href="{{ route('login') }}">Login di sini</a>
        </p>

        <div class="lm-auth-back">
            <a href="{{ route('landing') }}">← Kembali ke halaman utama</a>
        </div>
    </div>
</body>
</html>
