@extends('admin.layouts.master')
@section('title', __('System Update'))

@section('head_style')
    <link rel="stylesheet" href="{{ asset('Modules/Upgrader/Resources/assets/css/style.min.css') }}">
@endsection

@section('page_content')
    <!-- Main content -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h5>{{ __('Update your system') }}</h5>
                </div>
            </div>
            <div class="card-body pb-5">
                <form class="row" action="{{ route('version.download') }}" method="post">
                    @csrf
                    <input type="hidden" name="version" value="{{ $latestVersion }}">

                    @if ($status == 'success')
                    
                        <div class="offset-2 col-sm-2 d-flex align-items-center">
                            <label for="">{{ __('Purchase Key/Code') }}</label>
                        </div>
                        <div class="col-sm-6">
                            <input type="text" class="form-control inputFieldDesign" id="purchaseCode"
                                name="purchase_code" placeholder="{{ __('Purchase code') }}" required
                                oninvalid="this.setCustomValidity('{{ __('This field is required.') }}')">
                            <label><a target="_blank" href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-">{{ __('Where Is My Purchase Code?') }}</a></label>
                        </div>
                    @endif

                    <div class="offset-2 col-sm-4">
                        <div class="text-center alert alert-success mt-3 font-bold">
                            <p class="mb-1">{{ __('Current Version') }}</p>
                            <p class="mb-0">{{ $currentVersion }}</p>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-center alert alert-primary mt-3 font-bold">
                            <p class="mb-1">{{ __('Latest Version') }}</p>
                            <p class="mb-0">{{ $latestVersion }}</p>
                        </div>
                    </div>

                    @if ($status == 'success')
                        <div class="offset-4 col-sm-4 mt-3">
                            <h4 class="text-center">{{ __('An update is available') }}</h4>
                            <div class="d-flex justify-content-center mt-3">
                                <button type="submit" class="btn btn-sm btn-primary">{{ __('Download Now') }}</button>
                            </div>
                        </div>
                    @else
                        <div class="offset-2 col-sm-8 mt-4">{!! $message !!}</div>
                    @endif

                    @if (session('status') == 'fail')
                        <div class="offset-2 col-sm-8 mt-4">
                            {!! session('message') !!}
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection
@push('extra_body_scripts')
    <script src="{{ asset('public/dist/plugins/html5-validation-1.0.0/validation.min.js') }}"></script>
    <script src="{{ asset('Modules/Upgrader/Resources/assets/js/update.min.js') }}"></script>
@endpush
