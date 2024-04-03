<?php

namespace Modules\ExpressRestApi\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpressTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            "user_id" => $this->user_id,
            "currency" => $this->currency->name,
            "currency_code" => $this->currency->code,
            "currency_symbol" => $this->currency->symbol,
            "payment_method_id" => getPaymentMethodName($this->payment_method),
            "business_name" => $this->merchant->business_name,
            "merchant_uuid" => $this->merchant->merchant_uuid,
            "transaction_uuid" => $this->uuid,
            "transaction_type_name" => ucfirst(str_replace('_', ' ', $this->transaction_type->name)),
            "user_type" => $this->user_type,
            "subtotal" => number_format($this->subtotal),
            "percentage" => number_format($this->percentage),
            "charge_percentage" => number_format($this->charge_percentage),
            "charge_fixed" => number_format($this->charge_fixed),
            "total" => number_format($this->total),
            "status" => $this->status,
        ];
    }
}
