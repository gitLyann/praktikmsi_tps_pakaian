<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>La Mamora - Toko Outfit Official</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
{{-- Design system La Mamora: satu sumber token visual (dipakai bersama dashboard) --}}
<link href="{{ asset('css/la-mamora.css') }}" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--navy);background:var(--sky2);line-height:1.6}
a{color:inherit;text-decoration:none}
button,input,textarea,select{font:inherit;color:inherit}
button{cursor:pointer;border:0;background:none}
:focus-visible{outline:2px solid var(--orange);outline-offset:3px}
section{scroll-margin-top:70px}
.wrap{max-width:1140px;margin:0 auto;padding:0 24px}
@media(prefers-reduced-motion:reduce){*{animation:none!important;scroll-behavior:auto!important}}

/* Navbar */
header{position:fixed;inset:0 0 auto;z-index:50;background:rgba(246,250,255,.82);backdrop-filter:blur(12px);border-bottom:1px solid transparent;transition:border-color .2s}
header.scrolled{border-color:var(--line)}
.nav{display:flex;align-items:center;height:68px;gap:24px}
.logo{display:flex;align-items:center;gap:8px;font-weight:800;font-size:20px}
.logo i{width:30px;height:30px;border-radius:9px;background:var(--navy);display:grid;place-items:center;color:#fff;font-style:normal;font-size:15px}
.menu{display:flex;gap:34px;margin:0 auto;list-style:none;font-size:13px;font-weight:500}
.menu>li{position:relative}
.menu a.link,.menu button{padding:6px 0;display:inline-flex;align-items:center;gap:4px;color:#3b4760}
.menu a.active,.menu button.active{color:var(--navy);font-weight:700;border-bottom:2px solid var(--orange)}
.drop{position:absolute;top:100%;left:-14px;min-width:170px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:6px;box-shadow:0 12px 30px rgba(20,33,61,.12);opacity:0;visibility:hidden;transform:translateY(6px);transition:.18s}
.menu li:hover .drop,.menu li:focus-within .drop{opacity:1;visibility:visible;transform:none}
.drop a{display:block;padding:9px 12px;border-radius:8px}
.drop a:hover{background:var(--sky)}
.auth{display:flex;gap:10px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 22px;border-radius:99px;font-size:13px;font-weight:600;transition:.15s}
.btn.dark{background:var(--navy);color:#fff}
.btn.dark:hover{background:#0b1426}
.btn.line{border:1.5px solid #b9c6da;background:#fff}
.btn.line:hover{border-color:var(--navy)}
.btn.orange{background:var(--orange);color:#fff}
.burger{display:none;font-size:22px;margin-left:auto}

/* Hero */
#home{min-height:100vh;padding-top:68px;display:flex;align-items:center;position:relative;overflow:hidden;background:linear-gradient(160deg,#dbe9fb 0%,#eef5fd 55%,#f6faff 100%)}
.hero{display:grid;grid-template-columns:1.1fr .9fr;gap:20px;align-items:center;position:relative;z-index:2}
.hero h1{font-size:clamp(38px,5.4vw,66px);line-height:1.1;font-weight:800;letter-spacing:-.02em}
.hero h1 u{text-decoration:none;position:relative;white-space:nowrap}
.hero h1 u::after{content:"";position:absolute;left:0;right:0;bottom:-8px;height:8px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='8'%3E%3Cpath d='M0 4Q6 0 12 4T24 4' fill='none' stroke='%23ff6b2c' stroke-width='2'/%3E%3C/svg%3E") repeat-x}
.hero p{margin:28px 0 24px;color:var(--muted);max-width:380px;font-size:14px}
.hero>div:first-child>*{animation:rise .7s both}
.hero>div:first-child>*:nth-child(2){animation-delay:.12s}
.hero>div:first-child>*:nth-child(3){animation-delay:.24s}
@keyframes rise{from{opacity:0;transform:translateY(22px)}}
.cloud{position:absolute;background:#fff;border-radius:99px;opacity:.85;filter:blur(1px);animation:drift 26s linear infinite}
.cloud::before,.cloud::after{content:"";position:absolute;background:#fff;border-radius:50%}
.cloud.c1{width:110px;height:26px;top:24%;right:8%}
.cloud.c1::before{width:44px;height:44px;left:18px;top:-22px}
.cloud.c2{width:80px;height:20px;bottom:22%;right:34%;animation-duration:34s}
.cloud.c2::before{width:32px;height:32px;left:14px;top:-16px}
@keyframes drift{50%{transform:translateX(-40px)}}

/* Phone */
.stage{position:relative;display:grid;place-items:center;min-height:520px}
.phone{width:262px;height:520px;border-radius:38px;background:#111;padding:9px;box-shadow:0 30px 60px rgba(20,33,61,.28);transform:rotate(-5deg);animation:float 5s ease-in-out infinite}
@keyframes float{50%{transform:rotate(-5deg) translateY(-12px)}}
.screen{height:100%;border-radius:30px;background:#fff;padding:14px 12px;overflow:hidden;position:relative}
.screen::before{content:"";position:absolute;top:8px;left:50%;width:70px;height:16px;background:#111;border-radius:99px;transform:translateX(-50%)}
.s-top{display:flex;justify-content:space-between;align-items:center;margin:22px 0 10px;font-size:9px;font-weight:600}
.s-top span{background:var(--sky);border-radius:99px;padding:3px 8px}
.screen h4{font-size:15px;line-height:1.2;margin-bottom:12px}
.s-row{display:flex;justify-content:space-between;font-size:10px;font-weight:700;margin-bottom:6px}
.s-row small{color:var(--orange);font-weight:600}
.cards{display:flex;gap:8px;margin-bottom:12px}
.mc{flex:1;border-radius:12px;background:var(--sky);padding:8px;font-size:9px;font-weight:600}
.mc b{display:block;font-size:34px;text-align:center;margin-bottom:4px;font-weight:400}
.mc em{font-style:normal;color:var(--orange)}
.item{display:flex;align-items:center;gap:8px;background:#f4f7fc;border-radius:12px;padding:8px;margin-bottom:6px;font-size:9px;font-weight:600}
.item span{font-size:20px}
.item i{margin-left:auto;font-style:normal;background:var(--navy);color:#fff;padding:3px 8px;border-radius:99px;font-size:8px}
.tab{position:absolute;bottom:10px;left:12px;right:12px;height:34px;background:#fff;border-radius:99px;box-shadow:0 4px 14px rgba(0,0,0,.1);display:flex;justify-content:space-around;align-items:center;font-size:12px}
.tab b{width:28px;height:28px;border-radius:50%;background:var(--orange);color:#fff;display:grid;place-items:center;font-size:12px;margin-top:-10px}
.chip{position:absolute;background:#fff;border-radius:14px;padding:10px 14px;font-size:11px;font-weight:700;box-shadow:0 10px 26px rgba(20,33,61,.15);animation:float2 6s ease-in-out infinite}
.chip.a{left:2%;top:26%}
.chip.b{right:0;bottom:16%;animation-delay:1.2s}
@keyframes float2{50%{transform:translateY(-8px)}}

/* Sections */
.sec{padding:96px 0}
.sec.alt{background:#fff}
.head{max-width:560px;margin-bottom:44px}
.head h2{font-size:clamp(28px,3.6vw,40px);line-height:1.15;letter-spacing:-.01em}
.head p{color:var(--muted);margin-top:12px}
.about{display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.stat{background:var(--sky2);border:1px solid var(--line);border-radius:18px;padding:26px}
.stat b{font-size:32px;display:block}
.stat span{color:var(--muted);font-size:13px}
.about ul{list-style:none;margin-top:20px;display:grid;gap:10px}
.about li::before{content:"✓";color:var(--orange);font-weight:800;margin-right:10px}

.tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:26px}
.tabs button{padding:9px 18px;border-radius:99px;border:1.5px solid var(--line);background:#fff;font-size:13px;font-weight:600}
.tabs button.on{background:var(--navy);color:#fff;border-color:var(--navy)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:18px}
.p{background:#fff;border:1px solid var(--line);border-radius:18px;padding:10px;transition:.2s}
.p:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(20,33,61,.1)}
.p .im{aspect-ratio:1/.85;border-radius:12px;background:var(--sky);display:grid;place-items:center;font-size:64px;position:relative;overflow:hidden}
.p .im img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.p .tag{position:absolute;top:10px;left:10px;z-index:1;font-size:10px;font-weight:700;background:var(--orange);color:#fff;padding:3px 9px;border-radius:99px}
.p .tag.out{background:#d64545}
.p h3{font-size:14px;margin:12px 6px 2px}
.p small{margin:0 6px;color:var(--muted);font-size:11px}
.p .row{display:flex;align-items:center;justify-content:space-between;margin:12px 6px 4px}
.p .row b{font-size:14px}
.p .add{width:32px;height:32px;border-radius:50%;background:var(--navy);color:#fff;font-size:16px;display:grid;place-items:center}
.p .add:hover{background:var(--orange)}
.empty-note{grid-column:1/-1;text-align:center;color:var(--muted);padding:40px 0;font-size:14px;background:#fff;border:1px dashed var(--line);border-radius:18px}

/* Rekomendasi produk (DSS) */
.rec-why{display:flex;flex-wrap:wrap;gap:6px;margin:10px 6px 4px}
.rec-why span{font-size:10px;font-weight:700;background:var(--sky);color:var(--navy);padding:3px 9px;border-radius:99px}
.rec-note{display:inline-flex;align-items:center;gap:8px;font-size:12px;color:var(--muted);background:#fff;border:1px dashed var(--line);border-radius:99px;padding:8px 14px;margin-bottom:20px}
.rec-note b{color:var(--navy)}

.contact{display:grid;grid-template-columns:1fr 1fr;gap:48px}
.info{display:grid;gap:18px;margin-top:26px}
.info div{display:flex;gap:14px;align-items:center}
.info span{width:44px;height:44px;border-radius:12px;background:var(--sky);display:grid;place-items:center}
form.card{background:#fff;border:1px solid var(--line);border-radius:20px;padding:28px;display:grid;gap:14px}
label{font-size:12px;font-weight:600;display:grid;gap:6px}
label[hidden]{display:none}
input,textarea,select{border:1.5px solid var(--line);border-radius:12px;padding:12px 14px;background:var(--sky2);width:100%}
input:focus,textarea:focus{outline:0;border-color:var(--orange)}
input.err,textarea.err{border-color:#d64545}
textarea{min-height:110px;resize:vertical}

.blog{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px}
.post{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden}
.post .im{height:150px;display:grid;place-items:center;font-size:56px;background:var(--sky)}
.post div.b{padding:18px}
.post small{color:var(--orange);font-weight:700;font-size:11px}
.post h3{font-size:16px;margin:6px 0}
.post p{font-size:13px;color:var(--muted)}
footer{background:var(--navy);color:#b8c3d8;padding:34px 0;font-size:13px}
footer .wrap{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}

/* Modal */
dialog{border:0;border-radius:22px;padding:28px;width:min(400px,92vw);margin:auto;box-shadow:0 30px 80px rgba(20,33,61,.3)}
dialog::backdrop{background:rgba(20,33,61,.45);backdrop-filter:blur(3px)}
.seg{display:grid;grid-template-columns:1fr 1fr;background:var(--sky);border-radius:99px;padding:4px;margin-bottom:20px}
.seg button{padding:9px;border-radius:99px;font-size:13px;font-weight:600}
.seg button.on{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.08)}
dialog form{display:grid;gap:12px}
.close{position:absolute;top:14px;right:16px;font-size:18px}
.msg{font-size:12px;color:#1c8c4e;min-height:16px;font-weight:600}
.field-err{font-size:11px;color:#c62828;font-weight:600}
.toast{position:fixed;bottom:24px;left:50%;transform:translate(-50%,80px);background:var(--navy);color:#fff;padding:12px 22px;border-radius:99px;font-size:13px;transition:.3s;z-index:99}
.toast.show{transform:translate(-50%,0)}

@media(max-width:900px){
  .burger{display:block}
  .auth{display:none}
  .menu{position:absolute;top:68px;left:0;right:0;flex-direction:column;gap:0;margin:0;background:#fff;border-bottom:1px solid var(--line);padding:8px 24px 16px;display:none}
  .menu.open{display:flex}
  .menu>li{padding:10px 0}
  .drop{position:static;opacity:1;visibility:visible;transform:none;box-shadow:none;border:0;padding:0 0 0 12px}
  .menu .m-auth{display:flex!important;gap:10px;padding-top:12px}
  .hero,.about,.contact{grid-template-columns:1fr}
  .stage{min-height:480px}
}
.m-auth{display:none}
</style>
</head>
<body>

@php
    // Emoji fallback per kategori (dipakai bila produk tidak punya foto),
    // pola yang sama dengan katalog toko/index.blade.php
    $emojiFor = function ($nama) {
        $n = \Illuminate\Support\Str::lower($nama ?? '');
        if (str_contains($n, 'jeans') || str_contains($n, 'celana')) return '👖';
        if (str_contains($n, 'tas')) return '🎒';
        if (str_contains($n, 'outer') || str_contains($n, 'jaket') || str_contains($n, 'hoodie')) return '🧥';
        if (str_contains($n, 'kemeja')) return '👔';
        if (str_contains($n, 'topi') || str_contains($n, 'aksesoris')) return '🧢';
        return '👕';
    };

    $dashUrl = auth()->check()
        ? match (auth()->user()->role) {
            'staff' => route('staff.dashboard'),
            'admin' => route('admin.dashboard'),
            default => route('toko.index'),
        }
        : null;
@endphp

<header id="top">
  <div class="wrap nav">
    <a href="#home" class="logo"><i>L</i>La Mamora.</a>
    <ul class="menu" id="menu">
      <li><a class="link" href="#home" data-s="home">Home</a></li>
      <li><a class="link" href="#about" data-s="about">About</a></li>
      <li>
        <button class="link" data-s="koleksi" aria-haspopup="true">Koleksi ⌄</button>
        <div class="drop">
          @foreach($categories as $cat)
            <a href="#koleksi" data-f="{{ $cat->name }}">{{ $emojiFor($cat->name) }} {{ $cat->name }}</a>
          @endforeach
        </div>
      </li>
      <li><a class="link" href="#contact" data-s="contact">Contact</a></li>
      <li><a class="link" href="#blog" data-s="blog">Blog</a></li>
      <li class="m-auth">
        @guest
          <button class="btn dark" data-auth="login">Login</button>
          <button class="btn line" data-auth="register">Register</button>
        @else
          <a class="btn dark" href="{{ $dashUrl }}">Dashboard</a>
        @endguest
      </li>
    </ul>
    <div class="auth">
      @guest
        <button class="btn dark" data-auth="login">Login</button>
        <button class="btn line" data-auth="register">Register</button>
      @else
        <a class="btn line" href="{{ route('toko.index') }}">Katalog</a>
        <a class="btn dark" href="{{ $dashUrl }}">Dashboard</a>
      @endguest
    </div>
    <button class="burger" id="burger" aria-label="Buka menu">☰</button>
  </div>
</header>

<main>
<!-- HOME -->
<section id="home">
  <span class="cloud c1"></span><span class="cloud c2"></span>
  <div class="wrap hero">
    <div>
      <h1>Outfit Official Untuk <u>Gaya Terbaikmu</u></h1>
      <p>Baju, celana, jaket, dan aksesoris original dari toko resmi. Belanja kapan saja, dikirim aman ke rumahmu.</p>
      <a href="#koleksi" class="btn dark">Belanja Sekarang</a>
    </div>
    <div class="stage">
      <div class="phone"><div class="screen">
        <div class="s-top"><span>La Mamora</span><span>🛍 3</span></div>
        <h4>Bangun gaya<br>khasmu</h4>
        <div class="s-row">Rekomendasi <small>Lihat semua</small></div>
        <div class="cards">
          <div class="mc"><b>🧥</b>Outer Pria<br><em>Rp 70.000</em></div>
          <div class="mc"><b>👕</b>Kemeja Polos<br><em>Rp 150.000</em></div>
        </div>
        <div class="s-row">Keranjangku</div>
        <div class="item"><span>👖</span>Jeans Modern<i>Bayar</i></div>
        <div class="item"><span>🎒</span>Gaul Totebag<i>Bayar</i></div>
        <div class="tab"><span>⌂</span><span>♡</span><b>＋</b><span>🛍</span><span>☺</span></div>
      </div></div>
      <div class="chip a">✅ 100% Original</div>
      <div class="chip b">🚚 Gratis Ongkir</div>
    </div>
  </div>
</section>

<!-- ABOUT -->
<section id="about" class="sec alt"><div class="wrap about">
  <div>
    <div class="head" style="margin-bottom:0"><h2>Toko resmi yang paham gaya harianmu</h2>
    <p>La Mamora menyeleksi setiap produk langsung dari brand dan produsen. Kamu mendapat kualitas terjamin dengan harga jelas.</p></div>
    <ul><li>Produk original bergaransi tukar ukuran</li><li>Pengiriman 1-3 hari ke seluruh Indonesia</li><li>Pembayaran aman: transfer, e-wallet, COD</li></ul>
  </div>
  <div class="stats">
    <div class="stat"><b>{{ $stats['produk'] }}</b><span>Produk outfit</span></div>
    <div class="stat"><b>{{ $stats['pelanggan'] }}</b><span>Pelanggan terdaftar</span></div>
    <div class="stat"><b>{{ $stats['kategori'] }}</b><span>Kategori outfit</span></div>
    <div class="stat"><b>1-3 hari</b><span>Pengiriman nasional</span></div>
  </div>
</div></section>

<!-- KOLEKSI -->
<section id="koleksi" class="sec"><div class="wrap">
  <div class="head"><h2>Koleksi terbaru</h2><p>Pilih kategori, lalu lihat produk unggulan kami. Masuk untuk mulai berbelanja.</p></div>
  <div class="tabs" id="tabs">
    <button class="on" data-c="Semua">Semua</button>
    @foreach($categories as $cat)
      <button data-c="{{ $cat->name }}">{{ $cat->name }}</button>
    @endforeach
  </div>
  <div class="grid" id="grid">
    @forelse($products as $product)
      @php
          $kategori = $product->category->name ?? '';
          $emoji = $emojiFor($kategori);
      @endphp
      <article class="p" data-kategori="{{ $kategori }}">
        <div class="im">
          @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
          @else
            {{ $emoji }}
          @endif
          @if((int) $product->stock <= 0)
            <span class="tag out">Stok Habis</span>
          @endif
        </div>
        <h3>{{ $product->name }}</h3>
        <small>{{ $kategori }}{{ $product->type ? ' · ' . $product->type : '' }}</small>
        <div class="row">
          <b>Rp {{ number_format($product->price, 0, ',', '.') }}</b>
          @guest
            <button class="add" data-need-auth aria-label="Masuk untuk membeli {{ $product->name }}">+</button>
          @else
            <a class="add" href="{{ route('toko.index') }}" aria-label="Lihat {{ $product->name }} di katalog">→</a>
          @endguest
        </div>
      </article>
    @empty
    @endforelse
    <div class="empty-note" id="gridEmpty" @if($products->isNotEmpty()) style="display:none" @endif>
      {{ $products->isEmpty() ? 'Belum ada produk. Silakan kembali lagi nanti.' : 'Tidak ada produk pada kategori ini.' }}
    </div>
  </div>
</div></section>

<!-- REKOMENDASI -->
<section id="rekomendasi" class="sec alt"><div class="wrap">
  <div class="head"><h2>Rekomendasi untukmu</h2>
    <p>Dipilih otomatis dari riwayat belanja dan produk terlaris, jadi lebih sesuai dengan selerimu.</p>
  </div>
  <div class="rec-note">
    <span>&#9889;</span>
    <span>
      @if($isPersonal)
        <b>Rekomendasi personal</b> berdasarkan riwayat belanja &amp; kategori favoritmu.
      @else
        <b>Rekomendasi populer</b> untuk pengunjung.
      @endif
    </span>
  </div>
  <div class="grid">
    @forelse($recommended as $item)
      @php
          $recProduct = $item['product'];
          $kategori = $recProduct->category->name ?? '';
          $emoji = $emojiFor($kategori);
      @endphp
      <article class="p">
        <div class="im">
          @if($recProduct->image_url)
            <img src="{{ $recProduct->image_url }}" alt="{{ $recProduct->name }}" loading="lazy">
          @else
            {{ $emoji }}
          @endif
          <span class="tag">Rekomendasi</span>
        </div>
        <h3>{{ $recProduct->name }}</h3>
        <small>{{ $kategori }}{{ $recProduct->type ? ' · ' . $recProduct->type : '' }}</small>
        <div class="row">
          <b>Rp {{ number_format($recProduct->price, 0, ',', '.') }}</b>
          @guest
            <button class="add" data-need-auth aria-label="Masuk untuk membeli {{ $recProduct->name }}">+</button>
          @else
            <a class="add" href="{{ route('toko.index') }}" aria-label="Lihat {{ $recProduct->name }} di katalog">&rarr;</a>
          @endguest
        </div>
        <div class="rec-why">
          @foreach($item['reasons'] as $reason)
            <span>{{ $reason }}</span>
          @endforeach
        </div>
      </article>
    @empty
      <div class="empty-note">Belum ada rekomendasi. Produk tersedia akan muncul di sini.</div>
    @endforelse
  </div>
</div></section>

<!-- CONTACT -->
<section id="contact" class="sec alt"><div class="wrap contact">
  <div>
    <div class="head" style="margin-bottom:0"><h2>Ada pertanyaan? Hubungi kami</h2><p>Tim kami membalas dalam 1x24 jam.</p></div>
    <div class="info">
      <div><span>📍</span>Yogyakarta, Indonesia</div>
      <div><span>✉️</span>halo@lamamora.id</div>
      <div><span>💬</span>+62 812-0000-0000</div>
    </div>
  </div>
  <form class="card" id="contactForm">
    <label>Nama<input required placeholder="Nama lengkap"></label>
    <label>Email<input type="email" required placeholder="nama@email.com"></label>
    <label>Pesan<textarea required placeholder="Tulis pesanmu"></textarea></label>
    <button class="btn orange" type="submit">Kirim pesan</button>
  </form>
</div></section>

<!-- BLOG -->
<section id="blog" class="sec"><div class="wrap">
  <div class="head"><h2>Blog &amp; tips gaya</h2><p>Inspirasi padu padan dan panduan memilih ukuran.</p></div>
  <div class="blog">
    <article class="post"><div class="im">🧥</div><div class="b"><small>Tips</small><h3>5 cara padu padan jaket denim</h3><p>Dari kasual sampai semi formal, satu jaket bisa banyak gaya.</p></div></article>
    <article class="post"><div class="im">📏</div><div class="b"><small>Panduan</small><h3>Cara memilih ukuran yang pas</h3><p>Ukur badan dengan benar agar tidak perlu tukar ukuran.</p></div></article>
    <article class="post"><div class="im">🧢</div><div class="b"><small>Tren</small><h3>Aksesoris kecil, dampak besar</h3><p>Topi, tas, dan ikat pinggang yang melengkapi outfit harian.</p></div></article>
  </div>
</div></section>
</main>

<footer><div class="wrap"><span>© 2026 La Mamora. Toko outfit official.</span><span>Syarat &amp; Ketentuan · Kebijakan Privasi</span></div></footer>

<!-- Login / Register (terhubung ke backend: POST /login & /register) -->
<dialog id="dlg">
  <button class="close" id="close" aria-label="Tutup">✕</button>
  <div class="seg"><button data-tab="login" class="on">Login</button><button data-tab="register">Register</button></div>
  <form id="authForm" method="POST" action="{{ route('login') }}">
    @csrf
    <input type="hidden" name="_tab" id="tabField" value="login">
    <label id="nameF" hidden>Nama
      <input name="name" placeholder="Nama lengkap" value="{{ old('name') }}" @error('name') class="err" @enderror>
      @error('name')<span class="field-err">{{ $message }}</span>@enderror
    </label>
    <label>Email
      <input type="email" name="email" required placeholder="nama@email.com" value="{{ old('email') }}" @error('email') class="err" @enderror>
      @error('email')<span class="field-err">{{ $message }}</span>@enderror
    </label>
    <label>Password
      <input type="password" name="password" minlength="6" required placeholder="Minimal 6 karakter" autocomplete="current-password" @error('password') class="err" @enderror>
      @error('password')<span class="field-err">{{ $message }}</span>@enderror
    </label>
    <label id="confF" hidden>Konfirmasi Password
      <input type="password" name="password_confirmation" minlength="6" placeholder="Ulangi password" autocomplete="new-password">
    </label>
    <button class="btn dark" type="submit" id="submitBtn">Login</button>
    <div class="msg" id="msg"></div>
  </form>
</dialog>
<div class="toast" id="toast"></div>

<script>
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];

function toast(t){const el=$("#toast");el.textContent=t;el.classList.add("show");clearTimeout(toast.t);toast.t=setTimeout(()=>el.classList.remove("show"),2600)}

/* Filter kategori (client-side, data dari database) */
let filter="Semua";
function setFilter(c){
  filter=c;
  $$("#tabs [data-c]").forEach(b=>b.classList.toggle("on",b.dataset.c===c));
  let visible=0;
  $$("#grid .p").forEach(card=>{
    const show=(c==="Semua"||card.dataset.kategori===c);
    card.style.display=show?"":"none";
    if(show)visible++;
  });
  const empty=$("#gridEmpty");
  if(empty)empty.style.display=visible===0?"":"none";
}
$("#tabs").addEventListener("click",e=>{const b=e.target.closest("[data-c]");if(b)setFilter(b.dataset.c)});
$$(".drop a").forEach(a=>a.addEventListener("click",()=>{setFilter(a.dataset.f);$("#menu").classList.remove("open")}));

/* Tombol "+" pada kartu produk (koleksi & rekomendasi): tamu diminta masuk dulu */
document.addEventListener("click",e=>{
  const b=e.target.closest("[data-need-auth]");
  if(!b)return;
  setTab("login");
  if(dlg && dlg.open===false) dlg.showModal();
  toast("Silakan masuk dulu untuk berbelanja.");
});

/* Navbar: scroll spy + shadow + mobile */
const links=$$(".menu [data-s]");
const spy=new IntersectionObserver(es=>es.forEach(en=>{
  if(en.isIntersecting)links.forEach(l=>l.classList.toggle("active",l.dataset.s===en.target.id));
}),{rootMargin:"-45% 0px -50% 0px"});
["home","about","koleksi","contact","blog"].forEach(id=>{const el=$("#"+id);if(el)spy.observe(el)});
addEventListener("scroll",()=>$("#top").classList.toggle("scrolled",scrollY>10),{passive:true});
$("#burger").addEventListener("click",()=>$("#menu").classList.toggle("open"));
$$(".menu a.link").forEach(a=>a.addEventListener("click",()=>$("#menu").classList.remove("open")));

/* Auth modal -> backend nyata */
const dlg=$("#dlg");
function setTab(t){
  $$(".seg button").forEach(b=>b.classList.toggle("on",b.dataset.tab===t));
  const reg=t==="register";
  $("#nameF").hidden=!reg;
  $("#nameF input").required=reg;
  $("#confF").hidden=!reg;
  $("#confF input").required=reg;
  $("#submitBtn").textContent=reg?"Buat akun":"Login";
  $("#authForm").action=reg?"{{ route('register') }}":"{{ route('login') }}";
  $("#tabField").value=t;
  $("#msg").textContent="";
}
$$("[data-auth]").forEach(b=>b.addEventListener("click",()=>{
  setTab(b.dataset.auth);
  if(dlg && dlg.open===false) dlg.showModal();
  $("#menu").classList.remove("open");
}));
$$(".seg button").forEach(b=>b.addEventListener("click",()=>setTab(b.dataset.tab)));
$("#close").addEventListener("click",()=>dlg.close());
dlg.addEventListener("click",e=>{if(e.target===dlg)dlg.close()});

/* Form kontak (demo - backend pesan belum ada di skema) */
$("#contactForm").addEventListener("submit",e=>{e.preventDefault();e.target.reset();toast("Pesan terkirim. Kami segera membalas.")});

/* Init: set tab sesuai kiriman terakhir (login/register gagal) */
setTab(@json(old('_tab', 'login')));
@if($errors->any())
  if(dlg && dlg.open===false) dlg.showModal();
@endif
@if(session('success'))
  toast(@json(session('success')));
@endif
</script>
</body>
</html>
