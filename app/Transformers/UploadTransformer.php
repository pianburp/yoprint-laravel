<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class UploadTransformer extends TransformerAbstract
{
    public function transform($upload)
    {
        return [
            'id' => $upload->id,
            'file_name' => $upload->file_name,
            'status' => $upload->status,
            'uploaded_at' => $upload->uploaded_at->toDateTimeString(),
        ];
    }
}