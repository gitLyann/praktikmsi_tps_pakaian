@extends('layouts.app', [
    'title' => 'Penilaian Pemasok - DSS',
    'sidebar' => true,
    'activeRoute' => 'dss',
    'dashboardRoute' => 'staff.dashboard'
])

@section('content')
    @if(session('success'))
        <div class="mb-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $errorMessage)
                        <li>{{ $errorMessage }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Penilaian Pemasok (DSS)</h3>
            <p class="text-muted mb-0">
                Isi skor tiap pemasok untuk setiap kriteria. Bobot AHP di bawah dipakai otomatis
                admin saat menjalankan analisis VIKOR.
            </p>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
        </span>
    </div>

    {{-- Kriteria penilaian beserta bobot AHP --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Kriteria Penilaian &amp; Bobot AHP</h5>
        </div>
        <div class="card-body">
            @forelse($criteria as $criterion)
                <div class="lm-criterion mb-2">
                    <div class="min-w-0">
                        <div class="lm-criterion-name">{{ $criterion['code'] }} &mdash; {{ $criterion['name'] }}</div>
                        <div class="lm-criterion-type">
                            {{ $criterion['type'] === 'benefit' ? 'Benefit (nilai besar lebih baik)' : 'Cost (nilai kecil lebih baik)' }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="lm-score">{{ number_format($criterion['weight'], 4) }}</div>
                        <div class="lm-criterion-type">bobot</div>
                    </div>
                </div>
            @empty
                <div class="lm-empty">
                    <i class="bi bi-diagram-3"></i>
                    <div class="lm-empty-title">Belum ada kriteria penilaian</div>
                    <div class="lm-empty-text">Tambahkan kriteria pada tabel <code>dss_criteria</code> terlebih dahulu.</div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Form penilaian pemasok --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Form Penilaian</h5>
            @if($run)
                <span class="badge bg-light text-dark">
                    Run penilaian aktif: {{ $run->name }} (#{{ $run->id }})
                </span>
            @endif
        </div>
        <div class="card-body">
            @if($suppliers === [] || $criteria === [])
                <div class="lm-empty">
                    <i class="bi bi-truck"></i>
                    <div class="lm-empty-title">Data penilaian belum lengkap</div>
                    <div class="lm-empty-text">
                        Form penilaian membutuhkan minimal satu pemasok dan satu kriteria penilaian.
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('staff.dss.scores.store') }}">
                    @csrf
                    <div class="lm-table-wrap">
                        <table class="table table-bordered align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 220px;">Pemasok</th>
                                    @foreach($criteria as $criterion)
                                        <th class="text-center">
                                            {{ $criterion['code'] }}
                                            <div class="fw-normal small text-muted">{{ $criterion['name'] }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php($scoreIndex = 0)
                                @foreach($suppliers as $supplier)
                                    <tr>
                                        <th class="text-start">
                                            {{ $supplier['supplier_name'] }}
                                            <div class="fw-normal small text-muted">
                                                {{ $supplier['code'] }} &middot; lead time {{ $supplier['lead_time_days'] }} hari
                                                &middot; MOQ {{ $supplier['min_order_qty'] }}
                                            </div>
                                        </th>
                                        @foreach($supplier['scores'] as $score)
                                            <td>
                                                <input type="hidden" name="scores[{{ $scoreIndex }}][supplier_id]" value="{{ $supplier['supplier_id'] }}">
                                                <input type="hidden" name="scores[{{ $scoreIndex }}][criterion_id]" value="{{ $score['criterion_id'] }}">
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control form-control-sm"
                                                    name="scores[{{ $scoreIndex }}][score]"
                                                    value="{{ $score['score'] !== null ? $score['score'] : '' }}"
                                                    placeholder="-">
                                            </td>
                                            @php($scoreIndex++)
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-muted small mb-0">
                            Kosongkan sel yang belum dinilai. Skor memakai nilai mentah kriteria
                            (mis. harga dalam rupiah, lead time dalam hari, rating 1-5).
                        </p>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan Penilaian
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
