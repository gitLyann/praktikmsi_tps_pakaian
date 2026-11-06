@php
    $settingsRole = Auth::check() ? Auth::user()->role : null;
    $showSettingsModal = Auth::check() && in_array($settingsRole, ['staff', 'admin'], true);
@endphp
@if($showSettingsModal)
    {{-- Modal Pengaturan (global). Staff dashboard punya section #pengaturan sendiri;
         modal ini dipakai di semua halaman staff lain + admin. Dibuka via [data-open-settings]. --}}
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="settingsModalLabel"><i class="bi bi-gear me-2"></i>Pengaturan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Mengakhiri sesi aktif pada perangkat ini. Anda akan diarahkan kembali ke halaman login.
                    </p>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout Akun
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @once
        @push('scripts')
            <script>
                document.addEventListener('click', function (e) {
                    var trigger = e.target.closest('[data-open-settings]');
                    if (!trigger) return;
                    e.preventDefault();
                    var modalEl = document.getElementById('settingsModal');
                    if (!modalEl || typeof bootstrap === 'undefined') return;
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                });
            </script>
        @endpush
    @endonce
@endif
