<?php
/**
 * @package P2PKassaProcessor
 * @author tehcvillage <support@techvill.org>
 * @contributor Md Abdur Rahaman Zihad <[zihad.techvill@gmail.com]>
 * @created 21-12-2022
 */
namespace App\Services\Gateways\P2PKassa;
use App\Services\Gateways\Gateway\Exceptions\{
    GatewayInitializeFailedException
};
use App\Services\Gateways\Gateway\PaymentProcessor;
use Exception;
use Illuminate\Support\Facades\Http;
/**
 * @method array pay()
 */
class P2pkassaProcessor extends PaymentProcessor
{
    protected $data;
    protected $p2pkassa;
    /**
     * Initiate the P2PKassa payment process
     *
     * @param array $data
     *
     * @return void
     */
    protected function pay(array $data) : array
    {
        $data['payment_method_id'] = P2PKassa;

        $this->boot($data);

        $response =  $this->initialization(
            $data['total_amount'],
            $data['country']
        );
        
        $response = array_merge($response, [
            "type" => $this->gateway(),
            'redirectUrl' => $data['redirectUrl'],
        ]);
        
        if ($response['status'] == false) {
            throw new GatewayInitializeFailedException(__("P2PKassa initialize failed."));
        }
        return $response;
    }
    /**
     * Boot p2pkassa payment processor
     *
     * @param array $data
     *
     * @return void
     */
    protected function boot($data)
    {
        $this->data = $data;
        $this->paymentCurrency();
        $this->p2pkassa = $this->paymentMethodCredentials();
        if (!$this->p2pkassa->apikey) {
            throw new GatewayInitializeFailedException(__("Stripe initialize failed."));
        }
    }
    public function initialization($amount, $currency) 
    {
        $collection = ['RUB'=> 'ru', 'UAH' => 'ua', 'KZT' => 'kz', 'UZS' => 'uz', 'KGS' => 'kg', 'MDL' => 'md'];
        try {
            $data = [
                'project_id' => $this->p2pkassa->project_id,
                'apikey' => $this->p2pkassa->apikey,
                'order_id' => mt_rand(1, 999999),
                'amount' => $amount,
                'country' => $collection[$currency],
                'method' => 'card',
            ];
            $responseData = Http::get('https://p2pkassa.online/api/v1/json', $data)->body();
            $response = json_decode($responseData);

            return [
                'status' => true,
                'message' => __('success'),
                'id' => $response->id,
                'country' => $response->country,
                'card_number' => $response->card_number,
                'card_name' => $response->card_name ?? '',
                'bank_name' => $response->bank_name ?? '',
                'amount' => $amount,
                'paidamount' => $response->amount,
                'currency' => $response->currency,
                'project_id' => $data['project_id'],
                'apikey' => $data['apikey'],
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    /**
     * Get gateway alias name
     *
     * @return string
     */
    public function gateway(): string
    {
        return "p2pkassa";
    }
    public function verify($request)
    {
        try {
            $data = getPaymentParam($request->params);
            $data['payment_method_id'] = P2PKassa;
            $this->setPaymentType($data['payment_type']);
            $this->boot($data);
            if (true) {
                $payment = callAPI(
                    'GET',
                    $data['redirectUrl'],
                    [
                        'params' => $request->params,
                        'execute' => 'api'
                    ]
                );
                $data ['transaction_id'] = $payment;
                return $data;
            }
            throw new GatewayInitializeFailedException(__("Stripe Payment failed."));
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    /**
     * Method paymentView
     *
     * @return void
     */
    public function paymentView()
    {
        return 'expressrestapi::gateways.confirm';
    }
}