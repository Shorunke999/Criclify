<?php

namespace App\Http\Controllers;

use App\Enum\LogType;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{

    public function success_response($data, $message = "Sucess",$code = 200){
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ],$code);
    }

    public function error_response($message = "Error", $code = 500,$log = false,LogType $type = null)
    {
       $this->logInfo($log,$message,$type);
        return response()->json([
            'status' => 'failed',
            'message' => $message,
        ],$code);
    }

    public function logInfo($log,$message, LogType $type = null):bool
    {
        if($log){
            Log::create([
                'type' => $type,
                'message' => $message,
                'user_id' => Auth::id() ?? null
            ]);
        }
        return true;
    }
}
