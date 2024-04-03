@extends('frontend.layouts.app')
@section('content')
    <div class="min-vh-100">

        <!--End banner Section-->
<div class="standards-hero-section privacy-template scrol-pt">
        <div class="px-240">
            <div class="d-flex flex-column align-items-start">
                <nav class="customize-bcrm" style="margin-top: 40px;">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Тарифы</li>
                    </ol>
                </nav>

                <div class="merchant-text">
                    <p>
                        <h1>Комиссия за обработку платежей</h1>
                    </p>
                </div>
                <div class="btn-section" style="">
                    <button class="btn btn-dark btn-lg" style="background-color: #635bff;margin-top: 50px;margin-bottom: 0px;"><a style="color: #fff;padding: 10px 20px" href="https://crocopay.app/register">РЕГИСТРАЦИЯ</a></button>
                </div>
            </div>
        </div>
    </div>
        <!--Start Section-->
        <section class="mt-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="pageArticle_content">
                            <div class="row">
<div style="margin: 10px 0 20px;">
<h4>Ставка может варьироваться в зависимости от нескольких факторов:</h4><br>
<ul>
<li><strong>Обороты и количество магазинов:</strong> Чем выше обороты и больше магазинов, тем ниже может быть ставка.</li>
<li><strong>Тематика вашего проекта:</strong> Ставка может зависеть от тематики вашего проекта. Некоторые тематики могут быть более привлекательными для рекламодателей, что может повлиять на ставку.</li>
<li><strong>Объем продаж:</strong> Если вы продаете больше, то вероятно платите меньшую комиссию. Это связано с тем, что с увеличением объема продаж платформе или партнерам становится выгоднее снизить комиссию.</li>
<li><strong>Автоматическое пересчет каждый месяц:</strong> Ставка может пересчитываться автоматически каждый месяц. Это может происходить для адаптации к изменяющимся условиям рынка и результатам вашей работы.</li>
<li>Таким образом, ставка зависит от оборотов, количества магазинов, тематики проекта и объема продаж. При этом она может пересчитываться каждый месяц.</li>
</ul>
</div>
<h2>P2P процессинг</h2>
<div class="col-12 col-md-3 mb-4">
    <div style="border:1px solid #95959540;border-radius:10px;padding:10px">
    <div class="row">
        <div class="col-5" style="background-position-x: 3px !important;background: url(https://crocopay.app/Modules/ExpressRestApi/Resources/assets/image/rub.png);background-size: contain;background-position: center;background-repeat: no-repeat;"></div>
        <div class="col-7 text-end">
            <span class="cur">RUB</span><p>
            <span class="nazv">Visa/MC/MIR</span>
            <span class="procent">от <strong>7%</strong></span>
        </div>
        </div>
    </div>
</div>

<div class="col-12 col-md-3 mb-4">
    <div style="border:1px solid #95959540;border-radius:10px;padding:10px">
    <div class="row">
        <div class="col-5" style="background-position-x: 3px !important;background: url(https://crocopay.app/Modules/ExpressRestApi/Resources/assets/image/rub.png);background-size: contain;background-position: center;background-repeat: no-repeat;"></div>
        <div class="col-7 text-end">
            <span class="cur">RUB</span><p>
            <span class="nazv">СБП</span>
            <span class="procent">от <strong>7%</strong></span>
        </div>
        </div>
    </div>
</div>

<div class="col-12 col-md-3 mb-4">
    <div style="border:1px solid #95959540;border-radius:10px;padding:10px">
    <div class="row">
        <div class="col-5" style="background-position-x: 3px !important;background: url(https://crocopay.app/Modules/ExpressRestApi/Resources/assets/image/byn.png);background-size: cover;background-position: center;background-repeat: no-repeat;"></div>
        <div class="col-7 text-end">
            <span class="cur">BYN</span><p>
            <span class="nazv">Visa/MC/MIR</span>
            <span class="procent">от <strong>8%</strong></span>
        </div>
        </div>
    </div>
</div>

<div class="col-12 col-md-3 mb-4">
    <div style="border:1px solid #95959540;border-radius:10px;padding:10px">
    <div class="row">
        <div class="col-5" style="background-position-x: 3px !important;background: url(https://crocopay.app/Modules/ExpressRestApi/Resources/assets/image/uah.png);background-size: contain;background-position: center;background-repeat: no-repeat;"></div>
        <div class="col-7 text-end">
            <span class="cur">UAH</span><p>
            <span class="nazv">Visa/MC/MIR</span>
            <span class="procent">от <strong>8%</strong></span>
        </div>
        </div>
    </div>
</div>

<div class="col-12 col-md-3 mb-4">
    <div style="border:1px solid #95959540;border-radius:10px;padding:10px">
    <div class="row">
        <div class="col-5" style="background-position-x: 3px !important;background: url(https://crocopay.app/Modules/ExpressRestApi/Resources/assets/image/kzt.png);background-size: cover;background-position: center;background-repeat: no-repeat;"></div>
        <div class="col-7 text-end">
            <span class="cur">KZT</span><p>
            <span class="nazv">Visa/MC/MIR</span>
            <span class="procent">от <strong>8%</strong></span>
        </div>
        </div>
    </div>
</div>

<div class="col-12 col-md-3 mb-4">
    <div style="border:1px solid #95959540;border-radius:10px;padding:10px">
    <div class="row">
        <div class="col-5" style="background-position-x: 3px !important;background: url(https://crocopay.app/Modules/ExpressRestApi/Resources/assets/image/uzs.png);background-size: cover;background-position: center;background-repeat: no-repeat;"></div>
        <div class="col-7 text-end">
            <span class="cur">UZS</span><p>
            <span class="nazv">Visa/MC/MIR</span>
            <span class="procent">от <strong>8%</strong></span>
        </div>
        </div>
    </div>
</div>



<h2>Crypto</h2>




                            </div>
                        </div>
                    </div>
                    <!--/col-->
                </div>
                <!--/row-->
            </div>
        </section>
    </div>
<style>
.cur {background: #635bff;padding: 4px;color: #fff;font-weight: 500;border-radius: 5px;}
.nazv {margin: 9px 0;display: block;font-size: 18px;font-weight: 700;}
.procent {background: #febf5c;padding: 5px 10px;border-radius: 8px;color: #ffffff;}
</style>
@endsection
