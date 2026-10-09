<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncOutsourceDirectoryRequest;
use App\Services\OutsourceDirectorySyncService;
use Illuminate\Http\JsonResponse;

class OutsourceDirectorySyncController extends Controller
{
    public function sync(
        SyncOutsourceDirectoryRequest $request,
        OutsourceDirectorySyncService $syncService,
    ): JsonResponse {
        $result = $syncService->sync(
            $request->validated('people'),
            (bool) $request->validated('dry_run', false),
        );

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }
}
