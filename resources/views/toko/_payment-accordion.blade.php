@php
    /**
     * Akordeon metode pembayaran (simulasi).
     *
     * Variabel yang diharapkan:
     * - $paymentGroups : array dari App\Services\PaymentService::groups()
     * - $prefix (opsional): prefix id unik saat dipakai lebih dari sekali di halaman.
     *
     * Field sensitif (nomor kartu, CVV, nomor rekening) TIDAK memakai atribut
     * name sehingga tidak pernah ikut terkirim / tersimpan.
     */
    $prefix = $prefix ?? '';
    $firstGroup = true;
@endphp

<div class="pay-acc" data-pay-acc data-prefix="{{ $prefix }}">
    @foreach($paymentGroups as $group)
        @php $gid = $prefix . 'pay-' . $group['group_code']; @endphp
        <div class="pay-group{{ $firstGroup ? ' open' : '' }}" data-pay-group="{{ $gid }}">
            <button type="button" class="pay-head" data-pay-toggle="{{ $gid }}"
                    aria-expanded="{{ $firstGroup ? 'true' : 'false' }}" aria-controls="{{ $gid }}">
                <span class="pay-title">{{ $group['title'] }}</span>
                <span class="pay-caret" aria-hidden="true">⌄</span>
            </button>

            <div class="pay-body" id="{{ $gid }}" @if(! $firstGroup) hidden @endif>
                <p class="pay-desc">{{ $group['desc'] }}</p>

                @if(! empty($group['brands']))
                    <div class="pay-brands">
                        @foreach($group['brands'] as $brand)
                            <span class="pay-brand">{{ $brand }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="pay-options">
                    @foreach($group['items'] as $item)
                        <label class="pay-option">
                            <input type="radio" name="{{ $prefix }}payment_method" value="{{ $item['code'] }}"
                                   data-pay-option data-group="{{ $group['group_code'] }}"
                                   @if($group['form'] === 'phone') data-needs-phone="1" @endif
                                   @if($loop->first && $firstGroup) checked @endif>
                            <span>{{ $item['name'] }}</span>
                        </label>
                    @endforeach
                </div>

                @if($group['form'] === 'card')
                    <div class="pay-note">
                        Jenis kartu hanya untuk tampilan. Nomor kartu &amp; CVV tidak diketik dan tidak disimpan.
                    </div>
                @elseif($group['form'] === 'rekening')
                    <label class="pay-field" for="{{ $gid }}-rek">
                        Nomor Rekening (hanya tampilan)
                        <input type="text" id="{{ $gid }}-rek" inputmode="numeric" placeholder="1234567890" autocomplete="off">
                    </label>
                    <div class="pay-note">Nomor rekening tidak pernah dikirim atau disimpan.</div>
                @elseif($group['form'] === 'phone')
                    <label class="pay-field" for="{{ $gid }}-phone">
                        Nomor HP
                        <input type="tel" id="{{ $gid }}-phone" name="{{ $prefix }}phone" inputmode="numeric"
                               placeholder="08xxxxxxxxxx" autocomplete="off" data-pay-phone>
                    </label>
                    <div class="pay-note">Hanya 4 digit terakhir yang disimpan untuk verifikasi.</div>
                @endif
            </div>
        </div>
        @php $firstGroup = false; @endphp
    @endforeach

    @if(empty($paymentGroups))
        <div class="pay-note">Belum ada metode pembayaran aktif.</div>
    @endif
</div>
