<?php

namespace App\Helper;

class ApiResponse
{
    static function sendResponse($code = 200, $status = null, $message = null, $data = [])
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }
}
