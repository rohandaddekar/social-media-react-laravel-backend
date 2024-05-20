<?php

namespace App\Traits;

trait ApiResponse{
  public function successResponse($msg, $data, $code = 200){
    $response = [
      'sucess' => true,
      'message' => $msg,
    ];

    if($data !== null){
      $response['data'] = $data;
    }

    return response()->json($response, $code);
  }

  public function errorResponse($msg, $error, $code = 500){
    $response = [
      'sucess' => false,
      'message' => $msg,
    ];

    if($error !== null){
      $response['error'] = $error;
    }

    return response()->json($response, $code);
  }
}