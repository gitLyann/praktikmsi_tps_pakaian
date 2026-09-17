<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Pembeli - TPS Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h4 class="text-center fw-bold mb-1">Daftar Akun Baru</h4>
                        <p class="text-center text-muted small mb-4">Khusus Pendaftaran Pembeli Baru</p>

                        @if($errors->any())
                            <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                        @endif

                        <form action="{{ route('register') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Email Gmail</label>
                                <input type="email" name="email" class="form-control" placeholder="nama@gmail.com" required value="{{ old('email') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Password</label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Daftar Sekarang</button>
                        </form>

                        <div class="text-center mt-3">
                            <span class="small text-muted">Sudah punya akun?</span>
                            <a href="{{ route('login') }}" class="small text-decoration-none">Login disini</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
