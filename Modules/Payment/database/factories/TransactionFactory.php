<?php

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Payment\Models\Transaction;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id'   => null,
            'circle_id' => null,

            'type'   => TransactionTypeEnum::Contribution,
            'status' => TransactionStatusEnum::Pending,

            'amount'   => $this->faker->randomFloat(2, 1000, 50000),
            'currency' => 'NGN',

            'reference' => (string) Str::uuid(),

            'type_ids' => [],
            'meta'     => [],
        ];
    }
}



