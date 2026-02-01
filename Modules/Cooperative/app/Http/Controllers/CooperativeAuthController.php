<?php

namespace Modules\Cooperative\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Cooperative\Services\AuthCooperative;
use Modules\Cooperative\Http\Requests\{
    GenerateCooperativeApiKeyRequest,
    CreateCooperativeApiPermissionRequest,
};


class CooperativeAuthController extends Controller
{
    public function __construct(
        protected AuthCooperative $authCooperative
    ) {}

    /**
     * Generate API key for cooperative
     */
    public function generateApiKey(
        GenerateCooperativeApiKeyRequest $request
    ) {
        $cooperative = request()->get('cooperative');

        return $this->authCooperative->generateApiKey(
            $cooperative,
            $request->validated()
        );
    }

    /**
     * GetAPI key for cooperative
     */
    public function getKeys(
    ) {
        $cooperative = request()->get('cooperative');

        return $this->authCooperative->getKeys(
            $cooperative
        );
    }

     /**
     * Delete key
     */
    public function deleteKey($keyId) {
        $cooperative = request()->get('cooperative');

        return $this->authCooperative->deleteKey(
            $cooperative,
            $keyId
        );
    }

    /**
     * Superadmin: create API permission
     */
    public function createApiPermission(
        CreateCooperativeApiPermissionRequest $request
    ) {
        return $this->authCooperative->createApiPermission(
            $request->validated()
        );
    }
}

