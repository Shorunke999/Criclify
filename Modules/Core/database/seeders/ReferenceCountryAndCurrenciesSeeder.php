<?php

namespace Modules\Core\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use PragmaRX\Countries\Package\Countries;
use PragmaRX\Countries\Package\Services\Config;

class ReferenceCountryAndCurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $response = Http::get('https://restcountries.com/v3.1/all',[
            'fields' => 'name,cca2,currencies,idd'
        ]);

            if (! $response->successful()) {
                throw new Exception('Failed to fetch countries data');
            }

            $countries = $response->json();

            foreach ($countries as $country) {
                $isoCode = $country['cca2'] ?? null;
                $currencies = $country['currencies'] ?? [];

                if (! $isoCode || empty($currencies)) {
                    continue;
                }

                foreach ($currencies as $currencyCode => $currencyData) {
                    $currency = DB::table('reference_currencies')
                        ->where('code', $currencyCode)
                        ->first();

                    if (! $currency) {
                        DB::table('reference_currencies')->insertGetId([
                            'code' => $currencyCode,
                            'name' => $currencyData['name'] ?? $currencyCode,
                            'symbol' => $currencyData['symbol'] ?? '',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $primaryCurrencyCode = array_key_first($currencies);
                $primaryCurrency = DB::table('reference_currencies')
                    ->where('code', $primaryCurrencyCode)
                    ->first();

                DB::table('reference_countries')->updateOrInsert(
                    ['iso_code' => $isoCode],
                    [
                        'name' => $country['name']['common'] ?? null,
                        'phone_code' => $country['idd']['root'].$country['idd']["suffixes"][0] ?? null,
                        'reference_currency_id' => $primaryCurrency->id ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });
    }

}
