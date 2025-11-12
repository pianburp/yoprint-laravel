<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessCsvUpload;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('uploads', $fileName);

        $upload = Upload::create([
            'file_name' => $fileName,
            'status' => 'pending',
        ]);

        // Dispatch the job to process the file
        ProcessCsvUpload::dispatch($upload);

        return response()->json(['message' => 'File uploaded successfully', 'upload' => $upload], 201);
    }
}