<?php


namespace App\Repositories\GateSug;


use App\Models\SubCategorySuggestion;
use App\Repositories\Interfaces\SubCategorySuggestionRepositoryInterface;

class SubCategorySuggestionRepository implements SubCategorySuggestionRepositoryInterface
{
    public function all()
    {
        return SubCategorySuggestion::all();
    }

    public function find($id)
    {
        return SubCategorySuggestion::findOrFail($id);
    }

    public function create(array $data)
    {
        // Check if the product already exists for the given gate
        $exists = SubCategorySuggestion::where('subCategory_id', $data['subCategory_id'])
            ->where('product_id', $data['product_id'])
            ->exists();

        if ($exists) {
            return
                SubCategorySuggestion::where('subCategory_id', $data['subCategory_id'])
                ->where('product_id', $data['product_id'])
                ->first();
        }

        // If not exist, add it
        return SubCategorySuggestion::create($data);
    }

    public function update($id, array $data)
    {
        $subCategorySuggestion = $this->find($id);

        $exists = SubCategorySuggestion::where('subCategory_id', $data['subCategory_id'])
            ->where('product_id', $data['product_id'])
            ->where('id', '!=', $id) // Exclude the current record
            ->exists();

        if ($exists) {
            return
                SubCategorySuggestion::where('subCategory_id', $data['subCategory_id'])
                ->where('product_id', $data['product_id'])
                ->first();
        }

        $subCategorySuggestion->update($data);
        return $subCategorySuggestion;
    }

    public function delete($id)
    {
        $subCategorySuggestion = $this->find($id);
        return $subCategorySuggestion->delete();
    }
}
