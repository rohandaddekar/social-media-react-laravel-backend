<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait FileUpload{
  public function fileUpload($files){
    $uploadedFiles = [];

    foreach($files as $file){
      $response = Http::attach(
          'file',
          file_get_contents($file->getRealPath()),
          $file->getClientOriginalName()
        )->post(
          env('CLOUDINARY_API_BASE_URL') . 
          env('CLOUDINARY_CLOUD_NAME') . 
          '/image/upload', 
          ['upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),]
        );

      if($response->successful()){
        $uploadedFiles[] = $response->json()['secure_url'];
      } else {
        throw new \Exception("Failed to upload files: " . $response->status() . " - " . $response->body());
      }
    }

    return $uploadedFiles;
  }
}