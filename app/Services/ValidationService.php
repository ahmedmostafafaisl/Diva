<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ValidationService
{

    public function checkRecordExists(string $modelClass, int $id)
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided class is not a valid Eloquent model.',
            ], 400);
        }

        try {
            return $modelClass::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "status" => 404,
                'success' => false,
                'message' => class_basename($modelClass) . ' not found.',

            ], 404);
        }
    }
}
