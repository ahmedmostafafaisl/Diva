<?php

namespace App\Http\Controllers\Refactor\Review;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\Refactor\Review\ReviewResource;
use App\Http\Requests\Refactor\Review\StoreReviewRequest;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected $reviewRepository;

    public function __construct(ReviewRepositoryInterface $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function index()
    {
        return ReviewResource::collection($this->reviewRepository->getAll());
    }

    public function store(StoreReviewRequest $request)
    {
        $review = $this->reviewRepository->create($request->validated());
        return new ReviewResource($review);
    }

    public function show($id)
    {
        return new ReviewResource($this->reviewRepository->findById($id));
    }

    public function update(Request $request, $id)
    {
        $review = $this->reviewRepository->update($id, $request->validated());
        return new ReviewResource($review);
    }

    public function destroy($id)
    {
        $this->reviewRepository->delete($id);
        return response()->json(['message' => 'Review deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
