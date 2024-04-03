@extends('gateways.layouts.master')

@section('styles')
<style>
    .timeline {
      list-style: none;
      padding: 0;
    }
    .timeline-step {
      display: flex;
      align-items: center;
    }
    .timeline-step .step-icon {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: #007bff;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }
    .timeline-step .step-content {
      margin-left: 20px;
    }
    .timeline-step .step-title {
      font-weight: bold;
      margin-bottom: 5px;
    }
    .timeline-step .step-subtitle {
      color: #666;
    }
  </style>
@endsection

@section('content')
<div class="container">
    <ul class="timeline">
        <li class="timeline-step">
            <div class="step-icon">1</div>
            <div class="step-content">
                <div class="step-title">{{ __('Step 1') }}</div>
                <div class="step-subtitle">{{ __('Login to your banking app') }}</div>
            </div>
        </li>
        <li class="timeline-step">
            <div class="step-icon">2</div>
            <div class="step-content">
                <div class="step-title">{{ __('Step 2') }}</div>
                <div class="step-subtitle">{{ __('Transfer the exact amount using the details indicated below') }}</div>
            </div>
        </li>
    </ul>
</div>

<form action="{{ route('gateway.confirm_payment') }}" method="post" id="p2pkassaPaymentForm">
    @csrf

    <input type="hidden" name="payment_method_id" id="payment_method_id" value="{{ $payment_method }}">
    <input type="hidden" name="payment_type" id="payment_type" value="{{ $payment_type }}">
    <input type="hidden" name="transaction_type" id="transaction_type" value="{{ $transaction_type }}">
    <input type="hidden" name="currency_id" id="currency_id" value="{{ $currency_id }}">
    <input type="hidden" name="uuid" id="uuid" value="{{  $uuid  }}">
    <input type="hidden" name="gateway" id="gateway" value="p2pkassa">
    <input type="hidden" name="amount" id="amount" value="{{ $total }}">
    <input type="hidden" name="total_amount" id="total_amount" value="{{ $total }}">
    <input type="hidden" name="redirectUrl" id="redirectUrl" value="{{ $redirectUrl }}">
    <input type="hidden" name="params" value="{{ $params }}">
    <input type="hidden" name="p2pkassa_id" id="p2pkassa_id" value="{{ $p2pkassa['id'] }}">
    <input type="hidden" name="project_id" id="project_id" value="{{ $p2pkassa['project_id'] }}">
    <input type="hidden" name="apikey" id="apikey" value="{{ $p2pkassa['apikey'] }}">
    <input type="hidden" name="country" id="country" value="{{ $p2pkassa['currency'] }}">

    @if ($transaction_type == Payment_Sent)
    <div class="row">
        <div class="col-md-12">
            <div class="form-group mb-3">
                <label class="form-label" for="cr_no">{{ __('Card number:') }} <span class="star">*</span></label>
                <div id="show_hide_password" class="position-relative">
                    <input type="text" class="form-control input-form-control" placeholder="{{ __('0000 0000 0000 0000') }}" name="card" id="cr_no" required data-value-missing="{{ __('This field is required.') }}" size="18" minlength="19" maxlength="19" value="{{ $p2pkassa['card_number'] }}" readonly>
                    <span class="eye-icon cursor-pointer" id="copyIcon"><svg xmlns="http://www.w3.org/2000/svg" height="16" width="14" viewBox="0 0 448 512"><path d="M384 336H192c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16l140.1 0L400 115.9V320c0 8.8-7.2 16-16 16zM192 384H384c35.3 0 64-28.7 64-64V115.9c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1H192c-35.3 0-64 28.7-64 64V320c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H256c35.3 0 64-28.7 64-64V416H272v32c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192c0-8.8 7.2-16 16-16H96V128H64z"/></svg></span>
                </div>
                @error('card')
                    <span class="error"> {{ $message }} </span>
                @enderror
            </div>
            <div class="form-group mb-3">
                <label class="form-label">{{ __('Payment amount:') }} <span class="star">*</span></label>
                <div id="show_hide_password" class="position-relative">
                    <input type="text" class="form-control input-form-control" id="amount" placeholder="{{ __('Payment amount') }}" name="amount" required data-value-missing="{{ __('This field is required.') }}" value="{{ $p2pkassa['amount'] }}">
                    <span class="eye-icon cursor-pointer" id="currency">{{ $p2pkassa['currency'] }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="d-grid">
        <button class="btn btn-lg btn-primary" type="submit" id="p2pkassaSubmitBtn">
            <div class="spinner spinner-border text-white spinner-border-sm mx-2 d-none">
                <span class="visually-hidden"></span>
            </div>
            <span id="p2pkassaSubmitBtnText" class="px-1">{{ __('Pay with :x', ['x' => ucfirst(settings('name'))]) }}</span>
        </button>
    </div>
</form>
@endsection

@section('js')
    <script type="text/javascript" src="{{ asset('public/dist/libraries/jquery-3.6.1/jquery-3.6.1.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('public/dist/plugins/jquery-validation-1.17.0/dist/jquery.validate.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('public/dist/plugins/jquery-validation-1.17.0/dist/additional-methods.min.js') }}"></script>
    <script>
        "use strict";
        var submitText = "{{ __('Submitting...') }}";
        var preText = "{{ __('Payment Via Wallet') }}";
        var ajaxUrl = "{{ route('restapi.payment.verification') }}";
        $(document).ready(function(){

            //For Card Number formatted input
            var cardNum = document.getElementById('cr_no');
            cardNum.onkeyup = function (e) {
                if (this.value == this.lastValue) return;
                var caretPosition = this.selectionStart;
                var sanitizedValue = this.value.replace(/[^0-9]/gi, '');
                var parts = [];
                
                for (var i = 0, len = sanitizedValue.length; i < len; i += 4) {
                    parts.push(sanitizedValue.substring(i, i + 4));
                }
                
                for (var i = caretPosition - 1; i >= 0; i--) {
                    var c = this.value[i];
                    if (c < '0' || c > '9') {
                        caretPosition--;
                    }
                }
                caretPosition += Math.floor(caretPosition / 4);
                
                this.value = this.lastValue = parts.join(' ');
                this.selectionStart = this.selectionEnd = caretPosition;
            }


            function checkForResult() {

                var id = $('#p2pkassa_id').val();
                var project_id = $('#project_id').val();
                var apikey = $('#apikey').val();

                $.ajax({
                    url: ajaxUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        'token': $('input[name="_token"]').val(),
                        'id': id,
                        'project_id': project_id,
                        'apikey': apikey
                    },
                    success: function(response) {
                        if (response.status == 'PAID') {
                            $('#p2pkassaPaymentForm').submit();
                        } else {
                            $("#p2pkassaSubmitBtn").attr("disabled", true);
                            $(".spinner").removeClass('d-none');
                            $("#p2pkassaSubmitBtnText").text("Processing...");
                            setTimeout(checkForResult, 5000);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }

            $('#p2pkassaSubmitBtn').on('click', function(e) {
                e.preventDefault();
                checkForResult();
            });

            $('#copyIcon').click(function() {
                $('#cr_no').select();
                document.execCommand('copy');
                $('#cr_no').blur();
                alert('Text has been copied!');
            });
        });
    </script>
    <script src="{{ asset('public/frontend/customs/js/gateways/mts.min.js') }}"></script>
@endsection