<?php

namespace App\Repositories\Zone;

use App\Models\Zone;
use App\Repositories\Interfaces\ZoneRepositoryInterface;

class ZoneRepository implements ZoneRepositoryInterface
{
    public function all()
    {
        return Zone::all();
    }

    public function find($id)
    {
        return Zone::findOrFail($id);
    }

    public function create(array $data)
    {
        return Zone::create($data);
    }

    public function update($id, array $data)
    {
        $zone = $this->find($id);
        $zone->update($data);
        return $zone;
    }

    public function delete($id)
    {
        $zone = $this->find($id);
        return $zone->delete();
    }
}
