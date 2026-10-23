@extends('layouts.toko-shell', [
    'title' => 'Pesanan Saya',
    'activeNav' => 'pesanan',
    'heading' => 'Pesanan Saya',
    'subheading' => 'Riwayat & status pesanan ' . (Auth::user()->name ?? ''),
])

@section('content')
    @php
        // Peta status transaksi ke label bahasa manusia
        $labelStatus = [
            'pending' => 'Menunggu Pembayaran',
            'processing' => 'Diproses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
    @endphp
    <a href="{{ route('toko.index') }}" class="back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        Kembali ke Katalog
    </a>

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
                    @if($trx->payment && $trx->payment->status === 'pending')
                        <span class="badge pending"><i></i>Menunggu Pembayaran</span>
                    @endif
                    <span class="no">{{ $trx->date?->format('d M Y · H:i') ?? '-' }}</span>
                </div>
                <div class="item-body">
                    <div class="meta">
                        <small>Metode Pembayaran</small>
                        <b>{{ $trx->payment_method_label }}</b>
                    </div>
                    <div class="total">
                        <small>Total Bayar</small>
                        <b>Rp {{ number_format($trx->total, 0, ',', '.') }}</b>
                    </div>
                    @if($trx->payment && $trx->payment->reference_code)
                        <div class="meta">
                            <small>{{ $trx->payment->status === 'pending' ? 'Kode Pembayaran' : 'Referensi' }}</small>
                            <b class="mono">{{ $trx->payment->reference_code }}</b>
                        </div>
                    @endif
                    @if($trx->payment && $trx->payment->phone_last4)
                        <div class="meta">
                            <small>Nomor HP</small>
                            <b>••••{{ $trx->payment->phone_last4 }}</b>
                        </div>
                    @endif
                    @if($trx->payment && $trx->payment->status === 'pending' && $trx->payment->expires_at)
                        <div class="meta">
                            <small>Batas Bayar</small>
                            <b>{{ $trx->payment->expires_at->format('d M Y · H:i') }}</b>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty ico">
                <div class="ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><rect width="13" height="8" x="3" y="4" rx="2"/><circle cx="15" cy="20" r="1"/><circle cx="7" cy="20" r="1"/></svg>
                </div>
                <h2>Belum ada pesanan</h2>
                <p>Pesanan yang kamu buat akan tampil di sini lengkap dengan status dan riwayat pembayarannya.</p>
                <a href="{{ route('toko.index') }}" class="btn">Mulai Belanja</a>
            </div>
        @endforelse
    </div>
@endsection
