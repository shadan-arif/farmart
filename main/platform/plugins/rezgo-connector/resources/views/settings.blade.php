@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="container-fluid">
        <div class="page-header">
            <h1>{{ trans('plugins/rezgo-connector::rezgo.settings_title') }}</h1>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="ti ti-plug-connected me-2"></i>
                            {{ trans('plugins/rezgo-connector::rezgo.api_credentials') }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('rezgo-connector.settings.update') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label required" for="rezgo_cid">
                                    {{ trans('plugins/rezgo-connector::rezgo.cid_label') }}
                                </label>
                                <input
                                    type="text"
                                    id="rezgo_cid"
                                    name="rezgo_cid"
                                    class="form-control @error('rezgo_cid') is-invalid @enderror"
                                    value="{{ old('rezgo_cid', $settings['rezgo_cid']) }}"
                                    placeholder="e.g. 1446"
                                    required
                                />
                                <div class="form-text">{{ trans('plugins/rezgo-connector::rezgo.cid_help') }}</div>
                                @error('rezgo_cid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="rezgo_api_key">
                                    {{ trans('plugins/rezgo-connector::rezgo.api_key_label') }}
                                </label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        id="rezgo_api_key"
                                        name="rezgo_api_key"
                                        class="form-control @error('rezgo_api_key') is-invalid @enderror"
                                        placeholder="{{ empty($settings['rezgo_api_key']) ? trans('plugins/rezgo-connector::rezgo.api_key_placeholder_new') : trans('plugins/rezgo-connector::rezgo.api_key_placeholder_set') }}"
                                        autocomplete="new-password"
                                    />
                                    <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">{{ trans('plugins/rezgo-connector::rezgo.api_key_help') }}</div>
                                @error('rezgo_api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="rezgo_enabled"
                                        name="rezgo_enabled"
                                        value="1"
                                        {{ $settings['rezgo_enabled'] ? 'checked' : '' }}
                                    />
                                    <label class="form-check-label" for="rezgo_enabled">
                                        {{ trans('plugins/rezgo-connector::rezgo.enabled_label') }}
                                    </label>
                                </div>
                                <div class="form-text">{{ trans('plugins/rezgo-connector::rezgo.enabled_help') }}</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i>
                                    {{ trans('plugins/rezgo-connector::rezgo.save_settings') }}
                                </button>

                                <button type="button" id="testConnectionBtn" class="btn btn-outline-info">
                                    <i class="ti ti-wifi me-1"></i>
                                    {{ trans('plugins/rezgo-connector::rezgo.test_connection') }}
                                </button>
                            </div>
                        </form>

                        {{-- Test Connection Result --}}
                        <div id="connectionResult" class="mt-3 d-none">
                            <div id="connectionAlert" class="alert" role="alert"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                {{-- Info card --}}
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="ti ti-info-circle me-1"></i> Rezgo API Info</h5>
                    </div>
                    <div class="card-body small">
                        <p>{{ trans('plugins/rezgo-connector::rezgo.info_text') }}</p>
                        <ul class="mb-0 ps-3">
                            <li>{{ trans('plugins/rezgo-connector::rezgo.info_cid') }}</li>
                            <li>{{ trans('plugins/rezgo-connector::rezgo.info_key') }}</li>
                            <li>{{ trans('plugins/rezgo-connector::rezgo.info_log') }}</li>
                        </ul>
                        <hr />
                        <a href="https://www.rezgo.com/api-documentation/" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="ti ti-external-link me-1"></i> API Documentation
                        </a>
                    </div>
                </div>

                {{-- Log preview --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-file-text me-1"></i> Recent Sync Log</h5>
                    </div>
                    <div class="card-body p-2">
                        <pre class="bg-dark text-light p-2 rounded" style="font-size:11px; max-height:200px; overflow-y:auto;">@php
$logFiles = glob(storage_path('logs/rezgo-sync*.log'));
if (!empty($logFiles)) {
    usort($logFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
    $lines = array_slice(file($logFiles[0]), -30);
    echo htmlspecialchars(implode('', $lines));
} else {
    echo 'No log entries yet.';
}
@endphp</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
<script>
    // Toggle API key visibility
    document.getElementById('toggleApiKey')?.addEventListener('click', function () {
        const input = document.getElementById('rezgo_api_key');
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ti ti-eye-off';
        } else {
            input.type = 'password';
            icon.className = 'ti ti-eye';
        }
    });

    // Test Connection
    document.getElementById('testConnectionBtn')?.addEventListener('click', function () {
        const btn    = this;
        const result = document.getElementById('connectionResult');
        const alert  = document.getElementById('connectionAlert');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing...';
        result.classList.add('d-none');

        fetch('{{ route('rezgo-connector.test-connection') }}', {
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(r => r.text())
        .then(text => {
            // Strip any PHP notices (like Broken Pipe) injected before the JSON
            const startIndex = text.indexOf('{');
            const endIndex = text.lastIndexOf('}');
            if (startIndex === -1 || endIndex === -1) {
                throw new Error(text);
            }
            return JSON.parse(text.substring(startIndex, endIndex + 1));
        })
        .then(data => {
            result.classList.remove('d-none');
            alert.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
            alert.textContent = data.message;
        })
        .catch(err => {
            result.classList.remove('d-none');
            alert.className = 'alert alert-danger';
            alert.textContent = 'Request failed: ' + err.message;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-wifi me-1"></i> {{ trans('plugins/rezgo-connector::rezgo.test_connection') }}';
        });
    });
</script>
@endpush
