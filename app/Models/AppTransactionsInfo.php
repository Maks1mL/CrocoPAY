<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppTransactionsInfo extends Model
{
    protected $table    = 'app_transactions_infos';
    protected $fillable = ['app_id', 'payment_method', 'amount', 'currency', 'success_url', 'cancel_url', 'grant_id', 'token', 'expires_in', 'status'];

    public function app()
    {
        return $this->belongsTo(MerchantApp::class, 'app_id', 'id');
    }
    
    public static function createTransactionInfo($tokenAppId, $paymentMethod, $amount, $currency, $successUrl, $cancelUrl)
    {
        $grantId = random_int(10000000, 99999999);
        $urlToken = Str::random(20);
        $expiresIn = now()->addHours(5)->timestamp;
        return self::create([
            'app_id' => $tokenAppId,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'currency' => $currency,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'grant_id' => $grantId,
            'token' => $urlToken,
            'status' => 'pending',
            'expires_in' => $expiresIn,
        ]);
    }
    public function getByGrantIdAndToken($grantId, $token)
    {
        return $this->where('grant_id', $grantId)
            ->where('token', $token)
            ->where('expires_in', '>=', time())
            ->first();
    }
}
