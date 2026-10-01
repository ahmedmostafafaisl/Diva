<?php

namespace App\Http\Controllers\Refactor\Sug;

use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\GateSuggestionInterface;
use App\Http\Resources\Refactor\Sug\GateSuggestionResource;
use App\Http\Requests\Refactor\Sug\StoreGateSuggestionRequest;
use App\Http\Requests\Refactor\Sug\UpdateGateSuggestionRequest;

class GateSuggestionController extends Controller
{
    use ApiResponseHelper;

    protected $repository;

    public function __construct(GateSuggestionInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return $this->setCode(code: 200)->setData(GateSuggestionResource::collection($this->repository->getAll()))->setMessage('success')->send();
    }

    public function store(StoreGateSuggestionRequest $request)
    {
        $suggestion = $this->repository->create($request->validated());
        return $this->setCode(code: 200)->setData(new GateSuggestionResource($suggestion))->setMessage('Suggestion Created successfully')->send();
    }

    public function show($id)
    {
        $suggestion = $this->repository->findById($id);
        return $this->setCode(code: 200)->setData(new GateSuggestionResource($suggestion))->setMessage('success')->send();
    }

    public function update(UpdateGateSuggestionRequest $request, $id)
    {
        $suggestion = $this->repository->update($id, $request->validated());
        return $this->setCode(code: 200)->setData(new GateSuggestionResource($suggestion))->setMessage('Suggestion Updated successfully')->send();
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return $this->setCode(code: 200)->setData([])->setMessage('Gate suggestion deleted successfully')->send();
    }
}
