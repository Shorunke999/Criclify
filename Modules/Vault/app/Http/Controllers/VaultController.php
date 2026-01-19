<?php

namespace Modules\Vault\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vault\Services\VaultService;
use Modules\Vault\Http\Requests\StoreVaultRequest;
use Modules\Vault\Models\Vault;
class VaultController extends Controller
{
    public function __construct(
        protected VaultService $vaultService
    ) {}

    public function store(StoreVaultRequest $request)
    {
        return $this->vaultService->createVault(
            $request->validated(),
            auth()->id()
        );
    }

    public function index(Request $request)
    {
        return $this->vaultService->getUserVaults(
            $request->all(),
            auth()->id()
        );
    }

    public function show(Vault $vault)
    {
        return $this->vaultService->getVaultDetails(
            $vault,
            auth()->id()
        );
    }

    public function pay(Vault $vault)
    {
        return $this->vaultService->payForVault(
            $vault,
            auth()->id()
        );
    }

    public function disburse(Vault $vault)
    {
        return $this->vaultService->disbursedTowallet(
            $vault,
            auth()->id()
        );
    }
}
