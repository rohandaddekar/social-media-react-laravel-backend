<?php

namespace App\Traits;

trait ApiResponse{
  public function successResponse($msg, $data, $code = 200){
    return response()->json([
      'sucess' => true,
      'message' => $msg,
      'data' => $data
    ], $code);
  }

  public function errorResponse($msg, $error, $code = 500){
    return response()->json([
      'sucess' => false,
      'message' => $msg,
      'error' => $error
    ], $code);
  }
}