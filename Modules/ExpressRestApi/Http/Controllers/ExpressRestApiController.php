<?php

namespace Modules\ExpressRestApi\Http\Controllers;

use Exception;
use App\Models\Merchant;
use App\Http\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\AppTransactionsInfo;
use Illuminate\Support\Facades\Http;
use App\Services\MerchantPaymentService;
use Illuminate\Contracts\Support\Renderable;
use Modules\ExpressRestApi\Http\Requests\GeneratedUrlRequest;
use Modules\ExpressRestApi\Transformers\ExpressRestApiResource;
use Modules\ExpressRestApi\Services\ExpressMerchantPaymentService;
use App\Services\Mail\MerchantPayment\NotifyAdminOnPaymentMailService;
use App\Services\Mail\MerchantPayment\NotifyMerchantOnPaymentMailService;
use App\Models\Currency;

class ExpressRestApiController extends Controller
{
    protected $restApiService, $helper, $merchantService;

    public function __construct(ExpressMerchantPaymentService $restApiService, MerchantPaymentService $merchantService)
    {
        $this->helper = new Common;
        $this->restApiService = $restApiService;
        $this->merchantService = $merchantService;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(GeneratedUrlRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $data = $this->restApiService->getMerchantPaymentInfo($validatedData['grant_id'], $validatedData['token']);
            $data['paymentInfo'] = array_merge($request->all(), getPaymentData());
            setPaymentData($data['paymentInfo']);
            
            $data['currency'] = $this->helper->getCurrencyObject(['id' => $data['paymentInfo']['currency_id']], ['id', 'symbol', 'code', 'type', 'exchange_from', 'rate']);

            return view('expressrestapi::index', $data);
        } catch (Exception $e) {
            Common::one_time_message('error', $e->getMessage());
            return redirect('payment/fail');
        }
    }

    public function create(Request $request)
    {
        $data = getPaymentData();
        $paymentData = array_merge($data, $request->all());
        setPaymentData($paymentData);
        if ($request->method == 'Mts') {
            return $this->initiateMts();
        }
        
        $data['merchantInfo'] = Merchant::with('user')->find($data['merchant_id']);
        
        // dd($data);
        return view('expressrestapi::gateways.p2pkassa', $data);
    }

        /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function initiateMts()
    {
        try {
            $data = getPaymentData();

            $merchantCurrencyId = $data['currency_id'];

            $paymentMethod = PaymentMethod::whereName('Mts')->first(['id', 'name']);
            $methodId = $paymentMethod['id'];

            $toCurrency = $this->helper->getCurrencyObject(['id' => $merchantCurrencyId], ['id', 'symbol', 'code', 'type', 'exchange_from', 'rate']);


            $totalAmount = $data['amount'];

            $merchantCheck = Merchant::with('merchant_group:id,fee_bearer')->find($data['merchant_id'], ['id', 'user_id', 'status', 'fee', 'type', 'merchant_group_id']);

            if (optional($merchantCheck->merchant_group)->fee_bearer == 'User') {
                $feesLimit = $this->restApiService->checkMerchantPaymentFeesLimit($merchantCurrencyId, $methodId, $data['amount'], $merchantCheck->fee);
                $totalAmount = $feesLimit['totalFee'] + $data['amount'];
            }
            
            $paymentData = [
                'currency_id' =>  $merchantCurrencyId,
                'currencyCode' => $data['currency'],
                'total' => $totalAmount,
                'totalAmount' => $totalAmount,
                'transaction_type' => Payment_Sent,
                'payment_type' => 'deposit',
                'payment_method' =>  $methodId,
                'redirectUrl' => route('restapi.payment.success'),
                'cancel_url' => url('payment/fail'),
                'gateway' => strtolower($data['method']),
                'uuid' => unique_code(),
                "type" => "express"
            ];

            $paymentData = array_merge($data, $paymentData);
            
            return redirect(gatewayPaymentUrl($paymentData));
        } catch (Exception $e) {
            Common::one_time_message('error', $e->getMessage());
            return redirect('payment/fail');
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function initiate(Request $request)
    {
        try {
            $data = getPaymentData();
            
            $merchantCurrencyId = $data['currency_id'];
            $toCurrency = $this->helper->getCurrencyObject(['code' => $request->country], ['id', 'symbol', 'code', 'type', 'exchange_from', 'rate']);

            if (empty($toCurrency)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Requested currency not registered in this system.'
                ], 401);
            }

            $paymentMethod = PaymentMethod::whereName($data['method'])->first(['id', 'name']);
            $methodId = $paymentMethod['id'];

            if ($data['currency'] == $toCurrency->code) {
                $totalAmount = request()->amount;
            } else {
                if ($toCurrency->exchange_from == "api" && isEnabledExchangeApi()){
                    $toWalletRate = getApiCurrencyRate($data['currency'], $toCurrency->code);
                    if ($toWalletRate == 'error') {
                        $fromWalletCurrency = $this->helper->getCurrencyObject(['code' => $data['currency']], ['rate']);
                        $toWalletRate = getLocalCurrencyRate($fromWalletCurrency->rate, $toCurrency->rate);
                    }
                } else {
                    
                    $fromWalletCurrency = $this->helper->getCurrencyObject(['code' => $data['currency']], ['rate']);
                    $toWalletRate = getLocalCurrencyRate($fromWalletCurrency->rate, $toCurrency->rate);
                }
                $totalAmount = request()->amount * $toWalletRate;
            }

            $merchantCheck = Merchant::with('merchant_group:id,fee_bearer')->find($data['merchant_id'], ['id', 'user_id', 'status', 'fee', 'type', 'merchant_group_id']);

            if (optional($merchantCheck->merchant_group)->fee_bearer == 'User') {
                $feesLimit = $this->restApiService->checkMerchantPaymentFeesLimit($merchantCurrencyId, $methodId, request()->amount, $merchantCheck->fee);
                $totalAmount = $feesLimit['totalFee'] + request()->amount;
            }
            
            $paymentData = [
                'currency_id' =>  $merchantCurrencyId,
                'currencySymbol' => $toCurrency->symbol,
                'currencyCode' => $toCurrency->code,
                'currencyType' => $toCurrency->type,
                'amount' => request()->amount,
                'paidamount' => $totalAmount,
                'total' => request()->amount,
                'total_amount' => $totalAmount,
                'transaction_type' => Payment_Sent,
                'payment_type' => 'deposit',
                'payment_method' =>  $methodId,
                'redirectUrl' => route('restapi.payment.success'),
                'cancel_url' => url('payment/fail'),
                'gateway' => strtolower($data['method']),
                'uuid' => unique_code(),
                "type" => "express",
                "country" => $request->country,
            ];
            
            $paymentData = array_merge($data, $paymentData);
            
            return redirect(gatewayPaymentUrl($paymentData));
        } catch (Exception $e) {
            Common::one_time_message('error', $e->getMessage());
            return redirect('payment/fail');
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {

            DB::beginTransaction();

            $data = getPaymentParam(request()->params);

            isGatewayValidMethod($data['payment_method']);

            $sender = isset($request->user) ? $request->user : null;

            $amount            = $data['amount'];
            $merchant          = $data['merchant_id'];
            $item_name         = $data['item_name'];
            $order_no          = $data['order'];
            $currencyId        = $data['currency_id'];
            $payment_method_id = $data['payment_method'];
            $uniqueCode        = $data['uuid'];
            
            $request->merge(['amount' => $amount, 'merchant' => $merchant, 'order_no' => $order_no, 'item_name' => $item_name]);

            $merchantCheck = Merchant::with('merchant_group:id,fee_bearer')->find($merchant, ['id', 'user_id', 'status', 'fee', 'merchant_group_id']);

            if (!$merchantCheck || $merchantCheck->status != 'Approved') {
                throw new Exception(__('Merchant not found!'));
            }

            $successPaymentMethods = [Stripe, Paypal, Mts, P2PKassa];

            $status = in_array($payment_method_id, $successPaymentMethods) ? 'Success' : 'Pending';

            //Deposit + Merchant Fee
            $feesLimit = $this->merchantService->checkMerchantPaymentFeesLimit($currencyId, $payment_method_id, $amount, $merchantCheck->fee);

            //Merchant payment
            $merchantPayment = $this->merchantService->makeMerchantPayment($request, $merchantCheck, $feesLimit, $currencyId, $uniqueCode, $uniqueCode, $payment_method_id, $status);

            //Merchant Transaction
            $transaction = $this->merchantService->makeMerchantTransaction($request, $merchantCheck, $feesLimit,  $currencyId, $uniqueCode, $merchantPayment, $payment_method_id, $status);

            if (!is_null($sender)) {

                if ($merchantCheck->user_id == $sender) {
                    throw new Exception(__('Merchant cannot make payment to himself!'));
                }

                $this->merchantService->makeUserTransaction($request, $merchantCheck, $feesLimit,  $currencyId, $uniqueCode, $merchantPayment, $status);

                $senderWallet = $this->helper->getUserWallet([], ['user_id' => $sender, 'currency_id' =>  $currencyId], ['id', 'balance']);

                $this->merchantService->updateSenderWallet($request->amount, $merchantCheck, $senderWallet);
            }

            if ($status == 'Success') {

                $merchantWallet = $this->helper->getUserWallet([], ['user_id' => $merchantCheck->user_id, 'currency_id' =>  $currencyId], ['id', 'balance']);

                $this->merchantService->createOrUpdateMerchantWallet($request->amount, $merchantCheck, $currencyId, $feesLimit['totalFee'], $merchantWallet);
            }

            DB::commit();

            // Send mail to admin
            (new NotifyAdminOnPaymentMailService())->send($merchantPayment, ['type' => 'payment', 'medium' => 'email', 'fee_bearer' => $merchantCheck->merchant_group->fee_bearer, 'fee' => $feesLimit['totalFee']]);

            // Send mail to merchant
            (new NotifyMerchantOnPaymentMailService())->send($merchantPayment, ['fee_bearer' => $merchantCheck->merchant_group->fee_bearer, 'fee' => $feesLimit['totalFee']]);
            if (isset(request()->execute) && (request()->execute == 'api')) {
                return $transaction->id;
            }
            $data['transaction_id'] = $transaction->id;

            setPaymentData($data);
            return redirect()->route('restapi.payment.redirectUrl');

        } catch (Exception $e) {
            DB::rollback();
            $this->helper->one_time_message('error', $e->getMessage());
            return redirect('payment/fail');
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function redirectUrl(Request $request)
    {
        $data = getPaymentData();
        $grantId = $data['grant_id'];
        $token = $data['token'];

        $transactionInfo = (new AppTransactionsInfo)->getByGrantIdAndToken($grantId, $token);

        if ($transactionInfo) {
            $data['successurl'] = $transactionInfo->success_url;
            $data['status'] = 'Success';
            return response()->json(new ExpressRestApiResource($data), 200);
        } else {
            return response()->json(['message' => 'Transaction info not found'], 404);
        }
    }
    
    public function paymentVerification(Request $request)
    {
        $id_pay = $request->id;
        $project_id = $request->project_id;
        $apikey = $request->apikey;
        
        $data = [
            'id' => $id_pay,
            'project_id' => $project_id,
            'apikey' => $apikey
        ];
        
        $ch = curl_init('https://p2pkassa.online/api/v1/getPayment');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        return $result;
    }
}
