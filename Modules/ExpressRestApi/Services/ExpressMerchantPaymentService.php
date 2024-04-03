<?php

namespace Modules\ExpressRestApi\Services;

use Exception;
use App\Models\Wallet;
use App\Models\AppToken;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\FeesLimit;
use App\Models\MerchantApp;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Http\Helpers\Common;
use App\Models\PaymentMethod;
use App\Models\MerchantPayment;
use Illuminate\Support\Facades\DB;
use App\Models\AppTransactionsInfo;
use App\Models\CurrencyPaymentMethod;
use Illuminate\Support\Facades\Session;
use App\Exceptions\ExpressMerchantPaymentException;
use App\Services\Mail\MerchantPayment\NotifyAdminOnPaymentMailService;
use App\Services\Mail\MerchantPayment\NotifyMerchantOnPaymentMailService;

class ExpressMerchantPaymentService
{
    protected $helper;
    
    public function __construct()
    {
        $this->helper = new Common();
    }
    
    /**
     * verifyClientCredentials
     *
     * @param  string $clientId
     * @param  string $clientSecret
     * @return MerchantApp
     */
    public function verifyClientCredentials(string $clientId, string $clientSecret): ?MerchantApp
    {
        $merchantApp = MerchantApp::with('merchant:id,user_id,currency_id')
            ->where('client_id', $clientId)
            ->where('client_secret', $clientSecret)
            ->first();

        if (!$merchantApp) {
            return null;
        }

        if ($this->isMerchantUnavailable($merchantApp->merchant)) {
            return null;
        }

        return $merchantApp;
    }

    private function isMerchantUnavailable(?Merchant $merchant): bool
    {
        return !$merchant || in_array($merchant->status, ['Moderation', 'Disapproved']);
    }
    
    /**
     * createAccessToken
     *
     * @param  object $app
     * @return string
     */
    public function createAccessToken(object $app): string
    {
        return $app->accessToken()->create([
            'token' => Str::random(26),
            'expires_in' => now()->addHour()->timestamp
        ])->token;
    }
    
    /**
     * checkTokenAuthorization
     *
     * @param  string $actualToken
     * @return object
     */
    public function checkTokenAuthorization(string $actualToken): object
    {
        if (!$actualToken) {
            return response()->json(['error' => 'Unauthorized - Token not provided'], 401);
        }

        $appToken = AppToken::where('token', $actualToken)
            ->where('expires_in', '>=', time())
            ->first();

        if (!$appToken) {
            return response()->json(['error' => 'Unauthorized - Invalid token or expired'], 401);
        }

        return $appToken;
    }

    /**
     * Check merchant wallet availability by appToken and currencyCode 
     *
     * @param object $appToken
     * @param string $currencyCode
     *
     * @return void
     * 
     * @through MerchantWithdrawException
     */
    public function checkMerchantWalletAvailability(object $appToken, string $currencyCode, float $amount): void
    {
        $currencyId = Currency::where('code', $currencyCode)->value('id');

        $merchantWallet = Wallet::where([
            'user_id' => optional($appToken->app)->user_id,
            'currency_id' => $currencyId
        ])->first(['id', 'balance', 'user_id']);

        if (!$merchantWallet) {
            self::throwException(__('Currency :x is not supported by this merchant.', ['x' => $currencyCode]), 'currencyNotFound');
        }

        if ($amount <= 0) {
            self::throwException(__('Amount cannot be 0 or less than 0.'), 'amountZero');
        }
    }

    /**
     * createAppTransactionsInfo
     *
     * @param  int $tokenAppId
     * @param  string $paymentMethod
     * @param  float $amount
     * @param  string $currency
     * @param  string $successUrl
     * @param  string $cancelUrl
     * @return string
     */
    public function createAppTransactionsInfo(int $tokenAppId, string $paymentMethod, float $amount, string $currency, string $successUrl, string $cancelUrl): string
    {
        try {
            $transactionInfo = AppTransactionsInfo::createTransactionInfo(
                $tokenAppId,
                $paymentMethod,
                $amount,
                $currency,
                $successUrl,
                $cancelUrl
            );
        } catch (Exception $e) {
            throw new Exception(__('Failed to create transaction info.'));
        }
    
        return route('makepayment', [
            'grant_id' => $transactionInfo->grant_id,
            'token' => $transactionInfo->token,
        ]);
    }

    /**
     * Get app transaction info data through grantId and token
     *
     * @param string $grantId 
     * @param string $token
     *
     * @return object
     * 
     * @through MerchantWithdrawException
     */
    public function getTransactionData(string $grantId, string $token): object
    {
        $transactionInfo = AppTransactionsInfo::with([
            'app:id,merchant_id',
            'app.merchant',
            'app.merchant.merchant_group:id,fee_bearer',
            'app.merchant.user:id,first_name,last_name,status',
        ])
            ->where(['grant_id' => $grantId,'token' => $token,'status' => 'pending'])
            ->where('expires_in', '>=', time())
            ->first();

        self::checkNullAndThrowException($transactionInfo, __('Session expired.'), 'sessionExpired');
        return $transactionInfo;
    }

    /**
     * check for going to payment confirm page through transaction info
     *
     * @param object $transInfo
     *
     * @return array
     * 
     * @through MerchantWithdrawException
     */
    public function checkoutToPaymentConfirmPage(object $transInfo): array
    {
        self::checkNullAndThrowException($transInfo, __('Url has been deleted or expired.'), 'sessionExpired');

        self::checkNullAndThrowException($transInfo?->app?->merchant?->user, __('Merchant user is temporarily unavailable.'), 'merchantUserNotFound');

        //Check whether merchant is suspended or Inactive
        if ($transInfo?->app?->merchant?->user?->status == 'Suspended' || $transInfo?->app?->merchant?->user?->status == 'Inactive') {
            self::throwException(__('Merchant is temporarily unavailable.'), 'merchantNotFound');
        }

        //check if currency exists in wallets
        $availableCurrency = [];

        $wallets = Wallet::with('currency:id,code')->where('user_id', $transInfo?->app?->merchant?->user?->id)->get(['currency_id']);

        foreach ($wallets as $wallet) {
            $availableCurrency[] = getColumnValue($wallet->currency, 'code');
        }

        if (!in_array($transInfo->currency, $availableCurrency)) {
            self::throwException(__('The :x wallet does not exist for the payment', ['x' => $transInfo->currency]), 'walletNotFound');
        }

        //Put transaction information's to Session
        session()->put('transInfo', $transInfo);

        return [
            'status' => 'Active',
            'transInfo' => $transInfo,
            'currSymbol' => Currency::where('code', $transInfo->currency)->value('symbol')
        ];
    }

    /**
     * confirm transaction payment by user, grantId and token
     *
     * @return array
     * 
     * @through MerchantWithdrawException
     */
    public function storePaymentInformations()
    {
        $transInfo = Session::get('transInfo');

        self::checkNullAndThrowException($transInfo, __('Url has been deleted or expired.'), 'sessionExpired');

        $unique_code = unique_code();
        $amount      = $transInfo->amount;
        $currencyCode = $transInfo->currency;

        //Check currency exists in system or not
        $currencyId = Currency::where('code', $currencyCode)->value('id');

        self::checkNullAndThrowException($currencyId, __('Currency not found.'), 'currencyNotFound');

        $feesLimit = self::checkMerchantPaymentFeesLimit($currencyId, Mts, $transInfo->amount, $transInfo?->app?->merchant?->fee);

        $merchantUser = Merchant::where('id', $transInfo?->app?->merchant?->id)->first(['id', 'user_id']);

        self::checkNullAndThrowException($merchantUser, __('Merchant not found.'), 'merchantNotFound');

        $senderWallet = Wallet::where(['user_id' => auth()->user()->id, 'currency_id' => $currencyId])->first(['id', 'balance']);

        self::checkNullAndThrowException($senderWallet, __('Sender wallet not found.'), 'merchantWalletNotFound');

        if ($senderWallet->balance < $amount) {
            self::throwException(__('Sender does not have enough balance.'), 'notHaveEnoughBalance');
        }

        try {
            DB::beginTransaction();

            $data = [];

            //Check User has the wallet or not
            $senderWallet->balance = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $senderWallet->balance - $amount : $senderWallet->balance - ($amount + $feesLimit['totalFee']);
            $senderWallet->save();

            $merchantPayment                    = new MerchantPayment();
            $merchantPayment->merchant_id       = $transInfo->app?->merchant_id;
            $merchantPayment->currency_id       = $currencyId;
            $merchantPayment->payment_method_id = 1;
            $merchantPayment->user_id           = auth()->user()->id;
            $merchantPayment->gateway_reference = $unique_code;
            $merchantPayment->order_no          = '';
            $merchantPayment->item_name         = '';
            $merchantPayment->uuid              = $unique_code;
            $merchantPayment->status            = 'Success';
            $merchantPayment->fee_bearer        = $transInfo?->app?->merchant?->merchant_group?->fee_bearer;
            $merchantPayment->charge_percentage = $feesLimit['depositPercent'] + $feesLimit['merchantPercentOrTotalFee'];
            $merchantPayment->charge_fixed      = $feesLimit['chargeFixed'];
            $merchantPayment->percentage        = $transInfo?->app?->merchant?->fee + $feesLimit['chargePercentage'];
            $merchantPayment->amount            = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $amount - $feesLimit['totalFee'] : $amount;
            $merchantPayment->total             = $merchantPayment->amount + $feesLimit['totalFee'];
            $merchantPayment->save();

            $userTransaction                           = new Transaction();
            $userTransaction->user_id                  = auth()->user()->id;
            $userTransaction->end_user_id              = $transInfo?->app?->merchant?->user_id;
            $userTransaction->merchant_id              = $transInfo?->app?->merchant_id;
            $userTransaction->currency_id              = $currencyId;
            $userTransaction->payment_method_id        = Mts;
            $userTransaction->uuid                     = $unique_code;
            $userTransaction->transaction_reference_id = $merchantPayment->id;
            $userTransaction->transaction_type_id      = Payment_Sent;
            $userTransaction->subtotal                 = $amount;
            $userTransaction->status                   = 'Success';

            $userTransaction->percentage = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? 0 : $transInfo?->app?->merchant?->fee + $feesLimit['chargePercentage'];

            $userTransaction->charge_percentage = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? 0 : $feesLimit['depositPercent'] + $feesLimit['merchantPercentOrTotalFee'];

            $userTransaction->charge_fixed = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? 0 : $feesLimit['chargeFixed'];
            $userTransaction->total = '-' . ($userTransaction->subtotal + $userTransaction->charge_percentage + $userTransaction->charge_fixed);
            $userTransaction->save();

            $merchantTransaction                           = new Transaction();
            $merchantTransaction->user_id                  = $transInfo->app?->merchant?->user_id;
            $merchantTransaction->end_user_id              = auth()->user()->id;
            $merchantTransaction->merchant_id              = $transInfo->app?->merchant_id;
            $merchantTransaction->currency_id              = $currencyId;
            $merchantTransaction->payment_method_id        = 1;
            $merchantTransaction->uuid                     = $unique_code;
            $merchantTransaction->transaction_reference_id = $merchantPayment->id;
            $merchantTransaction->transaction_type_id      = Payment_Received;
            $merchantTransaction->status                   = 'Success';

            $merchantTransaction->subtotal = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $amount - $feesLimit['totalFee'] : $amount;

            $merchantTransaction->percentage = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $transInfo->app?->merchant?->fee + $feesLimit['chargePercentage'] : 0;

            $merchantTransaction->charge_percentage = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $feesLimit['depositPercent'] + $feesLimit['merchantPercentOrTotalFee'] : 0;

            $merchantTransaction->charge_fixed = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $feesLimit['chargeFixed'] : 0;

            $merchantTransaction->total = $merchantTransaction->charge_percentage + $merchantTransaction->charge_fixed + $merchantTransaction->subtotal;

            $merchantTransaction->save();

            $transInfo->status = 'success';
            $transInfo->save();

            //updating/Creating merchant wallet
            $merchantWallet = Wallet::where(['user_id' => $transInfo?->app?->merchant?->user_id, 'currency_id' => $currencyId])->first(['id', 'balance']);
            if (empty($merchantWallet)) {
                $wallet              = new Wallet();
                $wallet->user_id     = $transInfo->app?->merchant?->user_id;
                $wallet->currency_id = $currencyId;
                $wallet->balance     = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? ($amount - $feesLimit['totalFee']) : $amount;
                $wallet->is_default  = 'No';
                $wallet->save();
            } else {
                $merchantWallet->balance = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $merchantWallet->balance + ($amount - $feesLimit['totalFee']) : $merchantWallet->balance + $amount;
                $merchantWallet->save();
            }

            DB::commit();

            // Send mail to admin
            (new NotifyAdminOnPaymentMailService())->send($merchantPayment, ['type' => 'payment', 'medium' => 'email', 'fee_bearer' => $transInfo?->app?->merchant?->merchant_group?->fee_bearer, 'fee' => $feesLimit['totalFee']]);

            // Send mail to merchant
            (new NotifyMerchantOnPaymentMailService())->send($merchantPayment, ['fee_bearer' => $transInfo?->app?->merchant?->merchant_group?->fee_bearer, 'fee' => $feesLimit['totalFee']]);

            //pass the response to success url
            $response = [
                'status'         => 200,
                'transaction_id' => $merchantPayment->uuid,
                'merchant'       => getColumnValue($merchantPayment->merchant?->user),
                'currency'       => $merchantPayment->currency?->code,
                'fee'            => $merchantPayment->charge_percentage,
                'amount'         => $merchantPayment->amount,
                'total'          => $merchantPayment->total,
            ];
            $response            = json_encode($response);
            $encodedResponse     = base64_encode($response);
            $successPath         = $transInfo->success_url . '?' . $encodedResponse;
            $data['status']      = 200;
            $data['successPath'] = $successPath;
            return $data;
        } catch (Exception $e) {
            DB::rollBack();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect()->to('payment/fail');
        }
    }

    public function checkMerchantPaymentFeesLimit($currencyId, $paymentMethodId, $amount, $merchantFee)
    {
        $feeInfo = FeesLimit::where(['transaction_type_id' => Deposit, 'currency_id' => $currencyId, 'payment_method_id' => $paymentMethodId])->first(['charge_percentage', 'charge_fixed', 'has_transaction']);

        if (!empty($feeInfo) && $feeInfo->has_transaction == "Yes") {
            $feeInfoChargePercentage          = $feeInfo->charge_percentage;
            $feeInfoChargeFixed               = $feeInfo->charge_fixed;
            $depositCalcPercentVal            = $amount * ($feeInfoChargePercentage / 100);
            $depositTotalFee                  = $depositCalcPercentVal + $feeInfoChargeFixed;
            $merchantCalcPercentValOrTotalFee = $amount * ($merchantFee / 100);
            $totalFee                         = $depositTotalFee + $merchantCalcPercentValOrTotalFee;
        } else {
            $feeInfoChargePercentage          = 0;
            $feeInfoChargeFixed               = 0;
            $depositCalcPercentVal            = 0;
            $depositTotalFee                  = 0;
            $merchantCalcPercentValOrTotalFee = $amount * ($merchantFee / 100);
            $totalFee                         = $depositTotalFee + $merchantCalcPercentValOrTotalFee;
        }

        return [
            'merchantPercentOrTotalFee' => $merchantCalcPercentValOrTotalFee,
            'chargePercentage' => $feeInfoChargePercentage,
            'depositPercent' => $depositCalcPercentVal,
            'depositTotalFee' => $depositTotalFee,
            'chargeFixed' => $feeInfoChargeFixed,
            'totalFee' => $totalFee,
        ];
    }

    public function checkUserBalance(int $userId, float $amount, int $currencyId,)
    {
        $userWallet = Wallet::where(['user_id' => $userId, 'currency_id' => $currencyId])->first(['balance']);

        self::checkNullAndThrowException($userWallet, __('User wallet not found.'), 'walletNotFound');

        if ($userWallet->balance < $amount) {
            Self::throwException(__('User does not have sufficient balance.'), 'insufficientBalance');
        }
    }

    /**
     * checkNullAndThrowException
     *
     * @param object|int|null $object
     * @param string $message
     * @param string|null $reason
     *
     * @return void
     * 
     * @through ExpressMerchantPaymentException
     */
    public function checkNullAndThrowException(object|int|null $object, string $message, string|null $reason)
    {
        if (is_null($object)) {
            throw new ExpressMerchantPaymentException($message, [
                "reason" => $reason,
                "message" => $message
            ]);
        }
    }

    /**
     * Method throwException
     *
     * @param string $message
     * @param string|null $reason
     *
     * @return void
     * 
     * @through ExpressMerchantPaymentException
     */
    public function throwException(string $message, string|null $reason)
    {
        throw new ExpressMerchantPaymentException($message, [
            "reason" => $reason,
            "message" => $message
        ]);
    }

    public function getMerchantPaymentInfo($grantId, $token)
    {
        $transInfo = $this->getTransactionData($grantId, $token);
        $currency = Currency::whereCode($transInfo->currency)->first();

        $merchant = $transInfo->app->merchant()
            ->with('currency','user')
            ->first();

        $isMerchantAvailable = $merchant !== null;

        $paymentMethods = PaymentMethod::where('status', 'Active')->whereIn('name', ['Mts', 'P2PKassa'])
            ->get(['id', 'name'])
            ->toArray();

        $cpmWithoutMts = CurrencyPaymentMethod::where('currency_id', optional($currency)->id)
            ->where('activated_for', 'like', "%deposit%")
            ->pluck('method_id')
            ->toArray();

        $paymoneyId = PaymentMethod::whereName('Mts')->value('id');
        if ($paymoneyId) {
            $cpmWithoutMts[] = $paymoneyId;
        }

        setPaymentData([
            "merchant" => $merchant->merchant_uuid,
            "merchant_id" => $merchant->id,
            "item_name" => "Item Name",
            "currency_id" => $currency->id,
            "order" => "ORDER-ID",
            "amount" => $transInfo->amount,
            "custom" => "Dummy data",
        ]);
        
        return [
            'merchant' => $merchant,
            'isMerchantAvailable' => $isMerchantAvailable,
            'payment_methods' => $paymentMethods,
            'cpm' => $cpmWithoutMts,
        ];
    }
}
