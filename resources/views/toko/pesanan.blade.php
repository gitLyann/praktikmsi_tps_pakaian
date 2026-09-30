<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesanan Saya - TPS Pakaian</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f5f4f1; --card:#fff; --ink:#151515; --muted:#8a8a8a; --line:#ececec;
            --accent:#2563eb; --accent-soft:#e8f0fe; --field:#f3f3f1; --on-accent:#fff;
            --ok-soft:#e8f7ee; --ok:#1a7f43; --wait-soft:#fff1dc; --wait:#a35a00;
            --radius:18px;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Plus Jakarta Sans','Inter',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--ink);font-size:15px;line-height:1.5;-webkit-font-smoothing:antialiased}
        button{font:inherit;cursor:pointer;border:0;background:none;color:inherit}
        :focus-visible{outline:2px solid var(--accent);outline-offset:2px}
        .wrap{max-width:940px;margin:0 auto;padding:22px 16px 60px}

        .top{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:20px}
        .back{display:inline-flex;align-items:center;gap:8px;text-decoration:none;color:var(--accent);font-weight:600;font-size:14.5px;padding:9px 16px;border-radius:99px;background:var(--accent-soft)}
        .back:hover{filter:brightness(.96)}
        .back svg{width:17px;height:17px;stroke-width:2.1}
        .top h1{margin-left:auto;font-size:22px;font-weight:700;letter-spacing:-.02em}
        .lead{color:var(--muted);font-size:13.5px;margin-top:2px}

        .summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
        .sum{background:var(--card);border-radius:15px;padding:15px 17px;border:1px solid var(--line)}
        .sum small{display:block;font-size:12.5px;color:var(--muted);font-weight:600;margin-bottom:5px}
        .sum b{font-size:22px;font-weight:700;letter-spacing:-.02em}
        .sum.accent{background:var(--accent);border-color:var(--accent);color:var(--on-accent)}
        .sum.accent small{color:rgba(255,255,255,.82)}

        .list{display:flex;flex-direction:column;gap:13px}
        .item{background:var(--card);border-radius:16px;border:1px solid var(--line);padding:17px 19px}
        .item-head{display:flex;align-items:center;gap:11px;flex-wrap:wrap;padding-bottom:13px;border-bottom:1px solid var(--line)}
        .item-head b{font-size:15px}
        .item-head .no{font-size:12.5px;color:var(--muted);margin-left:auto}
        .badge{display:inline-flex;align-items:center;gap:5px;font-size:12.5px;font-weight:700;padding:5px 12px;border-radius:99px;text-transform:capitalize}
        .badge.completed{background:var(--ok-soft);color:var(--ok)}
        .badge.processing{background:var(--wait-soft);color:var(--wait)}
        .badge.pending{background:var(--field);color:var(--muted)}
        .badge.cancelled{background:#ffe0e0;color:#e5484d}
        .badge i{width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block}
        .item-body{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;padding-top:14px}
        .meta{display:flex;flex-direction:column;gap:3px}
        .meta small{font-size:12.5px;color:var(--muted);font-weight:600}
        .meta b{font-size:14.5px;font-weight:600}
        .total{margin-left:auto;text-align:right}
        .total small{font-size:12.5px;color:var(--muted);font-weight:600;display:block}
        .total b{font-size:20px;font-weight:700;letter-spacing:-.02em}

        .empty{background:var(--card);border-radius:18px;border:1px dashed var(--line);text-align:center;padding:56px 20px}
        .empty .ico{width:62px;height:62px;border-radius:18px;background:var(--accent-soft);color:var(--accent);display:grid;place-items:center;margin:0 auto 17px}
        .empty .ico svg{width:28px;height:28px;stroke-width:1.8}
        .empty h2{font-size:18px;font-weight:700;margin-bottom:7px}
        .empty p{color:var(--muted);font-size:14.5px;margin-bottom:20px}
        .btn{display:inline-block;background:var(--accent);color:var(--on-accent);font-weight:600;border-radius:99px;padding:12px 22px;font-size:14.5px;text-decoration:none}
        .btn:hover{filter:brightness(.95)}
    </style>
</head>
<body>

@php
    // Peta status transaksi ke label bahasa manusia
    $labelStatus = [
        'pending' => 'Menunggu Pembayaran',
        'processing' => 'Diproses',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

<div class="wrap">
    <div class="top">
        <a href="{{ route('toko.index') }}" class="back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Kembali ke Katalog
        </a>
        <div style="text-align:right">
            <h1>Pesanan Saya</h1>
            <div class="lead">Riwayat &amp; status pesanan {{ Auth::user()->name ?? '' }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="sum">
            <small>Total Pesanan</small>
            <b>{{ $transactions->count() }}</b>
        </div>
        <div class="sum">
            <small>Selesai</small>
            <b>{{ $transactions->where('status', 'completed')->count() }}</b>
        </div>
        <div class="sum">
            <small>Sedang Diproses</small>
            <b>{{ $transactions->whereIn('status', ['pending', 'processing'])->count() }}</b>
        </div>
        <div class="sum accent">
            <small>Total Belanja</small>
            <b>Rp {{ number_format($transactions->sum('total'), 0, ',', '.') }}</b>
        </div>
    </div>

    <div class="list">
        @forelse($transactions as $trx)
            <article class="item">
                <div class="item-head">
                    <b>#{{ str_pad($trx->id, 5, '0', STR_PAD_LEFT) }}</b>
                    <span class="badge {{ $trx->status }}"><i></i>{{ $labelStatus[$trx->status] ?? ucfirst($trx->status) }}</span>
                    <span class="no">{{ $trx->date?->format('d M Y · H:i') ?? '-' }}</span>
                </div>
                <div class="item-body">
                    <div class="meta">
                        <small>Metode Pembayaran</small>
                        <b>{{ $trx->payment_method ?: '-' }}</b>
                    </div>
                    <div class="total">
                        <small>Total Bayar</small>
                        <b>Rp {{ number_format($trx->total, 0, ',', '.') }}</b>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty">
                <div class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><rect width="13" height="8" x="3" y="4" rx="2"/><circle cx="15" cy="20" r="1"/><circle cx="7" cy="20" r="1"/></svg>
                </div>
                <h2>Belum ada pesanan</h2>
                <p>Pesanan yang kamu buat akan tampil di sini lengkap dengan status dan riwayat pembayarannya.</p>
                <a href="{{ route('toko.index') }}" class="btn">Mulai Belanja</a>
            </div>
        @endforelse
    </div>
</div>

</body>
</html>
