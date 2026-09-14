<?php

namespace App\Http\Controllers;

use App\Support\AdminLocationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLocationController extends Controller
{
    public function options(Request $request, string $level): JsonResponse
    {
        $parentLevel = $request->input('parent_level');

        return response()->json(AdminLocationLevel::options(
            $level,
            $request->integer('parent_id') ?: null,
            is_string($parentLevel) && $parentLevel !== '' ? $parentLevel : null,
        ));
    }
}
