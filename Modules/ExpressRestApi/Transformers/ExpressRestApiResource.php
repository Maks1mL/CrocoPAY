<?php

namespace Modules\ExpressRestApi\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpressRestApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            "status" => $this['status'],
            "user_id" => $this['merchant_id'],
            "amount" => $this['amount'],
            "currencySymbol" => $this['currencySymbol'],
            "currencyCode" => $this['currencyCode'],
            "currencyType" => $this['currencyType'],
            "total" => $this['total'],
            "totalAmount" => $this['total_amount'],
            "transaction_type" => $this['transaction_type'],
            "payment_method" => $this['payment_method'],
            "gateway" => $this['gateway'],
            "uuid" => $this['uuid'],
            "transaction_id" => $this['transaction_id'],
            "successurl" => $this['successurl'],
        ];
    }
}
