<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - La Mamora</title>
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
        <h1 class="lm-auth-title">Masuk ke Akun</h1>
        <p class="lm-auth-lead">Gunakan email dan password yang terdaftar untuk melanjutkan.</p>

        @if($errors->any())
            <div class="lm-auth-alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="lm-auth-group">
                <label class="lm-auth-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="lm-auth-input" placeholder="nama@gmail.com" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="lm-auth-group">
                <label class="lm-auth-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="lm-auth-input" placeholder="Minimal 6 karakter" required>
            </div>
            <button type="submit" class="lm-auth-submit">Masuk</button>
        </form>

        <p class="lm-auth-foot">
            Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a>
        </p>

        <div class="lm-auth-back">
            <a href="{{ route('landing') }}">← Kembali ke halaman utama</a>
        </div>
    </div>
</body>
</html>
