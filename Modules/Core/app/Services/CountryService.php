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

    public function list(array $filters)
    {
        try {
            $perPage = $filters['per_page'] ?? 20;

            $query = DB::table('reference_countries')
                ->join(
                    'reference_currencies',
                    'reference_countries.reference_currency_id',
                    '=',
                    'reference_currencies.id'
                )
                ->select(
                    'reference_countries.id',
                    'reference_countries.name as country_name',
                    'reference_countries.iso_code',
                    'reference_countries.phone_code',
                    'reference_currencies.name as currency_name',
                    'reference_currencies.code as currency_code',
                    'reference_currencies.symbol as currency_symbol'
                );

            /* ----------------------------
             | Search (country or currency)
             -----------------------------*/
            if (! empty($filters['search'])) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('reference_countries.name', 'like', "%{$search}%")
                      ->orWhere('reference_countries.iso_code', 'like', "%{$search}%")
                      ->orWhere('reference_currencies.code', 'like', "%{$search}%")
                      ->orWhere('reference_currencies.name', 'like', "%{$search}%");
                });
            }

            /* ----------------------------
             | Filter by currency code
             -----------------------------*/
            if (! empty($filters['currency_code'])) {
                $query->where(
                    'reference_currencies.code',
                    strtoupper($filters['currency_code'])
                );
            }

            $countries = $query
                ->orderBy('reference_countries.name')
                ->paginate($perPage);

            return $this->success_response(
                $countries,
                'Reference countries retrieved successfully'
            );

        } catch (Exception $e) {
            return $this->error_response(
                'Failed to retrieve reference countries',
                500
            );
        }
    }

}
