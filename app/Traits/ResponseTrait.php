<?php

namespace App\Traits;

use App\Enums\LogType;
use App\Models\Log as ModelsLog;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Auth;
use Throwable;

trait ResponseTrait
{
    public function success_response($data, string $message = "Success", int $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function error_response(string $message = "Error", int $code = 500, bool $log = false, ?LogType $type = null)
    {
        $this->logInfo($log, $message, $type);
        return response()->json([
            'status' => 'failed',
            'message' => $message,
        ], (intval($code) > 600) ? 500 : $code);
    }

    public function reportError(
        Throwable $e,
        string $module,
        array $metadata = []

    ):void{
        Bugsnag::notifyException($e,function($report) use ($module,$metadata,$e){
            $report->setContext($e->getMessage());
            $report->addMetaData([
                'module'=>[
                    'name' => $module
                ],
                'context' => $metadata
            ]);
        });
    }
    public function logInfo(bool $log, string $message, ?LogType $type = null): bool
    {
        if ($log) {
            ModelsLog::create([
                'type' => $type,
                'message' => $message,
                'user_id' => Auth::id() ?? null
            ]);
        }
        return true;
    }
}
