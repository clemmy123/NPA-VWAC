<?php

namespace App\Http\Controllers;

use App\Support\AdminLocationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLocationController extends Controller
{
    public function options(Request $request, string $level): JsonResponse
    {
        return response()->json(AdminLocationLevel::options($level, $request->integer('parent_id') ?: null));
    }
}
