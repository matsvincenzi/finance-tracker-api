<?php

namespace App\Http\Resources;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Copied from the category. */
            'type' => $this->type,
            'description' => $this->description,
            /** Amount in the original currency. */
            'amount' => $this->amount,
            'currency' => $this->currency,
            /** BRL per unit of `currency` on `occurred_on` (1 for BRL transactions). */
            'exchange_rate' => $this->exchange_rate,
            'amount_brl' => $this->amount_brl,
            'occurred_on' => $this->occurred_on->toDateString(),
            'account' => $this->whenLoaded('account', fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
            ]),
            'category' => $this->whenLoaded('category', fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
