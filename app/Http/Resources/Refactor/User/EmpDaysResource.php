<?php

namespace App\Http\Resources\Refactor\User;

use App\Http\Resources\Refactor\WorkShift\WorkShiftResource;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpDaysResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'day_name' => $this->day_name,
            'status' => $this->status,
            'shifts' => WorkShiftResource::collection($this->shifts),
        ];
    }
}
