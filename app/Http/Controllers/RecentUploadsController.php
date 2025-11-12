<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;

class RecentUploadsController extends Controller
{
    public function index()
    {
        $uploads = Upload::orderBy('uploaded_at', 'desc')->get();

        return response()->json(['uploads' => $uploads]);
    }
}