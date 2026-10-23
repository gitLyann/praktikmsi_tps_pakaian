@props([
    'id',
    'title' => null,
    'icon' => null,
    'size' => null,
    'labelledby' => null,
])

@php
    $titleId = $labelledby ?? ($id . 'Label');
@endphp

<div class="modal fade ui-modal" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $titleId }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $size ? 'modal-' . $size : '' }}">
        <div class="modal-content">
            <div class="modal-header ui-modal-head">
                <h5 class="modal-title" id="{{ $titleId }}">
                    @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
                    {{ $title }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body ui-modal-body">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="modal-footer ui-modal-foot">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
