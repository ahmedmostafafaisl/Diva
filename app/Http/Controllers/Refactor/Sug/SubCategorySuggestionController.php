<?php

namespace App\Http\Controllers\Refactor\Sug;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSubCategorySuggestionRequest;
use App\Http\Resources\Refactor\Sug\SubCategorySuggestionResource;
use App\Repositories\Interfaces\SubCategorySuggestionRepositoryInterface;
use App\Http\Requests\Refactor\Suggestion\StoreSubCategorySuggestionRequest;


class SubCategorySuggestionController extends Controller
{
    protected $repository;

    public function __construct(SubCategorySuggestionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(): JsonResponse
    {
        $subCategorySuggestions = $this->repository->all();
        return response()->json(SubCategorySuggestionResource::collection($subCategorySuggestions));
    }

    public function show($id): JsonResponse
    {
        $subCategorySuggestion = $this->repository->find($id);
        return response()->json(new SubCategorySuggestionResource($subCategorySuggestion));
    }

    public function store(StoreSubCategorySuggestionRequest $request): JsonResponse
    {
        $subCategorySuggestion = $this->repository->create($request->validated());
        return response()->json(new SubCategorySuggestionResource($subCategorySuggestion), 201);
    }

    public function update(StoreSubCategorySuggestionRequest $request, $id): JsonResponse
    {
        $subCategorySuggestion = $this->repository->update($id, $request->validated());
        return response()->json(new SubCategorySuggestionResource($subCategorySuggestion));
    }

    public function destroy($id): JsonResponse
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Deleted successfully'], 200);
    }
}
