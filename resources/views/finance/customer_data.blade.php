@extends('layouts.finance.template')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

    <style>
        .customer-import-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .customer-import-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .customer-import-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .progress {
            height: 20px;
            border-radius: 999px;
            overflow: hidden;
            background: #e9ecef;
        }

        .progress-bar {
            transition: width 0.35s ease;
        }

        .table thead th {
            white-space: nowrap;
            vertical-align: middle;
        }

        .table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .search-wrapper {
            max-width: 360px;
        }

        .import-summary {
            font-size: 13px;
            margin-top: 8px;
        }
    </style>

    <div class="container-fluid mt-4">
        <div class="card customer-import-card">
            <div class="card-body">
                <div class="customer-import-header mb-3">
                    <div>
                        <h4 class="mb-1">Customer Accounts Import</h4>
                        <p class="text-muted mb-0">View imported customer data and run Flexcube customer imports.</p>
                    </div>

                    <div class="customer-import-actions">
                        <button type="button" class="btn btn-primary import-btn" data-script="INDI_CUSTOMERS">
                            Import Individuals
                        </button>

                        <button type="button" class="btn btn-success import-btn" data-script="CORP_CUSTOMERS">
                            Import Corporates
                        </button>

                        <button type="button" class="btn btn-dark import-btn" data-script="ALL">
                            Import All
                        </button>
                    </div>
                </div>

                <div id="importAlert" style="display:none;" class="alert"></div>

                <div id="progressWrapper" style="display:none;" class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span id="progressLabel">Running import...</span>
                        <span id="progressPercent">0%</span>
                    </div>

                    <div class="progress">
                        <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                            role="progressbar" style="width: 0%"></div>
                    </div>

                    <div id="importSummary" class="import-summary text-muted"></div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 search-wrapper">
                        <label for="customSearch" class="form-label fw-bold">Search Customer Data</label>
                        <input type="text" id="customSearch" class="form-control"
                            placeholder="Search CIF, account, name, branch, phone, email...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="customerDataTable" class="table table-bordered table-striped table-hover w-100">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>CIF</th>
                                <th>Customer A/C No</th>
                                <th>Account Description</th>
                                <th>Branch</th>
                                <th>Telephone</th>
                                <th>Email</th>
                                <th>LCY Balance</th>
                                <th>Withdrawable Bal</th>
                                <th>Status</th>
                                <th>Open Date</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {
            let importInterval = null;

            const table = $('#customerDataTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [12, 'desc']
                ],
                dom: 'lrtip',
                ajax: {
                    url: "{{ route('finance.customer-imports.data') }}",
                    type: "GET"
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'cust_category',
                        name: 'cust_category'
                    },
                    {
                        data: 'f12_cif',
                        name: 'f12_cif'
                    },
                    {
                        data: 'cust_ac_no',
                        name: 'cust_ac_no'
                    },
                    {
                        data: 'ac_desc',
                        name: 'ac_desc'
                    },
                    {
                        data: 'branch_code',
                        name: 'branch_code'
                    },
                    {
                        data: 'telephone',
                        name: 'telephone'
                    },
                    {
                        data: 'e_mail',
                        name: 'e_mail'
                    },
                    {
                        data: 'lcy_curr_balance',
                        name: 'lcy_curr_balance'
                    },
                    {
                        data: 'acy_withdrawable_bal',
                        name: 'acy_withdrawable_bal'
                    },
                    {
                        data: 'record_stat',
                        name: 'record_stat'
                    },
                    {
                        data: 'ac_open_date',
                        name: 'ac_open_date'
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at'
                    }
                ]
            });

            $('#customSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            function showAlert(type, message) {
                const alertBox = $('#importAlert');
                alertBox.removeClass('alert-success alert-danger alert-warning alert-info')
                    .addClass('alert alert-' + type)
                    .html(message)
                    .show();
            }

            function setProgress(value, label) {
                $('#progressWrapper').show();
                $('#progressLabel').text(label || 'Running import...');
                $('#progressPercent').text(value + '%');
                $('#importProgressBar').css('width', value + '%');
            }

            function startFakeProgress(scriptName) {
                let progress = 0;
                $('#importSummary').html('');
                setProgress(0, 'Running ' + scriptName + ' import...');
                clearInterval(importInterval);

                importInterval = setInterval(function() {
                    if (progress < 90) {
                        progress += Math.floor(Math.random() * 10) + 3;
                        if (progress > 90) progress = 90;
                        setProgress(progress, 'Saving customer records to database...');
                    }
                }, 600);
            }

            function stopFakeProgress(success, response) {
                clearInterval(importInterval);
                setProgress(100, success ? 'Import completed successfully.' : 'Import failed.');

                if (response) {
                    if (response.results && Array.isArray(response.results)) {
                        let html = '';
                        response.results.forEach(function(item) {
                            html += '<div><strong>' + item.script_name + ':</strong> ' +
                                'Inserted: ' + (item.inserted || 0) + ', ' +
                                'Updated: ' + (item.updated || 0) + ', ' +
                                'Skipped: ' + (item.skipped || 0) + ', ' +
                                'Rows: ' + (item.total_rows || 0) +
                                '</div>';
                        });
                        $('#importSummary').html(html);
                    } else {
                        $('#importSummary').html(
                            '<strong>Inserted:</strong> ' + (response.inserted || 0) + ' &nbsp; ' +
                            '<strong>Updated:</strong> ' + (response.updated || 0) + ' &nbsp; ' +
                            '<strong>Skipped:</strong> ' + (response.skipped || 0) + ' &nbsp; ' +
                            '<strong>Rows:</strong> ' + (response.total_rows || 0)
                        );
                    }
                }
            }

            $('.import-btn').on('click', function() {
                const scriptName = $(this).data('script');

                $('.import-btn').prop('disabled', true);
                showAlert('info', 'Import started for <strong>' + scriptName + '</strong>. Please wait...');
                startFakeProgress(scriptName);

                $.ajax({
                    url: "{{ route('finance.customer-imports.run-import') }}",
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        script_name: scriptName
                    },
                    success: function(response) {
                        stopFakeProgress(true, response);

                        showAlert(
                            'success',
                            (response.message || 'Import completed.') +
                            '<br><strong>Inserted:</strong> ' + (response.inserted || 0) +
                            ' | <strong>Updated:</strong> ' + (response.updated || 0) +
                            ' | <strong>Skipped:</strong> ' + (response.skipped || 0) +
                            ' | <strong>Total Rows:</strong> ' + (response.total_rows || 0)
                        );

                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let message = 'Import failed.';
                        let extra = '';

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }

                            if (xhr.responseJSON.details) {
                                extra = '<br><small style="white-space:pre-wrap;">' + xhr
                                    .responseJSON.details + '</small>';
                            }

                            if (xhr.responseJSON.error && !xhr.responseJSON.message) {
                                message = xhr.responseJSON.error;
                            }
                        } else if (xhr.responseText) {
                            extra = '<br><small style="white-space:pre-wrap;">' + xhr
                                .responseText.substring(0, 1000) + '</small>';
                        }

                        stopFakeProgress(false, xhr.responseJSON || null);
                        showAlert('danger', message + extra);
                    },
                });
            });
        });
    </script>
@endsection
