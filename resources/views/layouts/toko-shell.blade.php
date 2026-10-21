<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' : '' }}La Mamora - Toko Outfit Official</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/la-mamora.css') }}" rel="stylesheet">
    <link href="{{ asset('css/la-mamora-shop.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="shopSidebar">
        <div class="brand"><span class="logo">L</span>La Mamora.</div>
        <nav class="nav" aria-label="Navigasi utama">
            <a href="{{ route('toko.index') }}" @class(['active' => ($activeNav ?? '') === 'index'])>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>Overview</span>
            </a>
            <a href="{{ route('toko.index') }}" @class(['active' => ($activeNav ?? '') === 'katalog'])>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Katalog Produk</span>
            </a>
            <a href="#" data-info="cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <span>Keranjang Belanja</span>
            </a>
            <a href="{{ route('toko.pesanan') }}" @class(['active' => ($activeNav ?? '') === 'pesanan'])>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><rect width="13" height="8" x="3" y="4" rx="2"/><circle cx="15" cy="20" r="1"/><circle cx="7" cy="20" r="1"/></svg>
                <span>Pesanan Saya</span>
            </a>
            <a href="{{ route('toko.favorit') }}" @class(['active' => ($activeNav ?? '') === 'favorit'])>
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
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu navigasi" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
            </button>
            <div class="title">
                <h1>@yield('heading', $heading ?? 'La Mamora')</h1>
                <p>@yield('subheading', $subheading ?? '')</p>
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
            @yield('content')
        </div>
    </main>
</div>

{{-- Lapisan gelap penutup drawer navigasi di layar kecil --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- MODAL INFO (Keranjang / Bantuan) -->
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

    <div class="chatbot-window" id="chatbotWindow" role="dialog" aria-label="Asisten Virtual La Mamora" style="display:none">
        <div class="chatbot-header d-flex align-items-center justify-content-between">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:1.4rem">🤖</span>
                <div>
                    <div style="font-weight:700;font-size:.9rem">Asisten Virtual La Mamora</div>
                    <small style="opacity:.75">Siap membantu Anda 24/7</small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-light" style="color:#0d6efd" onclick="toggleChatbot()" aria-label="Tutup Chatbot">✕</button>
        </div>

        <div class="chatbot-messages" id="chatbotMessages" role="log" aria-live="polite">
            <div class="chatbot-message bot">
                <div class="message-bubble">
                    <p style="margin:0">Halo! 👋 Saya asisten virtual La Mamora. Ada yang bisa saya bantu?</p>
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
                <small style="color:var(--muted);display:block;margin-bottom:6px">Cepat tanyakan:</small>
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

    /* ================= DRAWER NAVIGASI (LAYAR KECIL) ================= */
    // Sidebar disembunyikan di layar <= 900px, jadi muncul sebagai drawer
    // yang dibuka lewat tombol menu di topbar.
    const sidebarToggle = $('#sidebarToggle');
    const sidebarOverlay = $('#sidebarOverlay');

    function setSidebar(open) {
        document.body.classList.toggle('sidebar-open', open);
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setSidebar(!document.body.classList.contains('sidebar-open'));
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => setSidebar(false));
    }

    /* ================= MODAL INFO SIDEBAR ================= */
    // Overview, Katalog, Pesanan Saya, dan Produk Favorit punya route nyata.
    // Keranjang dan Bantuan belum punya backend, jadi diberi tahu lewat modal.
    const svgIcon = (paths) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    const infoContent = {
        cart: {
            title: 'Keranjang Belanja',
            icon: svgIcon('<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>'),
            text: 'Fitur keranjang masih dalam pengembangan. Untuk sekarang, setiap produk bisa langsung dibeli lewat tombol "Beli Sekarang" pada kartunya.',
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
        const buyModal = $('#buyModal');
        if (buyModal) buyModal.classList.remove('show');
        infoModal.classList.remove('show');
        closeProfile();
    });

    /* ================= FAVORIT PRODUK ================= */
    // Tombol hati mengirim POST lewat fetch() lalu hanya menukar state tombol,
    // sehingga halaman tidak me-reload. Tombol dikunci selama request supaya
    // klik ganda tidak menyebabkan dua toggle beruntun.
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    $all('button[data-fav]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.disabled) return;
            btn.disabled = true;

            try {
                const res = await fetch(btn.dataset.fav, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                });

                if (!res.ok) throw new Error('Gagal menyimpan favorit');

                const data = await res.json();
                btn.setAttribute('aria-pressed', data.favorit ? 'true' : 'false');
            } catch (err) {
                console.error(err);
                alert('Favorit gagal disimpan. Silakan coba lagi.');
            } finally {
                btn.disabled = false;
            }
        });
    });

    /* ================= DARK MODE ================= */
    // Palet gelap memakai token La Mamora (navy/orange), disimpan sebagai
    // kelas .lm-dark pada elemen <html> supaya warnanya didefinisikan di CSS.
    $('#theme').addEventListener('click', () => {
        const root = document.documentElement;
        const gelap = root.classList.toggle('lm-dark');
        $('#theme').setAttribute('aria-label', gelap ? 'Mode terang' : 'Mode gelap');
        $('#theme').textContent = gelap ? '☀' : '☾';
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
            const response = await fetch(@json(route('chatbot.message')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
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
</script>

@stack('scripts')
</body>
</html>
