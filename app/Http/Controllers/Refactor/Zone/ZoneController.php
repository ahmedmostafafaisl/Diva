<?php

namespace App\Http\Controllers\Refactor\Zone;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refactor\Zone\ZoneRequest;
use App\Http\Resources\Refactor\Zone\ZoneResource;
use App\Repositories\Interfaces\ZoneRepositoryInterface;

class ZoneController extends Controller
{
    protected $zoneRepo;

    public function __construct(ZoneRepositoryInterface $zoneRepo)
    {
        $this->zoneRepo = $zoneRepo;
    }

    public function index()
    {
        $zones = $this->zoneRepo->all();
        return response()->json(ZoneResource::collection($zones));
    }

    public function store(ZoneRequest $request)
    {
        $zone = $this->zoneRepo->create($request->validated());
        return response()->json(new ZoneResource($zone), 201);
    }

    public function show($id)
    {
        $zone = $this->zoneRepo->find($id);
        return response()->json(new ZoneResource($zone));
    }

    public function update(ZoneRequest $request, $id)
    {
        $zone = $this->zoneRepo->update($id, $request->validated());
        return response()->json(new ZoneResource($zone));
    }

    public function destroy($id)
    {
        $this->zoneRepo->delete($id);
        return response()->json(['message' => 'Zone deleted successfully.']);
    }
}
