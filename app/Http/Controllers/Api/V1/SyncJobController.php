<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SyncJob;
use Illuminate\Http\Request;

class SyncJobController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(SyncJob $syncJob)
    {
        return response()->json($syncJob);
    }
}