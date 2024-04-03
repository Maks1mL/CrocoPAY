@extends('merchantPayment.layouts.app')

@section('content')
    <div class="section-payment">
        <div class="transaction-details-module">
            <div class="transaction-order-quantity">
                <h2>{{ __('Payment information') }}</h2>
            </div>
            <div class="transaction-order-quantity">
                <div class="d-flex justify-content-between">
                    <h3>{{ getColumnValue($merchantInfo->user).'\'s '. $merchantInfo->business_name }}</h3>
                    <span>
                        {{ $merchantInfo->currency->code .' '. formatNumber($amount, $currency_id) }}
                    </span>
                </div>
                <p>{{ __('Account') }}: #{{ $merchantInfo->merchant_uuid}}</p>
            </div>
            <div class="transaction-total d-flex justify-content-between">
                <h3>Total ({{ $merchantInfo->currency->code }})</h3>
                <span>{{ formatNumber($amount, $currency_id) }}</span>
            </div>

            <form action="{{ route('merchant.payment.initiate') }}" method="get" id="paymentMethodForm">
                @csrf
                <input name="merchant_id" value="{{ isset($paymentInfo['merchant_id']) ? $paymentInfo['merchant_id'] : '' }}" type="hidden">
                <input name="merchant" value="{{ isset($paymentInfo['merchant']) ? $paymentInfo['merchant'] : '' }}" type="hidden">
                <input name="amount" value="{{ $amount }}" type="hidden">
                <input name="order_no" value="{{ isset($paymentInfo['order']) ? $paymentInfo['order'] : '' }}" type="hidden">
                <input name="item_name" value="{{ isset($paymentInfo['item_name']) ? $paymentInfo['item_name'] : '' }}" type="hidden">

                <div class="transaction-payment-method">
                    <p>{{ __('Select a Payment Method') }}</p>
                    <div class="d-flex flex-wrap gap-18 mt-2 radio-hide">
                        @php
                            $collection = ['Russia'=> 'RUB', 'Ukraine' => 'UAH', 'Kazakhstan' => 'KZT', 'Uzbekistan' => 'UZS', 'Azerbaijan' => 'AZN'];
                        @endphp
                        @foreach ($collection as $key => $value)
                        <input type="radio" name="country" value="{{ $value }}" id="{{ $value }}" {{ $value == 'RUB' ? 'checked' : '' }}>
                        <label for="{{ $value }}" class="gateway d-inline-flex flex-column justify-content-center align-items-center {{ $value == 'RUB' ? 'gateway-selected' : '' }}">
                            <img src="{{ asset('Modules/ExpressRestApi/Resources/assets/image/' . strtolower($value) . '.png') }}" alt="{{ $value }}">{{ $key }}</label>                            
                        @endforeach
                    </div>
                </div>
                <div class="d-grid">
                    <button class="btn btn-lg btn-primary" type="submit" id="paymentMethodSubmitBtn">
                        <div class="spinner spinner-border text-white spinner-border-sm mx-2 d-none">
                            <span class="visually-hidden"></span>
                        </div>
                        <span id="paymentMethodSubmitBtnText" class="px-1">Continue</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>
        'use strict';
        let paymentMethodSubmitBtnText = "{{ __('Continuing...') }}";
        let pretext = "{{ __('Continue') }}";
    </script>

    <script src="{{ asset('public/frontend/customs/js/merchant-payments/index.js') }}"></script>
@endsection
