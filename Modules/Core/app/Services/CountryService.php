<?php

namespace  Modules\Core\Services;

use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Core\Repositories\Contracts\CountryRepositoryInterface;
use Modules\Core\Repositories\Contracts\CurrencyRepositoryInterface;

class CountryService
{
    use ResponseTrait;
    public function __construct(
        protected CountryRepositoryInterface $countryRepo,
        protected CurrencyRepositoryInterface $currencyRepo
    ){}

     public function createCountry(array $data)
    {
        DB::beginTransaction();

        try {

            $currency = $this->currencyRepo->firstOrCreate(
                ['code' => $data['currency_code']],
                [
                    'name' => $data['currency_name'],
                    'symbol' => $data['currency_symbol'] ?? null,
                ]
            );

            $country = $this->countryRepo->create([
                'name' => $data['name'],
                'iso_code' => $data['iso_code'],
                'currency_id' => $currency->id,
                'platform_fee_percentage' => $data['platform_fee_percentage'],
                'circle_creation_fee_percentage' => $data['circle_creation_fee_percentage'],
            ]);

            DB::commit();

            return $this->success_response($country, 'Country created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response($e->getMessage(), 400);
        }
    }

    public function updateCountry(int $id, array $data)
    {
        $country = $this->countryRepo->find($id);
        $currencyData = [
            'currency_name' => $data['currency_name'],
            'currency_code' => $data['currency_code'],
            'currency_symbol' => $data['currency_symbol'],
        ];
        unset($data['currency_name'],$data['currency_code'],$data['currency_symbol']);
        $country->update($data);
        $country->currency()->update($currencyData);

        return $this->success_response($country, 'Country updated successfully');
    }

     public function listActiveWithCurrency()
    {
        return $this->countryRepo->findBy('is_active',true,relations:['currency']);
    }

}
