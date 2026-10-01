<?php

namespace App\Repositories\Zone;

use App\Models\Shipping;
use App\Repositories\Interfaces\ShippingInterface;


class ShippingRepository implements ShippingInterface
{
    public function all()
    {
        return Shipping::where('enabled', 1)->with('zone')->get();
    }

    public function find($id)
    {
        return Shipping::with('zone')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Shipping::create($data);
    }

    public function update($id, array $data)
    {
        $shipping = $this->find($id);
        $shipping->update($data);
        return $shipping;
    }

    public function delete($id)
    {
        $shipping = $this->find($id);
        return $shipping->delete();
    }
}
