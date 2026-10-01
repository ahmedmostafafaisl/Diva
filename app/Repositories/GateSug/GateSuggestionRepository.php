<?php


namespace App\Repositories\GateSug;

use App\Models\GateSuggestion;
use App\Repositories\Interfaces\GateSuggestionInterface;

class GateSuggestionRepository implements GateSuggestionInterface
{
    public function getAll()
    {
        return GateSuggestion::all();
    }

    public function findById($id)
    {
        return GateSuggestion::findOrFail($id);
    }

    public function create(array $data)
    {
        // Check if the product already exists for the given gate
        $exists = GateSuggestion::where('gate_id', $data['gate_id'])
            ->where('product_id', $data['product_id'])
            ->exists();

        if ($exists) {
            return
                GateSuggestion::where('gate_id', $data['gate_id'])
                ->where('product_id', $data['product_id'])
                ->first();
        }

        // If not exist, add it
        return GateSuggestion::create($data);
    }


    public function update($id, array $data)
    {
        $suggestion = GateSuggestion::findOrFail($id);
        $suggestion->update($data);
        return $suggestion;
    }

    public function delete($id)
    {
        return GateSuggestion::destroy($id);
    }
}
