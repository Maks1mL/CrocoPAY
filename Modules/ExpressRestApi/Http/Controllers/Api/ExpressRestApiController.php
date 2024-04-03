<?php

namespace Modules\ExpressRestApi\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use App\Models\AppToken;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Http\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\MerchantPayment;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\AppTransactionsInfo;
use App\Services\TransactionService;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Lcobucci\JWT\Token\Parser as JwtParser;
use App\Exceptions\ExpressMerchantPaymentException;
use Modules\ExpressRestApi\Http\Requests\PaymentRequest;
use Modules\ExpressRestApi\Http\Requests\GeneratedUrlRequest;
use Modules\ExpressRestApi\Services\ExpressMerchantPaymentService;
use Modules\ExpressRestApi\Transformers\ExpressTransactionResource;
use App\Services\Mail\MerchantPayment\NotifyAdminOnPaymentMailService;
use App\Services\Mail\MerchantPayment\NotifyMerchantOnPaymentMailService;
use Modules\ExpressRestApi\Notifications\NotifyAdminOnExpressRestApiMailService;

class ExpressRestApiController extends Controller
{
    protected $helper;
    protected $merchantService;

    public function __construct(ExpressMerchantPaymentService $merchantService)
    {
        $this->helper = new Common();
        $this->merchantService = $merchantService;
    }

    public function createAccessToken(Request $request)
    {
        try {
            $clientId = $request->input('client_id');
            $clientSecret = $request->input('client_secret');
    
            $merchantApp = $this->merchantService->verifyClientCredentials($clientId, $clientSecret);
    
            if (!$merchantApp) {
                abort(403, __('Can not verify the client. Please check client Id and Client Secret.'));
            }

            $wallet = Wallet::firstOrCreate(['user_id' => $merchantApp->user_id, 'currency_id' => $merchantApp->merchant->currency_id], ['balance' => 0, 'is_default' => 'No']);

            $accessToken = $this->merchantService->createAccessToken($merchantApp);
            
            return response()->json([
                'status' => 'success',
                'access_token' => $accessToken,
                'expires_in' => 86400,
                'token_type' => 'Bearer',
            ], 200);
        } catch (ExpressMerchantPaymentException $exception) {
            return response()->json([
                'status'  => 'error',
                'message' => $exception->getMessage(),
            ], 400);
        } catch(Exception $exception) {
            return response()->json([
                'status'  => 'error',
                'message' => __("Failed to process the request."),
            ], 404);
        }
    }

    public function initiatePayment(PaymentRequest $request)
    {
        try {            
            $validatedData = $request->validated();

            $amount = $validatedData['amount'];
            $currency = $validatedData['currency'];
            $successUrl = $validatedData['successUrl'];
            $cancelUrl = $validatedData['cancelUrl'];

            $token = $this->merchantService->checkTokenAuthorization($request->bearerToken());

            # Currency And Amount Validation
            $this->merchantService->checkMerchantWalletAvailability($token, $currency, $amount);

            # Update/Create AppTransactionsInfo and return response
            $responseUrl = $this->merchantService->createAppTransactionsInfo($token->app_id, settings('name'), $amount, $currency, $successUrl, $cancelUrl);
            
            // return json_encode($res);
            return response()->json([
                'status'  => 'success',
                'message' => __('Initiated payment successfully.'),
                'redirect_url' => $responseUrl,
            ], 200);
        } catch (ExpressMerchantPaymentException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch(Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => __("Failed to process the request."),
            ], 404);
        }
    }

    public function paymentVerify(Request $request)
    {
        try {
            $transactionId = $request->transaction_id;

            $transaction = Transaction::with(['currency', 'merchant'])->where(['uuid' => $transactionId])->first();

            return response()->json((new ExpressTransactionResource($transaction)), 200);
        } catch(Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => __($e->getMessage()),
            ], 404);
        }
    }

    public function generatedUrl(GeneratedUrlRequest $request)
    {
        $transInfo = $this->getTransactionData($request->grant_id, $request->token);

        if (empty($transInfo)) {
            return response()->json([
                'success' => [
                    'message' => "Session expired.",
                    'status' => 403,
                ]
            ], $this->unauthorisedStatus);
        }

        $user = User::find($this->getAuthUserId(), ['id', 'status']);
        $checkPaidByUser = $this->helper->getUserStatus($user->status);
    
        if ($checkPaidByUser === 'Suspended') {
            return $this->responseWithMessage(801, __('User is suspended to do any kind of transaction!'));
        } elseif ($checkPaidByUser === 'Inactive') {
            return $this->responseWithMessage(802, __('User account is inactivated. Please try again later!'));
        }

        $data = $this->checkoutToPaymentConfirmPage($transInfo);
        if ($data['status'] === 403) {
            return response()->json(['success' => $data], $this->unauthorisedStatus);
        } elseif ($data['status'] === 'Suspended') {
            return $this->responseWithMessage(803, __('Merchant is suspended to do any kind of transaction!'));
        } elseif ($data['status'] === 'Inactive') {
            return $this->responseWithMessage(804, __('Merchant account is inactivated. Please try again later!'));
        } elseif ($transInfo->app->merchant->user->id === $user->id) {
            return $this->responseWithMessage(805, __('Merchant cannot make payment to himself!'));
        }
    
        return $this->confirmPayment($user, request('grant_id'), request('token'));
    }

    private function responseWithMessage($status, $message)
    {
        return response()->json([
            'success' => [
                'status' => $status,
                'message' => $message,
            ]
        ], $this->unauthorisedStatus);
    }

    protected function checkoutToPaymentConfirmPage($transInfo)
    {
        if (!$transInfo) {
            return $this->abortWithError('Url has been deleted or expired.', 403);
        }

        $merchantStatus = $this->getMerchantStatus($transInfo->app->merchant->user->status);
        if ($merchantStatus) {
            return [
                'message' => __('Merchant is ' . $merchantStatus),
                'status' => $merchantStatus,
            ];
        }

        $availableCurrencies = Wallet::where('user_id', $transInfo->app->merchant->user->id)
            ->with('currency:id,code')
            ->get()
            ->pluck('currency.code')
            ->toArray();

        if (!in_array($transInfo->currency, $availableCurrencies)) {
            $this->helper->one_time_message('error', "You don't have the payment wallet. Please create a wallet for currency - {$transInfo->currency} !");
            return redirect()->to('payment/fail');
        }

        $data = [
            'currSymbol' => Currency::where('code', $transInfo->currency)->value('symbol'),
            'transInfo' => $transInfo,
            'status' => 'Active',
        ];

        Session::put('transInfo', $transInfo);

        return $data;
    }

    protected function abortWithError($message, $code)
    {
        abort($code, $message);
    }

    protected function getMerchantStatus($status)
    {
        if ($status === 'Suspended') {
            return 'Suspended';
        } elseif ($status === 'Inactive') {
            return 'Inactive';
        }
        return null;
    }

    protected function checkTokenAuthorization($headerAuthorization)
    {
        $accessToken = $headerAuthorization;
        $actualToken = '';

        if (preg_match('/\bBearer\b/', $accessToken)) {
            $t = explode(' ', $accessToken);
            $actualToken = end($t);
        }

        $token = AppToken::where('token', $actualToken)->where('expires_in', '>=', time())->first();

        if (!$token) {
            return json_encode([
                'status' => 411,
                'message' => 'Unauthorized token or token has expired',
            ]);
        }
        return $token;
    }

    protected function currencyValidation($token, $currency)
    {
        $acceptedCurrency = '';
        $wallets = $token->app->merchant->user->wallets;

        foreach ($wallets as $wallet) {
            if ($wallet->is_default === 'Yes') {
                $acceptedCurrency = $wallet->currency->code;
                break;
            }
        }

        if ($currency !== $acceptedCurrency) {
            return [
                'status' => 'error',
                'message' => 'Currency ' . $currency . ' is not supported by this merchant!',
            ];
        }
        return ['status' => ''];
    }

    protected function amountValidation($amount)
    {
        if ($amount <= 0) {
            return [
                'status' => 'error',
                'message' => 'Amount cannot be 0 or less than 0.',
                'data' => [],
            ];
        }
        return ['status' => ''];
    }

    protected function updateOrAppTransactionsInfoAndReturnResponse($tokenAppId, $paymentMethod, $amount, $currency, $successUrl, $cancelUrl)
    {
        try {
            $grandId = random_int(10000000, 99999999);
            $urlToken = Str::random(20);

            AppTransactionsInfo::updateOrCreate([
                'app_id' => $tokenAppId,
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'currency' => $currency,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'grant_id' => $grandId,
                'token' => $urlToken,
                'expires_in' => time() + (60 * 60 * 5),
            ]);

            $url = url("merchant/payment?grant_id=$grandId&token=$urlToken");
            return [
                'status' => 'success',
                'grandId' => $grandId,
                'token' => $urlToken,
                'data' => [
                    'approvedUrl' => $url,
                ],
            ];
        } catch (Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Internal Server Error'], 500);
        }
    }

    public function confirmPayment()
    {
        $data = $this->storePaymentInformations();

        $response = [
            'status' => $data['status']
        ];
    
        switch ($data['status']) {
            case 200:
                $response['message'] = "Success";
                $response['successPath'] = $data['successPath'];
                return response()->json(['success' => $response], $this->successStatus);
    
            case 404:
                $response['message'] = "Currency does not exist in the system!";
                break;
    
            case 402:
                $response['message'] = "User doesn't have the wallet - {$data['currency']}. Please exchange to wallet - {$data['currency']}!";
                break;
    
            case 405:
                $response['message'] = "Merchant doesn't have sufficient balance!";
                break;
    
            case 406:
                $response['message'] = "User does not have enough balance!";
                break;
    
            case 407:
                $response['message'] = $data['message'];
                $response['cancelPath'] = $data['cancelPath'];
                break;
    
            default:
                return response()->json(['error' => 'Unexpected error occurred.'], $this->unauthorisedStatus);
        }
    
        return response()->json(['success' => $response], $this->unauthorisedStatus);
    }

    protected function storePaymentInformations()
    {

        $transInfo = Session::get('transInfo');

        if (empty($transInfo)) {
            return $this->returnError(403, 'Url has been deleted or expired.');
        }

        try {
            $amount = $transInfo->amount;
            $currency = $transInfo->currency;
            $p_calc = ($transInfo->app->merchant->fee / 100) * $amount;
            $unique_code = unique_code();

            $curr = Currency::where('code', $currency)->first(['id']);
            
            if (!$curr) {
                return $this->returnError(404);
            }
            $feesLimit = (new ExpressMerchantPaymentService())->checkMerchantPaymentFeesLimit($curr->id, Mts, $amount, $transInfo->app->merchant->fee);

            $userId = $this->getAuthUserId();
            $senderWallet = Wallet::where(['user_id' => $userId, 'currency_id' => $curr->id])->first(['id', 'balance']);

            if (!$senderWallet || $senderWallet->balance < $amount) {
                return $this->returnError(405, $transInfo->currency);
            }

            DB::beginTransaction();

            $data = [];

            $trans = AppTransactionsInfo::find($transInfo->id);
            $trans->status = 'success';
            $trans->save();
    
            // Update sender's wallet balance
            $senderWallet->balance = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $senderWallet->balance - $amount : $senderWallet->balance - ($amount + $feesLimit['totalFee']) ;
            $senderWallet->save();
    
            // Create merchant payment
            $merchantPayment = new MerchantPayment();
            $merchantPayment->merchant_id = $transInfo->app->merchant_id;
            $merchantPayment->currency_id = $curr->id;
            $merchantPayment->payment_method_id = 1;
            $merchantPayment->user_id = $userId;
            $merchantPayment->gateway_reference = $unique_code;
            $merchantPayment->order_no = '';
            $merchantPayment->item_name = '';
            $merchantPayment->uuid = $unique_code;
            $merchantPayment->fee_bearer = $transInfo?->app?->merchant?->merchant_group?->fee_bearer;
            $merchantPayment->charge_percentage = $feesLimit['depositPercent'] + $feesLimit['merchantPercentOrTotalFee'];
            $merchantPayment->charge_fixed      = $feesLimit['chargeFixed'];
            $merchantPayment->percentage        = $transInfo?->app?->merchant?->fee + $feesLimit['chargePercentage'];
            $merchantPayment->amount            = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $amount - $feesLimit['totalFee'] : $amount;
            $merchantPayment->total             = $merchantPayment->amount + $feesLimit['totalFee'];
            $merchantPayment->status = 'Success';
            $merchantPayment->save();

            $transaction_A                           = new Transaction();
            $transaction_A->user_id                  = $userId;
            $transaction_A->end_user_id              = $transInfo->app->merchant->user_id;
            $transaction_A->merchant_id              = $transInfo->app->merchant_id;
            $transaction_A->currency_id              = $curr->id;
            $transaction_A->payment_method_id        = 1;
            $transaction_A->uuid                     = $unique_code;
            $transaction_A->transaction_reference_id = $merchantPayment->id;
            $transaction_A->transaction_type_id      = Payment_Sent;
            $transaction_A->subtotal                 = $amount;
            $transaction_A->percentage               = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? 0 : $transInfo?->app?->merchant?->fee + $feesLimit['chargePercentage'];
            $transaction_A->charge_percentage        = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? 0 : $feesLimit['depositPercent'] + $feesLimit['merchantPercentOrTotalFee'];
            $transaction_A->charge_fixed             = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? 0 : $feesLimit['chargeFixed'];
            $transaction_A->total                    = '-' . ($transaction_A->subtotal + $transaction_A->charge_percentage + $transaction_A->charge_fixed);
            $transaction_A->note                     = 'Merchant payment';
            $transaction_A->status                   = 'Success';
            $transaction_A->save();

            $merchantTransaction                           = new Transaction();
            $merchantTransaction->user_id                  = $transInfo->app->merchant->user_id;
            $merchantTransaction->end_user_id              = $userId;
            $merchantTransaction->merchant_id              = $transInfo->app->merchant_id;
            $merchantTransaction->currency_id              = $curr->id;
            $merchantTransaction->payment_method_id        = 1;
            $merchantTransaction->uuid                     = $unique_code;
            $merchantTransaction->transaction_reference_id = $merchantPayment->id;
            $merchantTransaction->transaction_type_id      = Payment_Received;
            $merchantTransaction->subtotal                 = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $amount - $feesLimit['totalFee'] : $amount;
            $merchantTransaction->percentage               = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $transInfo->app?->merchant?->fee + $feesLimit['chargePercentage'] : 0;
            $merchantTransaction->charge_percentage        = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $feesLimit['depositPercent'] + $feesLimit['merchantPercentOrTotalFee'] : 0;
            $merchantTransaction->charge_fixed             = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $feesLimit['chargeFixed'] : 0;
            $merchantTransaction->total                    = $merchantTransaction->charge_percentage + $merchantTransaction->charge_fixed + $merchantTransaction->subtotal;
            $merchantTransaction->note                     = 'Merchant payment';
            $merchantTransaction->status                   = 'Success';
            $merchantTransaction->save();

            //updating/Creating merchant wallet
            $merchantWallet          = Wallet::where(['user_id' => $transInfo->app->merchant->user_id, 'currency_id' => $curr->id])->first(['id', 'balance']);
            if (empty($merchantWallet)) {
                $wallet              = new Wallet();
                $wallet->user_id     = $transInfo->app->merchant->user_id;
                $wallet->currency_id = $curr->id;
                $wallet->balance     = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? ($amount - $feesLimit['totalFee']) : $amount;
                $wallet->is_default  = 'No';
                $wallet->save();
            } else {
                $merchantWallet->balance = $transInfo?->app?->merchant?->merchant_group?->fee_bearer == 'Merchant' ? $merchantWallet->balance + ($amount - $feesLimit['totalFee']) : $merchantWallet->balance + $amount;
                $merchantWallet->save();
            }

            DB::commit();
            
            (new NotifyAdminOnPaymentMailService())->send($merchantPayment, ['type' => 'payment', 'medium' => 'email', 'fee_bearer' => $transInfo?->app?->merchant?->merchant_group?->fee_bearer, 'fee' => $feesLimit['totalFee']]);
            (new NotifyMerchantOnPaymentMailService())->send($merchantPayment, ['fee_bearer' => $transInfo?->app?->merchant?->merchant_group?->fee_bearer, 'fee' => $feesLimit['totalFee']]);

            $successPath = $transInfo->success_url . '?' . base64_encode(json_encode([
                'status' => 200,
                'transaction_id' => $merchantPayment->uuid,
                'merchant' => $merchantPayment->merchant->user->first_name . ' ' . $merchantPayment->merchant->user->last_name,
                'currency' => $merchantPayment->currency->code,
                'fee' => $merchantPayment->charge_percentage,
                'amount' => $merchantPayment->amount,
                'total' => $merchantPayment->total,
            ]));
            
            return [
                'status' => 200,
                'successPath' => $successPath
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $data['status'] = 407;
            $data['message']     = $e->getMessage();
            $data['cancelPath']  = $transInfo->cancel_url;
            return $data;
        }
    }

    protected function returnError($status, $message = '', $cancelPath = '')
    {
        $data = ['status' => $status];

        switch ($status) {
            case 403:
                $data['message'] = 'Url has been deleted or expired.';
                break;
            case 404:
                $data['message'] = "Currency does not exist in the system!";
                break;
            case 405:
                $data['message'] = "User doesn't have the wallet - {$message}. Please exchange to wallet - {$message}!";
                break;
            case 407:
                $data['message'] = $message;
                $data['cancelPath'] = $cancelPath;
                break;
            default:
                // Handle unexpected error
                break;
        }

        return $data;
    }

    public function cancelPayment()
    {
        $grant_id  = request('grant_id');
        $token     = request('token');

        $rules = array(
            'grant_id'  => 'required',
            'token'     => 'required',
        );

        $fieldNames = array(
            'grant_id' => 'Grant id',
            'token'    => 'Token'
        );

        $validator = Validator::make(request()->all(), $rules);
        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            $response['status']  = 409;
            $response['message'] = $validator->messages();
            return response()->json(['error' => $response]);
        }
        $trans = AppTransactionsInfo::where(['grant_id' => $grant_id, 'token' => $token])->first(['id', 'status', 'cancel_url']);
        if (empty($trans)) {
            $response['status']  = 410;
            $response['message'] = __('Invalid grant id or token');
            return response()->json(['error' => $response]);
        }
        if ($trans->status == 'cancel') {
            $data['status'] = 412;
            $data['message'] = __('The payment is already canceled');
            $data['cancelUrl'] = $trans->cancel_url;
            return response()->json(['success' => $data]);
        }
        $trans->status = 'cancel';
        $trans->save();
        $data['status'] = 200;
        $data['message'] = __('Payment cancelled successfully');
        $data['cancelUrl'] = $trans->cancel_url;

        return response()->json(['success' => $data]);
    }

    protected function getTransactionData($grantId,$token)
    {
        return AppTransactionsInfo::with([
            'app:id,merchant_id',
            'app.merchant:id,user_id,merchant_group_id,business_name,fee',
            'app.merchant.merchant_group:id,fee_bearer',
            'app.merchant.user:id,first_name,last_name,status',
        ])
        ->where(['grant_id' => $grantId, 'token' => $token, 'status' => 'pending'])->where('expires_in', '>=', time())
        ->first(['id', 'app_id', 'payment_method', 'currency', 'amount', 'success_url', 'cancel_url']);
    }   

    public function getAuthUserIds()
    {
        $value  = isset($_SERVER['HTTP_AUTHORIZATION_TOKEN']) ? $_SERVER['HTTP_AUTHORIZATION_TOKEN'] : null;
        $parser = new JwtParser(new JoseEncoder());
        $id     = $parser->parse($value)->claims()->get('jti');
        $userId = DB::table('oauth_access_tokens')->where('id', $id)->first()->user_id;
        return $userId;
    }

    public function getAuthUserId()
    {
        $authorizationToken = request()->header('Authorization');
        $id = null;

        if ($authorizationToken) {
            $parsedToken = (new JwtParser(new JoseEncoder()))->parse($authorizationToken);
            $id = $parsedToken->claims()->get('jti');
        }

        if ($id) {
            $userId = DB::table('oauth_access_tokens')->where('id', $id)->value('user_id');
            return $userId;
        }

        return null;
    }
}
