<?php
namespace Modules\Cooperative\Services;

use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cooperative\Repositories\Contracts\{
    CooperativeApiKeyRepositoryInterface,
    CooperativeApiPermissionRepositoryInterface,
    CoopMemberRepositoryInterface
};
use Modules\Cooperative\Repositories\CooperativeApiKeyRepository;
use Modules\Cooperative\Models\Cooperative;
use App\Models\User;

class AuthCooperative
{
    use ResponseTrait;

    public function __construct(
        protected CooperativeApiKeyRepository $apiKeyRepo,
        protected CooperativeApiPermissionRepositoryInterface $permissionRepo,
        protected CoopMemberRepositoryInterface $memberRepo,
    ) {}

    public function generateApiKey(
    Cooperative $cooperative,
    array $data
    ) {
        DB::beginTransaction();

        try {
            if ($cooperative->owner_id != auth()->id()) {
                return $this->error_response('Unauthorized', 403);
            }
            $plainKey = 'coop_' . Str::random(40);

            $key = $this->apiKeyRepo->create([
                'cooperative_id' => $cooperative->id,
                'name' => $data['name'] ?? 'Default API Key',
                'key_hash' => hash('sha256', $plainKey),
                'abilities' => $data['abilities'] ?? [],
            ]);

            DB::commit();

            return $this->success_response([
                'api_key' => $plainKey, // ⚠️ return once
            ], 'API key generated', 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response($e->getMessage(), 400);
        }
    }

    public function getKeys(Cooperative $cooperative)
    {

        return $this->success_response(
            $this->apiKeyRepo->findBy('cooperative_id', $cooperative->id),
            'API keys retrieved'
        );
    }
    public function deleteKey(Cooperative $cooperative, $keyId)
    {
        $key = $this->apiKeyRepo->find($keyId);
        if ($key && $key->cooperative_id == $cooperative->id) {
            $this->apiKeyRepo->delete($keyId);
            return $this->success_response(null, 'API key deleted');
        }
        return $this->error_response('API key not found or unauthorized', 404);
    }

    public function createApiPermission(array $data)
    {
        try {
            $permission = $this->permissionRepo->create([
                'permission_name' => $data['permission_name'],
            ]);

            return $this->success_response(
                $permission,
                'API permission created',
                201
            );

        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), 400);
        }
    }

    public function createMember(Cooperative $cooperative, array $data)
    {
        DB::beginTransaction();

        try {
            $member = $this->memberRepo->create([
                'cooperative_id' => $cooperative->id,
                'user_id' => $data['user_id'] ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'bvn' => $data['bvn'] ?? null,
                'phone_number' => $data['phone_number'],
                'email' => $data['email'],
                'status' => 'pending',
            ]);

            DB::commit();

            return $this->success_response(
                $member,
                'Member added successfully',
                201
            );

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response($e->getMessage(), 400);
        }
    }


}