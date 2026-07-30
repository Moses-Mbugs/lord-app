@extends('layouts.finance.template')
@section('title', 'Upload — Customer Profitability')

@section('content')

<div class="page-header mb-4">
    <div>
        <h4 class="page-title">Customer Profitability</h4>
        <p class="text-muted small mb-0">Upload an Excel file to analyse customer revenue data</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-6">

        <h5 class="fw-semibold mb-1">Upload Profitability File</h5>
        <p class="text-muted small mb-4">
            Upload an Excel (.xlsx) file containing the <strong>YTD Profitability</strong>
            and <strong>Profitability per customer data</strong> sheets.
        </p>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form action="{{ route('finance.customer_profitability.upload.store') }}" method="POST"
                    enctype="multipart/form-data" id="uploadForm">
                    @csrf

                    <div id="dropZone" class="border border-2 rounded-3 text-center p-5 mb-3"
                        style="border-style:dashed !important; border-color:#dee2e6 !important; cursor:pointer; transition:.2s">
                        <div id="stateEmpty">
                            <div class="mb-2" style="font-size:2rem">📂</div>
                            <p class="fw-medium mb-1">Drag &amp; drop your file here</p>
                            <p class="text-muted small mb-3">Supports .xlsx and .xls up to 20 MB</p>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="fileInput.click()">Browse files</button>
                        </div>
                        <div id="stateReady" class="d-none">
                            <div class="mb-2" style="font-size:2rem">✅</div>
                            <p class="fw-medium mb-0" id="fileName"></p>
                            <p class="text-muted small mb-0" id="fileSize"></p>
                        </div>
                        <input type="file" id="fileInput" name="file" accept=".xlsx,.xls" class="d-none">
                    </div>

                    @error('file')
                        <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
                    @enderror

                    <div class="d-grid">
                        <button type="submit" id="submitBtn" class="btn btn-primary" disabled>
                            Upload &amp; Process
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($batches->count())
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3">Previous uploads</h6>
                    <div class="list-group list-group-flush">
                        @foreach ($batches as $b)
                            <a href="{{ route('finance.customer_profitability.dashboard', $b->id) }}"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    <p class="mb-0 fw-medium small">{{ $b->original_name }}</p>
                                    <p class="mb-0 text-muted" style="font-size:11px">
                                        {{ $b->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <span class="badge bg-dark rounded-pill small">View →</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

@endsection

@push('scripts')
<script>
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const submitBtn = document.getElementById('submitBtn');
    const stateEmpty = document.getElementById('stateEmpty');
    const stateReady = document.getElementById('stateReady');
    const fileName  = document.getElementById('fileName');
    const fileSize  = document.getElementById('fileSize');

    function setFile(file) {
        if (!file) return;
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        stateEmpty.classList.add('d-none');
        stateReady.classList.remove('d-none');
        submitBtn.disabled = false;
    }

    fileInput.addEventListener('change', () => setFile(fileInput.files[0]));
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.background = '#f8f9fa'; });
    dropZone.addEventListener('dragleave', () => dropZone.style.background = '');
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.style.background = '';
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            setFile(e.dataTransfer.files[0]);
        }
    });
</script>
@endpush
