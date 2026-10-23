@props([
    'paginator' => null,
    'label' => 'Navigasi halaman',
])

@php
    $hasPages = $paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages();
@endphp

@if($hasPages)
    <nav aria-label="{{ $label }}" class="ui-pagination">
        <ul class="pagination ui-pagination-list">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a
                    class="page-link"
                    href="{{ $paginator->previousPageUrl() ?? '#' }}"
                    @if($paginator->onFirstPage()) tabindex="-1" aria-disabled="true" @endif
                    aria-label="Halaman sebelumnya"
                >
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
            </li>

            @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                    <a
                        class="page-link"
                        href="{{ $url }}"
                        @if($page === $paginator->currentPage()) aria-current="page" @endif
                    >{{ $page }}</a>
                </li>
            @endforeach

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a
                    class="page-link"
                    href="{{ $paginator->nextPageUrl() ?? '#' }}"
                    @if(! $paginator->hasMorePages()) tabindex="-1" aria-disabled="true" @endif
                    aria-label="Halaman berikutnya"
                >
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            </li>
        </ul>
    </nav>
@endif
