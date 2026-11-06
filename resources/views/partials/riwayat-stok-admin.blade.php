{{-- Shared Riwayat Stok UI (admin dashboard section / staff page).
     $auditTrail: Collection<RestockRequest>. $canMutate: bool (admin only, default false). --}}
@php $canMutate = $canMutate ?? false; @endphp
<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Riwayat Pengajuan &amp; Status Approval</h5>
        <span class="badge bg-light text-dark">{{ $auditTrail->count() }} catatan</span>
    </div>
    <div class="card-body">
        <x-ui.table label="Riwayat pengajuan restock">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Pengaju (Staff)</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Status Approval</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditTrail as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="fw-semibold">{{ $log->product->name ?? 'Produk N/A' }}</td>
                            <td>
                                {{ $log->employee->name ?? 'Staff N/A' }}
                                @if(optional($log->employee)->position)
                                    <div class="text-muted small">{{ $log->employee->position }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $log->jumlah_restock }} pcs</td>
                            <td class="text-center">
                                @if($log->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($log->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $log->catatan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada aktivitas restock tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.table>
    </div>
</div>
