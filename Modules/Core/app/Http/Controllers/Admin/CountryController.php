<?php

namespace Modules\Core\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Core\Http\Requests\StoreCountryRequest;
use Modules\Core\Services\CountryService;

class CountryController extends Controller
{
    public function __construct(
        protected CountryService $countryService
    ) {}

    /**
     * List all available countries with currency
     *
     */
    public function index()
    {
        return $this->countryService->listActiveWithCurrency();
    }
    /**
     * Store countries with currency
     *
     */
    public function store(StoreCountryRequest $request)
    {
        return $this->countryService->createCountry($request->validated());
    }

    /**
     * Update countries with currency
     *
     */
    public function update(StoreCountryRequest $request, int $id)
    {
        return $this->countryService->updateCountry($id, $request->validated());
    }
}

