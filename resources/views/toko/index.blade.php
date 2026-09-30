<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Katalog Pakaian - TPS Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            /* Palet terang */
            --bg:#f5f4f1; --card:#fff; --ink:#151515; --muted:#8a8a8a; --line:#ececec;
            --accent:#2563eb; --accent-soft:#e8f0fe; --field:#f3f3f1; --tile:#f0f1f7;
            --on-accent:#fff; --alert:#ffe9e0; --danger-soft:#ffe0e0; --danger:#e5484d;
            --radius:18px;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Plus Jakarta Sans','Inter',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--ink);font-size:15px;line-height:1.5;-webkit-font-smoothing:antialiased}
        button,input,select{font:inherit;color:inherit}
        button{cursor:pointer;border:0;background:none}
        :focus-visible{outline:2px solid var(--accent);outline-offset:2px}
        .app{display:grid;grid-template-columns:244px 1fr;gap:16px;padding:16px;min-height:100vh}

        /* Sidebar */
        .sidebar{background:var(--card);border-radius:var(--radius);padding:22px 18px;display:flex;flex-direction:column;position:sticky;top:16px;height:calc(100vh - 32px)}
        .brand{display:flex;align-items:center;gap:11px;font-size:22px;font-weight:800;padding:0 6px 26px;letter-spacing:-.02em}
        .logo{width:38px;height:38px;border-radius:11px;background:var(--accent);display:grid;place-items:center;color:var(--on-accent);font-size:19px;flex-shrink:0}
        .nav{display:flex;flex-direction:column;gap:3px}
        .nav a{display:flex;align-items:center;gap:11px;padding:12px 13px;border-radius:11px;color:var(--ink);text-decoration:none;position:relative;font-size:14.5px;font-weight:500}
        .nav a svg{width:19px;height:19px;flex-shrink:0;stroke-width:1.9}
        .nav a:hover{background:var(--field);color:var(--accent)}
        .nav a.active{background:var(--accent-soft);color:var(--accent);font-weight:700}
        .nav a.active::before{content:"";position:absolute;left:-18px;top:9px;bottom:9px;width:3.5px;border-radius:0 3px 3px 0;background:var(--accent)}
        .spacer{flex:1}
        .nav.bottom{border-top:1px solid var(--line);padding-top:14px;margin-top:14px}

        /* Main */
        .main{display:flex;flex-direction:column;gap:16px;min-width:0}
        .topbar{background:var(--card);border-radius:var(--radius);padding:16px 22px;display:flex;align-items:center;gap:18px}
        .title h1{font-size:22px;font-weight:700;letter-spacing:-.02em}
        .title p{font-size:13px;color:var(--muted);margin-top:3px}
        .search input{border:0;background:none;outline:0;width:100%}
        .tools{display:flex;align-items:center;gap:6px;background:var(--field);border-radius:99px;padding:5px;margin-left:auto}
        .ic{width:36px;height:36px;border-radius:50%;background:var(--card);display:grid;place-items:center;font-size:15px}
        .ic.alert{background:var(--alert)}

        /* Profil user di topbar, bisa diklik untuk membuka modal profil */
        .user{display:flex;align-items:center;gap:9px;padding:0 12px 0 4px;border-radius:99px;cursor:pointer;text-align:left}
        .user:hover{background:var(--card)}
        .avatar{width:36px;height:36px;border-radius:50%;background:var(--accent);color:var(--on-accent);display:grid;place-items:center;font-weight:700;font-size:15px;flex-shrink:0}
        .user b{display:block;font-size:14px;font-weight:600}
        .user small{font-size:12px;color:var(--muted)}

        .flash{margin:0 4px;padding:14px 18px;border-radius:12px;font-size:14.5px;display:flex;justify-content:space-between;align-items:center;gap:10px}
        .flash.ok{background:#e8f7ee;color:#1a7f43}
        .flash.err{background:var(--alert);color:#b3261e}

        .content{display:grid;grid-template-columns:196px 1fr;gap:16px;align-items:start}
        @media(min-width:1100px){.content{grid-template-columns:210px 1fr}}

        /* Filter */
        .filters{background:var(--card);border-radius:var(--radius);padding:16px}
        .acc{width:100%;display:flex;justify-content:space-between;align-items:center;background:var(--field);border-radius:11px;padding:14px 13px;font-weight:600;margin-bottom:11px;font-size:14.5px}
        .checks{display:flex;flex-direction:column;gap:13px;padding:6px 4px 16px}
        .checks label{display:flex;align-items:center;gap:9px;color:var(--ink);cursor:pointer;font-size:14px}
        .checks input{appearance:none;width:18px;height:18px;border:1.5px solid var(--line);border-radius:5px;display:grid;place-items:center;cursor:pointer;flex-shrink:0}
        .checks input:checked{background:var(--accent);border-color:var(--accent)}
        .checks input:checked::after{content:"✓";color:var(--on-accent);font-size:12px;line-height:1;font-weight:700}
        .range-title{font-weight:600;margin:6px 0 32px;font-size:14.5px}
        .slider{position:relative;height:16px;margin:0 6px 20px}
        .slider .track{position:absolute;top:6px;left:0;right:0;height:4px;border-radius:4px;background:#c7dcfd}
        .slider .fill{position:absolute;top:6px;height:4px;border-radius:4px;background:var(--accent)}
        .slider input[type=range]{position:absolute;inset:0;width:100%;appearance:none;background:none;pointer-events:none}
        .slider input[type=range]::-webkit-slider-thumb{appearance:none;pointer-events:auto;width:15px;height:15px;border-radius:50%;background:var(--accent);border:2.5px solid var(--card);box-shadow:0 0 0 1px var(--accent);cursor:pointer}
        .slider input[type=range]::-moz-range-thumb{pointer-events:auto;width:12px;height:12px;border-radius:50%;background:var(--accent);border:2.5px solid var(--card);cursor:pointer}
        .bubble{position:absolute;top:-30px;transform:translateX(-50%);background:var(--accent);color:var(--on-accent);font-size:12.5px;font-weight:600;padding:5px 11px;border-radius:99px;white-space:nowrap}
        .bubble::after{content:"";position:absolute;left:50%;bottom:-3px;width:7px;height:7px;background:var(--accent);transform:translateX(-50%) rotate(45deg)}
        .minmax{display:flex;align-items:center;gap:6px;margin-bottom:16px;color:var(--muted)}
        .minmax input{width:100%;min-width:0;border:1px solid var(--line);background:var(--field);border-radius:7px;padding:8px 9px;font-size:12.5px}
        .btn{background:var(--accent);color:var(--on-accent);font-weight:600;border-radius:99px;padding:13px 20px;font-size:15px}
        .btn:hover{filter:brightness(.95)}
        .btn.block{width:100%;margin-top:5px}
        .btn:disabled{background:var(--line);color:var(--muted);cursor:not-allowed;filter:none}

        /* List head */
        .list{display:flex;flex-direction:column;gap:16px;min-width:0}
        .head{background:var(--card);border-radius:var(--radius);padding:14px 16px;display:flex;align-items:center;gap:12px}
        .crumb{margin-right:auto;font-size:13px;color:var(--muted)}
        .crumb b{color:var(--ink)}
        .crumb .count{display:block;margin-top:6px;font-size:13.5px}
        .search{display:flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:99px;padding:10px 16px;width:250px;background:var(--field)}
        .pill{border:1px solid var(--line);border-radius:99px;padding:10px 15px;font-size:13.5px}

        /* Grid */
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:14px}
        .card{background:var(--card);border-radius:15px;padding:7px 7px 11px;display:flex;flex-direction:column}
        .thumb{aspect-ratio:1/0.82;border-radius:11px;background:var(--tile);display:grid;place-items:center;font-size:52px;overflow:hidden;position:relative}
        .thumb img{width:100%;height:100%;object-fit:cover}
        .card h3{font-size:15px;font-weight:600;margin:11px 5px 5px;line-height:1.35}
        .card p{font-size:12.5px;color:var(--muted);line-height:1.45;margin:0 5px 13px;min-height:36px}
        .foot{display:flex;align-items:center;margin:0 5px}
        .foot b{font-size:15px;margin-right:auto;font-weight:700}
        .mini{width:auto;height:24px;border-radius:99px;background:var(--field);display:inline-flex;align-items:center;gap:4px;font-size:12px;margin-left:6px;padding:0 9px;color:var(--muted);font-weight:600}
        .mini.out{background:var(--danger-soft);color:var(--danger)}
        .buy{width:100%;margin-top:11px;background:var(--accent-soft);color:var(--accent);font-weight:700;border-radius:99px;padding:11px;font-size:14px}
        .buy:disabled{background:var(--field);color:var(--muted);cursor:not-allowed}
        .empty{grid-column:1/-1;text-align:center;color:var(--muted);padding:44px;font-size:15px}

        /* Modal */
        .modal-b{position:fixed;inset:0;background:rgba(2,6,23,.62);display:none;align-items:center;justify-content:center;z-index:1055;padding:16px}
        .modal-b.show{display:flex}
        .modal-box{background:var(--card);border-radius:var(--radius);width:100%;max-width:440px;padding:24px;color:var(--ink)}
        .modal-box h2{font-size:19px;font-weight:700;margin-bottom:16px;letter-spacing:-.01em}
        .modal-box .rowline{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line);font-size:14.5px}
        .modal-box .rowline b{font-weight:600}
        .modal-box input,.modal-box select{width:100%;border:1px solid var(--line);background:var(--field);border-radius:11px;padding:11px 13px;margin-top:6px;font-size:14.5px}
        .modal-box label{font-size:13px;color:var(--muted);font-weight:600;display:block;margin-top:14px}
        .modal-act{display:flex;gap:9px;margin-top:20px}
        .modal-act .btn{flex:1;text-align:center}
        .modal-act .btn.ghost{background:var(--field);color:var(--muted)}
        .modal-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:18px}
        .modal-head h2{margin:0}
        .x{width:34px;height:34px;border-radius:50%;background:var(--field);color:var(--muted);display:grid;place-items:center;font-size:15px;flex-shrink:0}
        .x:hover{background:var(--line);color:var(--ink)}
        .profile-top{text-align:center;padding-bottom:18px;margin-bottom:16px;border-bottom:1px solid var(--line)}
        .profile-top .avatar{width:66px;height:66px;font-size:25px;margin:0 auto 13px}
        .profile-top h3{font-size:18px;font-weight:700}
        .profile-top p{font-size:13.5px;color:var(--muted);margin-top:5px;word-break:break-all}
        .role-badge{display:inline-block;margin-top:11px;background:var(--accent-soft);color:var(--accent);font-size:12.5px;font-weight:600;padding:6px 14px;border-radius:99px}
        .btn.danger{background:var(--danger)}
        .btn.danger:hover{background:#c93a3f}
        .info-ico{width:56px;height:56px;border-radius:16px;background:var(--accent-soft);color:var(--accent);display:grid;place-items:center;margin:0 auto 16px}
        .info-ico svg{width:26px;height:26px;stroke-width:1.9}
        .info-text{text-align:center;color:var(--muted);font-size:14.5px;line-height:1.6}

        @media(max-width:900px){
            .app{grid-template-columns:1fr}
            .sidebar{display:none}
            .content{grid-template-columns:1fr}
            .search{width:auto;flex:1}
        }
    </style>
    <style>
        .chatbot-widget { position: fixed; bottom: 24px; right: 24px; z-index: 1060; font-family: inherit; }
        .chatbot-fab { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); border: none; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; color: white; font-size: 1.5rem; }
        .chatbot-fab:hover { transform: scale(1.05); box-shadow: 0 6px 16px rgba(13, 110, 253, 0.5); }
        .chatbot-fab:focus { outline: none; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.3); }
        .chatbot-fab-icon-close { font-size: 1.25rem; }
        .chatbot-window { position: absolute; bottom: 80px; right: 0; width: 380px; max-width: calc(100vw - 48px); height: 500px; max-height: 70vh; background: white; border-radius: 16px; box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15); border: 1px solid #e9ecef; display: flex; flex-direction: column; overflow: hidden; animation: slideUpFade 0.3s ease; }
        @keyframes slideUpFade { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .chatbot-header { padding: 16px; border-bottom: 1px solid #e9ecef; flex-shrink: 0; background: #0d6efd; color: #fff; }
        .chatbot-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; background: #f8f9fa; }
        .chatbot-message { display: flex; max-width: 85%; animation: messageIn 0.3s ease; }
        @keyframes messageIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .chatbot-message.bot { align-self: flex-start; }
        .chatbot-message.user { align-self: flex-end; flex-direction: row-reverse; }
        .message-bubble { padding: 12px 16px; border-radius: 18px; max-width: 100%; word-wrap: break-word; }
        .chatbot-message.bot .message-bubble { background: white; border: 1px solid #e9ecef; border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); }
        .chatbot-message.user .message-bubble { background: #0d6efd; color: white; border-bottom-right-radius: 4px; }
        .chatbot-message .message-bubble p { margin: 0; line-height: 1.5; font-size: 0.9rem; }
        .chatbot-message .message-bubble small { display: block; margin-top: 4px; opacity: 0.7; }
        .chatbot-input-area { padding: 16px; border-top: 1px solid #e9ecef; background: white; flex-shrink: 0; }
        .chatbot-input-area .form-control { width: 100%; border-radius: 24px; border: 1px solid #dee2e6; padding: 10px 16px; font-size: 0.875rem; }
        .chatbot-input-area .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15); }
        .chatbot-input-area .send { margin-top: 8px; width: 100%; background: #0d6efd; color: #fff; border-radius: 99px; padding: 11px; font-weight: 600; }
        .chatbot-input-area .send:disabled { background: #dee2e6; color: #adb5bd; }
        .quick-action { font-size: 0.7rem; padding: 6px 12px; border-radius: 16px; transition: all 0.2s; background: #e7f1ff; color: #0d6efd; }
        .quick-action:hover { background: #0d6efd; color: white; }
        @media (max-width: 575.98px) { .chatbot-window { width: calc(100vw - 32px); height: 70vh; max-height: 70vh; bottom: 80px; right: 16px; } }
    </style>
</head>
<body>

@php
    // Emoji fallback per kategori, dipakai hanya bila produk tidak punya foto
    $emojiKategori = [
        'kaos' => '👕', 'kemeja' => '👔', 'baju' => '👕', 'celana' => '👖',
        'outerwear' => '🧥', 'jaket' => '🧥', 'hoodie' => '🧥', 'sweater' => '🧶',
        'atasan' => '👕', 'bawahan' => '👖', 'aksesoris' => '🧣', 'sepatu' => '👟',
        'topi' => '🧢', 'tas' => '🎒', 'kacamata' => '🕶️',
    ];
@endphp

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand"><span class="logo">❦</span>TPS Pakaian</div>
        <nav class="nav" aria-label="Navigasi utama">
            {{-- Ikon SVG inline (stroke=currentColor) supaya warna otomatis mengikuti state active/hover --}}
            <a href="{{ route('toko.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>Overview</span>
            </a>
            <a href="{{ route('toko.index') }}" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Katalog Produk</span>
            </a>
            <a href="#" data-info="cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <span>Keranjang Belanja</span>
            </a>
            <a href="{{ route('toko.pesanan') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><rect width="13" height="8" x="3" y="4" rx="2"/><circle cx="15" cy="20" r="1"/><circle cx="7" cy="20" r="1"/></svg>
                <span>Pesanan Saya</span>
            </a>
            <a href="#" data-info="favorit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                <span>Produk Favorit</span>
            </a>
        </nav>
        <div class="spacer"></div>
        <nav class="nav bottom">
            <a href="#" data-info="bantuan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9.09 9 3 3a22 22 0 0 0 2 3"/><path d="M12 17h.01"/></svg>
                <span>Bantuan</span>
            </a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <header class="topbar">
            <div class="title">
                <h1>Katalog Produk</h1>
                <p>Temukan pakaian sesuai kebutuhan dan kebutuhanmu.</p>
            </div>
            <div class="tools">
                <button class="ic alert" aria-label="Notifikasi">🔔</button>
                <button class="ic" id="theme" aria-label="Mode gelap">☾</button>
                <button type="button" class="user" id="profileBtn" aria-haspopup="dialog" aria-expanded="false">
                    <span class="avatar">{{ Str::upper(Str::substr(Auth::user()->name ?? 'P', 0, 1)) }}</span>
                    <span>
                        <b>{{ Str::limit(Auth::user()->name ?? 'Pelanggan', 14) }}</b>
                        <small>{{ ucfirst(Auth::user()->role ?? 'pelanggan') }}</small>
                    </span>
                </button>
            </div>
        </header>

        @if(session('success'))
            <div class="flash ok"><span>{{ session('success') }}</span><button type="button" onclick="this.parentElement.remove()">✕</button></div>
        @endif
        @if(session('error'))
            <div class="flash err"><span>{{ session('error') }}</span><button type="button" onclick="this.parentElement.remove()">✕</button></div>
        @endif

        <div class="content">
            <!-- FILTER -->
            <aside class="filters">
                <button class="acc" type="button" data-acc="accKategori">Kategori <span>⌄</span></button>
                <div class="checks" id="accKategori">
                    <label><input type="checkbox" data-kategori="__all__" checked> Semua Kategori</label>
                    @foreach($categories as $categoryName)
                        <label><input type="checkbox" data-kategori="{{ $categoryName }}"> {{ $categoryName }}</label>
                    @endforeach
                </div>

                <button class="acc" type="button" data-acc="accHarga">Harga Produk <span>⌄</span></button>
                <div class="checks" id="accHarga">
                    <label><input type="checkbox" value="all" data-range="all" checked> Semua Harga</label>
                    <label><input type="checkbox" value="0-100000" data-range="0-100000"> Di bawah Rp 100.000</label>
                    <label><input type="checkbox" value="100000-250000" data-range="100000-250000"> Rp 100.000 - 250.000</label>
                    <label><input type="checkbox" value="250000-500000" data-range="250000-500000"> Rp 250.000 - 500.000</label>
                    <label><input type="checkbox" value="500000-1000000" data-range="500000-1000000"> Rp 500.000 - 1.000.000</label>
                </div>
                <div class="range-title">Custom Price Range :</div>
                <div class="slider">
                    <span class="bubble" id="bubble">Rp 1.000.000</span>
                    <div class="track"></div>
                    <div class="fill" id="fill"></div>
                    <input type="range" id="rMin" min="0" max="1000000" value="0" aria-label="Harga minimum">
                    <input type="range" id="rMax" min="0" max="1000000" value="1000000" aria-label="Harga maksimum">
                </div>
                <div class="minmax">
                    <input id="iMin" value="Rp 0" aria-label="Harga minimum">
                    <span>—</span>
                    <input id="iMax" value="Rp 1.000.000" aria-label="Harga maksimum">
                </div>

                <button class="btn block" id="apply" type="button">Terapkan</button>
                <button class="btn block" id="reset" type="button" style="background:var(--field);color:var(--muted);margin-top:8px">Reset</button>
            </aside>

            <!-- LIST -->
            <section class="list">
                <div class="head">
                    <div class="crumb">
                        Katalog › <b id="crumbFilter">Semua Produk</b>
                        <span class="count">Menampilkan <b id="count">{{ $products->count() }}</b> produk</span>
                    </div>
                    <label class="search">🔍 <input id="search" placeholder="Cari..." aria-label="Cari produk"></label>
                </div>

                <div class="grid" id="grid">
                    @forelse($products as $product)
                        @php
                            $kategoriNama = $product->category->name ?? '';
                            $emoji = $emojiKategori[Str::lower($kategoriNama)] ?? '👕';
                        @endphp
                        <article class="card"
                                 data-harga="{{ (int) $product->price }}"
                                 data-nama="{{ Str::lower($product->name . ' ' . $kategoriNama . ' ' . ($product->type ?? '') . ' ' . ($product->color ?? '')) }}"
                                 data-kategori="{{ $kategoriNama }}">
                            <div class="thumb">
                                @if($product->image)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                @else
                                    {{ $emoji }}
                                @endif
                            </div>
                            <h3>{{ $product->name }}</h3>
                            <p>{{ $kategoriNama ?: 'Tanpa kategori' }}{{ $product->type ? ' · ' . $product->type : '' }}{{ $product->color ? ' · ' . $product->color : '' }}</p>
                            <div class="foot">
                                <b>Rp {{ number_format($product->price, 0, ',', '.') }}</b>
                                <span class="mini{{ $product->stock > 0 ? '' : ' out' }}">{{ $product->stock }} Pcs</span>
                            </div>
                            <button class="buy" type="button" data-buy="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ (int) $product->price }}"
                                    data-stock="{{ (int) $product->stock }}"
                                    @disabled($product->stock <= 0)>
                                {{ $product->stock > 0 ? 'Beli Sekarang' : 'Stok Habis' }}
                            </button>
                        </article>
                    @empty
                        <div class="empty">Belum ada produk yang tersedia di katalog saat ini.</div>
                    @endforelse
                    <div class="empty" id="noMatch" style="display:none">Produk tidak ditemukan. Ubah filter harga atau kata kunci.</div>
                </div>
            </section>
        </div>
    </main>
</div>

<!-- MODAL PEMBELIAN -->
<div class="modal-b" id="buyModal" role="dialog" aria-modal="true" aria-labelledby="buyTitle">
    <div class="modal-box">
        <h2 id="buyTitle">Konfirmasi Pembelian</h2>
        <div class="rowline"><span>Produk</span><b id="mName">-</b></div>
        <div class="rowline"><span>Harga Satuan</span><b id="mPrice">-</b></div>
        <label for="mQty">Jumlah</label>
        <input type="number" id="mQty" min="1" value="1">
        <label for="mPay">Metode Pembayaran</label>
        <select id="mPay">
            <option value="E-Wallet">E-Wallet</option>
            <option value="M-Banking">M-Banking</option>
            <option value="Transfer Bank">Transfer Bank</option>
            <option value="Cash">Cash</option>
        </select>
        <div class="rowline" style="margin-top:14px;border-bottom:0"><span>Total Bayar</span><b id="mTotal">-</b></div>
        <form action="" method="POST" id="buyForm" style="display:none">
            @csrf
            <input type="hidden" name="quantity" id="mQtyHidden" value="1">
            <input type="hidden" name="payment_method" id="mPayHidden" value="E-Wallet">
        </form>
        <div class="modal-act">
            <button class="btn ghost" type="button" id="buyCancel">Batal</button>
            <button class="btn" type="button" id="buyConfirm">Konfirmasi Pembelian</button>
        </div>
    </div>
</div>

<!-- MODAL INFO (Keranjang / Favorit / Bantuan) -->
<div class="modal-b" id="infoModal" role="dialog" aria-modal="true" aria-labelledby="infoTitle">
    <div class="modal-box">
        <div class="modal-head">
            <h2 id="infoTitle">Informasi</h2>
            <button type="button" class="x" id="infoClose" aria-label="Tutup">✕</button>
        </div>
        <div class="info-ico" id="infoIcon"></div>
        <p class="info-text" id="infoText"></p>
        <div class="modal-act">
            <button type="button" class="btn ghost" id="infoOk">Mengerti</button>
        </div>
    </div>
</div>

<!-- MODAL PROFIL AKUN -->
<div class="modal-b" id="profileModal" role="dialog" aria-modal="true" aria-labelledby="profileTitle">
    <div class="modal-box">
        <div class="modal-head">
            <h2 id="profileTitle">Profil Saya</h2>
            <button type="button" class="x" id="profileClose" aria-label="Tutup profil">✕</button>
        </div>

        <div class="profile-top">
            <span class="avatar">{{ Str::upper(Str::substr(Auth::user()->name ?? 'P', 0, 1)) }}</span>
            <h3>{{ Auth::user()->name ?? 'Pelanggan' }}</h3>
            <p>{{ Auth::user()->email ?? '-' }}</p>
            <span class="role-badge">{{ ucfirst(Auth::user()->role ?? 'pelanggan') }}</span>
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn danger block">Keluar dari Akun</button>
        </form>
    </div>
</div>

<!-- Floating Chatbot Widget -->
<div id="chatbotWidget" class="chatbot-widget">
    <button type="button" class="chatbot-fab" id="chatbotFab" aria-label="Buka Chatbot" onclick="toggleChatbot()">
        <span class="chatbot-fab-icon-open" id="fabIconOpen">💬</span>
        <span class="chatbot-fab-icon-close" id="fabIconClose" style="display:none">✕</span>
    </button>

    <div class="chatbot-window" id="chatbotWindow" role="dialog" aria-label="Asisten Virtual TPS" style="display:none">
        <div class="chatbot-header d-flex align-items-center justify-content-between">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:1.4rem">🤖</span>
                <div>
                    <div style="font-weight:700;font-size:.9rem">Asisten Virtual TPS</div>
                    <small style="opacity:.75">Siap membantu Anda 24/7</small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-light" style="color:#0d6efd" onclick="toggleChatbot()" aria-label="Tutup Chatbot">✕</button>
        </div>

        <div class="chatbot-messages" id="chatbotMessages" role="log" aria-live="polite">
            <div class="chatbot-message bot">
                <div class="message-bubble">
                    <p style="margin:0">Halo! 👋 Saya asisten virtual TPS Pakaian. Ada yang bisa saya bantu?</p>
                    <small>Tanyakan tentang: jam operasional, cara beli, pembayaran, retur, pengiriman, ukuran, promo, atau kontak CS.</small>
                </div>
            </div>
        </div>

        <div class="chatbot-input-area">
            <form id="chatbotForm" onsubmit="sendMessage(event)">
                <input type="text" id="chatbotInput" placeholder="Ketik pertanyaan Anda..." autocomplete="off" required aria-label="Pertanyaan Anda">
                <button type="submit" class="send" id="chatbotSendBtn" disabled>Kirim ➤</button>
            </form>
            <div style="margin-top:12px" id="quickActions">
                <small style="color:#6c757d;display:block;margin-bottom:6px">Cepat tanyakan:</small>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    <button type="button" class="quick-action" data-query="jam operasional">🕐 Jam Buka</button>
                    <button type="button" class="quick-action" data-query="cara beli">🛍️ Cara Beli</button>
                    <button type="button" class="quick-action" data-query="metode pembayaran">💳 Bayar</button>
                    <button type="button" class="quick-action" data-query="pengembalian">🔄 Retur</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const $ = (s) => document.querySelector(s);
    const $all = (s) => Array.from(document.querySelectorAll(s));
    const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
    const BUY_URL = @json(route('toko.buy', ['product_id' => 0]));
    const MAX_HARGA = 1000000;

    /* ================= FILTER KATALOG ================= */
    const state = { q: '', min: 0, max: MAX_HARGA, ranges: ['all'], kategori: ['__all__'] };
    const cards = () => $all('#grid .card');
    const noMatch = $('#noMatch');

    function applyFilter() {
        const q = state.q.toLowerCase().trim();
        let visible = 0;

        cards().forEach((card) => {
            const harga = Number(card.dataset.harga) || 0;
            const nama = card.dataset.nama || '';
            const kategori = card.dataset.kategori || '';

            const cocokTeks = !q || nama.includes(q);
            const cocokHarga = harga >= state.min && harga <= state.max
                && (state.ranges.includes('all')
                    || state.ranges.some((r) => {
                        const [a, b] = r.split('-').map(Number);
                        return harga >= a && harga <= b;
                    }));
            const cocokKategori = state.kategori.includes('__all__') || state.kategori.includes(kategori);

            const tampil = cocokTeks && cocokHarga && cocokKategori;
            card.style.display = tampil ? '' : 'none';
            if (tampil) visible++;
        });

        $('#count').textContent = visible;
        if (noMatch) noMatch.style.display = visible === 0 ? '' : 'none';
    }

    /* Checkbox kategori */
    $all('#accKategori input').forEach((box) => {
        box.addEventListener('change', () => {
            const boxes = $all('#accKategori input');
            if (box.dataset.kategori === '__all__' && box.checked) {
                boxes.slice(1).forEach((b) => (b.checked = false));
            } else if (box.dataset.kategori !== '__all__') {
                boxes[0].checked = false;
            }
            if (!boxes.some((b) => b.checked)) boxes[0].checked = true;

            state.kategori = boxes.filter((b) => b.checked).map((b) => b.dataset.kategori);
            applyFilter();
        });
    });

    /* Checkbox rentang harga */
    $all('#accHarga input').forEach((box) => {
        box.addEventListener('change', () => {
            const boxes = $all('#accHarga input');
            if (box.value === 'all' && box.checked) boxes.slice(1).forEach((b) => (b.checked = false));
            else if (box.value !== 'all') boxes[0].checked = false;
            if (!boxes.some((b) => b.checked)) boxes[0].checked = true;

            state.ranges = boxes.filter((b) => b.checked).map((b) => b.value);
            applyFilter();
        });
    });

    /* Slider custom range */
    function syncSlider() {
        let a = +$('#rMin').value;
        let b = +$('#rMax').value;
        if (a > b) [a, b] = [b, a];

        const pct = (v) => (v / MAX_HARGA) * 100;
        $('#fill').style.left = pct(a) + '%';
        $('#fill').style.width = pct(b) - pct(a) + '%';
        $('#bubble').style.left = pct(b) + '%';
        $('#bubble').textContent = rupiah(b);
        $('#iMin').value = rupiah(a);
        $('#iMax').value = rupiah(b);
        return [a, b];
    }
    ['#rMin', '#rMax'].forEach((s) => $(s).addEventListener('input', syncSlider));
    ['#iMin', '#iMax'].forEach((s) => $(s).addEventListener('change', () => {
        const parse = (v) => Math.min(MAX_HARGA, Math.max(0, parseInt(String(v).replace(/\D/g, ''), 10) || 0));
        $('#rMin').value = parse($('#iMin').value);
        $('#rMax').value = parse($('#iMax').value);
        syncSlider();
    }));

    $('#apply').addEventListener('click', () => {
        const [a, b] = syncSlider();
        state.min = a;
        state.max = b;
        applyFilter();
    });

    $('#reset').addEventListener('click', () => {
        state.q = '';
        state.min = 0;
        state.max = MAX_HARGA;
        state.ranges = ['all'];
        state.kategori = ['__all__'];
        $('#rMin').value = 0;
        $('#rMax').value = MAX_HARGA;
        $('#search').value = '';
        $all('#accHarga input').forEach((b) => (b.checked = b.value === 'all'));
        $all('#accKategori input').forEach((b) => (b.checked = b.dataset.kategori === '__all__'));
        syncSlider();
        applyFilter();
    });

    /* Search katalog (satu-satunya input, di header area katalog) */
    $('#search').addEventListener('input', (e) => {
        state.q = e.target.value;
        applyFilter();
    });

    /* Accordion filter */
    $all('[data-acc]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const panel = document.getElementById(btn.dataset.acc);
            const tampil = panel.style.display !== 'none';
            panel.style.display = tampil ? 'none' : 'flex';
        });
    });

    /* ================= MODAL PEMBELIAN ================= */
    let buyId = null;
    let buyUnit = 0;
    let buyStock = 0;

    $all('#grid .buy').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            buyId = btn.dataset.buy;
            buyUnit = Number(btn.dataset.price) || 0;
            buyStock = Number(btn.dataset.stock) || 0;

            $('#mName').textContent = btn.dataset.name;
            $('#mPrice').textContent = rupiah(buyUnit);
            $('#mQty').value = 1;
            $('#mQty').max = buyStock;
            $('#mPay').value = 'E-Wallet';
            updateTotal();

            $('#buyForm').action = BUY_URL + buyId;
            $('#buyModal').classList.add('show');
        });
    });

    function updateTotal() {
        const qty = Math.max(1, Math.min(+$('#mQty').value || 1, buyStock || 1));
        $('#mTotal').textContent = rupiah(buyUnit * qty);
        $('#mQtyHidden').value = qty;
        $('#mPayHidden').value = $('#mPay').value;
    }
    $('#mQty').addEventListener('input', updateTotal);
    $('#mPay').addEventListener('change', updateTotal);
    $('#buyCancel').addEventListener('click', () => $('#buyModal').classList.remove('show'));
    $('#buyModal').addEventListener('click', (e) => { if (e.target.id === 'buyModal') $('#buyModal').classList.remove('show'); });
    $('#buyConfirm').addEventListener('click', () => { updateTotal(); $('#buyForm').submit(); });

    /* ================= MODAL INFO SIDEBAR ================= */
    // Menu Overview/Pesanan Saya punya route nyata, jadi link-nya dipakai langsung.
    // Keranjang, Favorit, dan Bantuan belum punya tabel/backend, jadi diberi tahu lewat modal
    // alih-alih link "#" yang diam-diam tidak melakukan apa-apa.
    const svgIcon = (paths) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    const infoContent = {
        cart: {
            title: 'Keranjang Belanja',
            icon: svgIcon('<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>'),
            text: 'Fitur keranjang masih dalam pengembangan. Untuk sekarang, setiap produk bisa langsung dibeli lewat tombol "Beli Sekarang" pada kartunya.',
        },
        favorit: {
            title: 'Produk Favorit',
            icon: svgIcon('<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>'),
            text: 'Fitur produk favorit masih dalam pengembangan. Sementara ini, gunakan filter kategori dan pencarian untuk menemukan produk yang kamu suka.',
        },
        bantuan: {
            title: 'Pusat Bantuan',
            icon: svgIcon('<circle cx="12" cy="12" r="10"/><path d="m9.09 9 3 3a22 22 0 0 0 2 3"/><path d="M12 17h.01"/>'),
            text: 'Butuh bantuan? Klik ikon asisten virtual di pojok kanan bawah untuk jawaban otomatis tentang jam buka, cara membeli, pembayaran, retur, dan pengiriman.',
        },
    };

    const infoModal = $('#infoModal');
    $all('a[data-info]').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const data = infoContent[link.dataset.info];
            if (!data) return;
            $('#infoTitle').textContent = data.title;
            $('#infoIcon').innerHTML = data.icon;
            $('#infoText').textContent = data.text;
            infoModal.classList.add('show');
        });
    });
    $('#infoClose').addEventListener('click', () => infoModal.classList.remove('show'));
    $('#infoOk').addEventListener('click', () => infoModal.classList.remove('show'));
    infoModal.addEventListener('click', (e) => { if (e.target.id === 'infoModal') infoModal.classList.remove('show'); });

    /* ================= MODAL PROFIL AKUN ================= */
    const profileBtn = $('#profileBtn');
    const profileModal = $('#profileModal');

    function openProfile() {
        profileModal.classList.add('show');
        profileBtn.setAttribute('aria-expanded', 'true');
    }
    function closeProfile() {
        profileModal.classList.remove('show');
        profileBtn.setAttribute('aria-expanded', 'false');
    }

    profileBtn.addEventListener('click', openProfile);
    $('#profileClose').addEventListener('click', closeProfile);
    profileModal.addEventListener('click', (e) => { if (e.target.id === 'profileModal') closeProfile(); });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        $('#buyModal').classList.remove('show');
        infoModal.classList.remove('show');
        closeProfile();
    });

    /* ================= DARK MODE ================= */
    $('#theme').addEventListener('click', () => {
        const r = document.documentElement.style;

        // Palet gelap high-contrast: latar biru-slat solid, teks terang,
        // border cukup tebal supaya kartu dan input tidak "tenggelam".
        const dark = {
            '--bg':'#121827', '--card':'#1f2937', '--ink':'#f3f4f6', '--muted':'#9ca3af',
            '--line':'#374151', '--field':'#263244', '--tile':'#2b3648',
            '--accent':'#3b82f6', '--accent-soft':'#1e3a8a', '--on-accent':'#ffffff',
            '--alert':'#7f1d1d', '--danger-soft':'#7f1d1d', '--danger':'#fca5a5',
        };
        const light = {
            '--bg':'#f5f4f1', '--card':'#fff', '--ink':'#151515', '--muted':'#8a8a8a',
            '--line':'#ececec', '--field':'#f3f3f1', '--tile':'#f0f1f7',
            '--accent':'#2563eb', '--accent-soft':'#e8f0fe', '--on-accent':'#fff',
            '--alert':'#ffe9e0', '--danger-soft':'#ffe0e0', '--danger':'#e5484d',
        };

        const isDark = r.getPropertyValue('--bg') === dark['--bg'];
        Object.entries(isDark ? light : dark).forEach(([k, v]) => r.setProperty(k, v));
    });

    /* ================= CHATBOT ================= */
    const chatbotFab = document.getElementById('chatbotFab');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const fabIconOpen = document.getElementById('fabIconOpen');
    const fabIconClose = document.getElementById('fabIconClose');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSendBtn = document.getElementById('chatbotSendBtn');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotForm = document.getElementById('chatbotForm');
    let isOpen = false;

    function toggleChatbot() {
        isOpen = !isOpen;
        chatbotWindow.style.display = isOpen ? 'flex' : 'none';
        fabIconOpen.style.display = isOpen ? 'none' : '';
        fabIconClose.style.display = isOpen ? '' : 'none';
        if (isOpen) chatbotInput.focus();
    }

    chatbotInput.addEventListener('input', function () {
        chatbotSendBtn.disabled = this.value.trim() === '';
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function addMessage(text, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chatbot-message ' + (isUser ? 'user' : 'bot');
        messageDiv.innerHTML = '<div class="message-bubble"><p style="margin:0">' + escapeHtml(text) + '</p></div>';
        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message bot';
        typingDiv.innerHTML = '<div class="message-bubble"><p style="margin:0">•••</p></div>';
        chatbotMessages.appendChild(typingDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        return typingDiv;
    }

    async function sendMessage(event) {
        event.preventDefault();
        const message = chatbotInput.value.trim();
        if (!message) return;

        addMessage(message, true);
        chatbotInput.value = '';
        chatbotSendBtn.disabled = true;

        const typingEl = showTypingIndicator();

        try {
            const response = await fetch('{{ route("chatbot.message") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();
            typingEl.remove();
            addMessage(data.reply, false);
        } catch (error) {
            typingEl.remove();
            addMessage('Maaf, terjadi kesalahan koneksi. Silakan coba lagi nanti.', false);
            console.error('Chatbot error:', error);
        }
    }

    document.querySelectorAll('.quick-action').forEach((btn) => {
        btn.addEventListener('click', function () {
            chatbotInput.value = this.dataset.query;
            chatbotSendBtn.disabled = false;
            sendMessage(new Event('submit'));
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) toggleChatbot();
    });

    chatbotFab.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleChatbot();
        }
    });

    /* Init */
    syncSlider();
    applyFilter();
</script>
</body>
</html>
