<?php

if (!function_exists('getPaymentMethodName')) {
    function getPaymentMethodName($paymentMethod)
    {
        $paymentMethod = $paymentMethod->name == "Mts" ? "Wallet" : $paymentMethod->name;

        return ucfirst($paymentMethod);
    }
}