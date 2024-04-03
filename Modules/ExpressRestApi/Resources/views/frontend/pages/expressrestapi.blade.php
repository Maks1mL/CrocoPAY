@extends('frontend.layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('public/frontend/templates/css/prism.min.css') }}">
@endsection

@section('content')
    <!-- Hero section -->
    <div class="standards-hero-section">
        <div class="px-240">
            <div class="d-flex flex-column align-items-start">
                <nav class="customize-bcrm">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('Developer') }}</li>
                    </ol>
                </nav>
                <div class="btn-section">
                    <button class="btn btn-dark btn-lg">{{ __('Developer') }}</button>
                </div>
                <div class="merchant-text">
                    <p>{{ __('With Pay Money Standard and Express, you can easily and safely receive online payments from your customer.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Merchant tab -->
    @include('frontend.pages.merchant_tab')


    <!--Paymoney code-snippet-section-->
    <div class="px-240 code-snippet-section">
        <div class="snippet-module">
            <div class="snippet-text">
                <div class="standard-title-text mb-28">
                    <h3>{{ __(':x Merchant Payment Rest API Documentation', ['x' => settings('name')]) }}</h3>
                </div>
                <span>{{ __('Description') }}</span>
                <p>{{ __('This document is a guide on how to integrate Merchant Payment with Rest API.') }} <br> {{ __('The API is a restful web service, which accept form data as input. All methods are implemented as POST.') }} <br> {{ __('Before anything to do the user (who is paying to the merchant) must be logged in to get the authorization token.') }}</p>
            </div>
        </div>
        <div class="snippet-module">
            <div class="snippet-text">
                <span>{{ __('LOGIN VIA API') }}</span>
                <p>
                    <b>URL:</b> {{ url('/api/login') }}
                    <br>
                    <b>Method Type:</b> POST
                    <br>
                    <b>Sample Request:</b> BODY PARAMETER (form-data)
                </p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"email":"exemple@exemple.com","password":"123456"} 
                        </code>
                    </pre>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <th>{{ __('Parameter') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Sample') }}</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('email') }}</td>
                            <td>{{ __('Must be email') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>david.luca@gmail.com </td>
                        </tr>
                        <tr>
                            <td>{{ __('password') }}</td>
                            <td>{{ __('User Password') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>123456</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="snippet-text">
                <span>{{ __('SAMPLE RESPONSE') }}</span>
                <p>{{ __('Login Successful') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"response":{"user_id":4,"first_name":"David","last_name":"Luca","email":"david.luca@gmail.com","formattedPhone":null,"picture":"","defaultCountry":"US","TOKEN":"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ijg0OGU2NjhhZDdjMWRmYzhjODA1NGE0NjY5ZTM0OGYyND","STATUS":200,"USER-STATUS":"ACTIVE"}}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('Login Error') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"response":{"status":401,"message":"Invalid email & credentials"}}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('N.B: You have to use this genereted TOKEN on all other steps as Authorization-token in the header section.') }}</p>
            </div>
        </div>
        <div class="snippet-module">
            <div class="snippet-text">
                <span>{{ __('End Point 1: Access Token') }}</span>
                <p>
                    <b>{{ __('URL') }}:</b> {{ url('/api/v2/access-token') }} <br>
                    <b>{{ __('Method') }}:</b> POST <br>
                    <b>{{ __('Description') }}:</b> {{ __('Go to merchant account') }}, {{ url('/merchants') }} {{ __('Click gear icon of
                    approved express merchant.') }}<br>
                    {{ __('From the modal copy client_id, client_secret. This method is used to
                    generate an access token.') }}<br>
                    <b><u>{{ __('N.B') }}:</b></u> {{ __('If the merchant is approved by the admin, only then the gear icon will be available for that merchant.') }}
                </p>
            </div>
            <div class="snippet-text">
                <span>{{ __('SAMPLE REQUEST ') }}</span>
                <p>BODY PARAMETER (form-data)</p>
            </div>
            <div class="language-container">
                <div class="snippet line-numbers">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {" client_id":" yMKqAvC2dILUyhwdIbryh4rsl784kF"," client_secret":" ZubitDCg2QyxuoSu0l6pJkNB5lOrcl1Ivy0qZlhlu8QhWHDYOelkVSNC8B0ybunOb3i832W3FC2SUuXw04Rj8kRHduMx7pdD4a48"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <th>{{ __('Parameter') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Sample') }}</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('client_id') }}</td>
                            <td>{{ __('Merchant client_id') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>yMKqAvC2dILUyhwdIbryh4rsl784kF </td>
                        </tr>
                        <tr>
                            <td>{{ __('client_secret') }}</td>
                            <td>{{ __('Merchant client_secret') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>ZubitDCg2QyxuoSu0l6pJkNB5lOrcl1Ivy0qZlhlu8QhWHDYOe...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="snippet-text">
                <span>{{ __('SAMPLE RESPONSE') }}</span>
                <p>{{ __('Merchant exists') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"access_token": "nJyp8O01Hv2bqkKthOjnw0mcte", "expires_in": 86400, "token_type": "Bearer"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('Merchant does not exist') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"status": "error", "message": "Failed to process the request."}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('N.B: You have to use this genereted access_token on next step as Authorization') }}</p>
            </div>
        </div>
		<div class="snippet-module">
            <div class="snippet-text">
                <span>{{ __('End Point - 2. Initiate Payment') }}</span>
                <p>
                    <b>{{ __('URL') }}:</b> {{ url('/api/v2/initiate-payment') }} <br>
                    <b>{{ __('Method') }}:</b> {{ __('POST') }} <br>
                    <b>{{ __('Description') }}:</b> {{ __('We use this endpoint to store the payment information. Get the access token which is generated by verifying merchant in previous step. Use') }} “Authorization” {{ __('as headers.') }} <code>{{ url('/api/login') }}</code><br>
                    <b><u>{{ __('N.B') }}:</b></u> {{ __('You will need to set the Authorization: Bearer followed by the token value. Add successUrl and cancelUrl as you need.') }}
                </p>
            </div>
            <div class="snippet-text">
                <span>{{ __('SAMPLE REQUEST ') }}</span>
                <p>BODY PARAMETER (form-data)</p>
            </div>
            <div class="language-container">
                <div class="snippet line-numbers">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"amount":"10","currency":"USD","successUrl":"{{ url('/dashboard') }}","cancelUrl":"{{ url('/') }}"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>HEADER PARAMETER </p>
            </div>
            <div class="language-container">
                <div class="snippet line-numbers">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"Authorization":"Bearer nJyp8O01Hv2bqkKthOjnw0mcte"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <th>{{ __('Parameter') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Sample') }}</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('amount') }}</td>
                            <td>{{ __('The amount User have to pay.') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('Double') }}</td>
                            <td>10</td>
                        </tr>
                        <tr>
                            <td>{{ __('currency') }}</td>
                            <td>{{ __('The payment occur on which currency, it should be ISO code.') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>USD</td>
                        </tr>
                        <tr>
                            <td>{{ __('successUrl') }}</td>
                            <td>{{ __('Application dashboard url') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>{{ url('/dashboard') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('cancelUrl') }}</td>
                            <td>{{ __('Application root url') }} </td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>{{ url('/') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Authorization') }}</td>
                            <td>{{ __('Must be given in header, collect it from Access Token') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>{{ "Bearer nJyp8O01Hv2bqkKthOjnw0mcte" }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="snippet-text">
                <span>{{ __('SAMPLE RESPONSE') }}</span>
                <p>{{ __('Success') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"status": "success","message": "Initiated payment successfully.","redirect_url": "http://localhost/pay_v4.1/restapi/payment?grant_id=78784424&token=DuMJThhEimrrdiCKrW2A"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('Invalid Currency') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"status": "error","message": "Currency UAH is not supported by this merchant."}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('Amount Zero') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"status": "error","message": "Amount cannot be 0 or less than 0."}
                        </code>
                    </pre>
                </div>
            </div>
        </div>
		<div class="snippet-module">
            <div class="snippet-text">
                <span>{{ __('End Point - 3. Payment Verify') }}</span>
                <p>
                    <b>{{ __('URL') }}:</b> {{ url('/api/v2/payment-verify') }} <br>
                    <b>{{ __('Method') }}:</b> POST <br>
                    <b>{{ __('Description') }}:</b> {{ __('In this endpoint payment will success, Checks all kinds of input validation and redirected to payment page (if user not logged in then user have to login) if all validation passed. User have to decide to cancel or accept the payment. User grant_id & token as body parameter which is generated in previous step.') }}
                </p>
            </div>
            <div class="snippet-text">
                <span>{{ __('SAMPLE REQUEST ') }}</span>
                <p>BODY PARAMETER (form-data)</p>
            </div>
            <div class="language-container">
                <div class="snippet line-numbers">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"transaction_id":"8479E89BD4192"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>HEADER PARAMETER </p>
            </div>
            <div class="language-container">
                <div class="snippet line-numbers">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"Authorization":"Bearer C49L8XNLz84PiE4I4HYEsOBlog"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <th>{{ __('Parameter') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Sample') }}</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('Transaction Id') }}</td>
                            <td>{{ __('Get from endpoint-2') }} </td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>{{ __('8479E89BD4192') }}</td>
                        </tr>

                        <tr>
                            <td>{{ __('Authorization') }}</td>
                            <td>{{ __('Must be given in header.') }}</td>
                            <td>{{ __('Required') }}</td>
                            <td>{{ __('String') }}</td>
                            <td>{{ "Bearer nJyp8O01Hv2bqkKthOjnw0mcte" }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="snippet-text">
                <span>{{ __('SAMPLE RESPONSE') }}</span>
                <p>{{ __('Payment Success') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"user_id": 1,"currency_id": 1,"payment_method_id": 1,"merchant_id": 1,"uuid": "2997108A5FD15","transaction_type_id": 10,"user_type": "registered","subtotal": "138","percentage": "2","charge_percentage": "2","charge_fixed": "0","total": "140","status": "Success"}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('Merchant & User same') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"success":{"status":801,"message":"Merchant cannot make payment to himself!"}}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('Grant Id or Token Mismatch') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"success":{"status":401,"message":"Grant Id or Token does not Match!"}}
                        </code>
                    </pre>
                </div>
            </div>
            <div class="snippet-text">
                <p>{{ __('Insufficient Balance') }}</p>
            </div>
            <div class="language-container">
                <div class="snippet">
                    <pre class="language-php thin-scrollbar">
                        <code>
                            {"success":{"status":401,"message":"User doesn't have sufficient balance!"}}
                        </code>
                    </pre>
                </div>
            </div>
        </div>
   </div> 
@endsection

@section('js')
    <script src="{{ asset('public/frontend/templates/js/prism.min.js') }}"></script>
@endsection
