<?php

namespace App\Http\Controllers\Refactor\Gate;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Refactor\Gate\StoreGateRequest;
use App\Http\Requests\Refactor\Gate\UpdateGateRequest;
use App\Http\Resources\Refactor\Gate\GateResource;
use App\Http\Resources\Refactor\Gate\SingleGateResource;
use App\Models\Gate;
use App\Repositories\Interfaces\GateRepositoryInterface;
use Illuminate\Http\Request;

class GateController extends Controller
{
    use ApiResponseHelper;

    protected $gateRepository;

    public function __construct(GateRepositoryInterface $gateRepository)
    {
        $this->gateRepository = $gateRepository;
    }

    public function index()
    {
        $gates = ($this->gateRepository->all());

        return $this->setCode(code: 200)->setData(new GateResource($gates))->setMessage('success')->send();
    }

    public function show(Request $request, $id)
    {
        $gate = $this->gateRepository->find($id);

        return $this->setCode(200)
            ->setData(new SingleGateResource($gate, $request->input('page', 1), $request->input('per_page', 10)))
            ->setMessage('success')
            ->send();
    }

    public function store(StoreGateRequest $request)
    {

        $gate = ($this->gateRepository->create($request->validated()));

        return $this->setCode(code: 200)->setData(new GateResource($gate))->setMessage('success')->send();
    }

    public function update(UpdateGateRequest $request, Gate $gate)
    {
        $gate = $this->gateRepository->update($gate, $request->validated());

        return $this->setCode(code: 200)->setData(new GateResource($gate))->setMessage('success')->send();
    }

    public function destroy($id)
    {
        return $this->gateRepository->delete($id)
            ? response()->json(['message' => 'Deleted Successfully'])
            : response()->json(['message' => 'Not Found'], 404);
    }
}
