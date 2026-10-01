<?php

namespace App\Repositories\User;

use App\Models\Skill;
use App\Models\Service;
use App\Services\DyService;
use Illuminate\Support\Facades\DB;
use App\Services\Qoyod\QoyodService;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Interfaces\ServiceRepositoryInterface;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function getAll()
    {
        return Service::all();
    }

    public function findById($id)
    {
        return Service::with('skills')->findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['service_image'])) {
            $image = $data['service_image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'Services/Image';
            $image_path = $image->storeAs($rut, $fileName, 's3');

            $data['service_image'] = $image_path;
        }
        if (isset($data['cover_image'])) {
            $image = $data['cover_image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'Services/Cover';
            $image_path = $image->storeAs($rut, $fileName, 's3');

            $data['cover_image'] = $image_path;
        }
        $service = Service::create($data);
        $service->skills()->attach($data['skills']);
        // $dQ = new QoyodService();
        // $dQ->createServiceIfNotExists($service);
        return ($service);
    }

    public function update($id, array $data)
    {
        $service = Service::findOrFail($id);
        if (isset($data['service_image'])) {
            // if ($service->service_image != null) {
            //     Storage::delete('s3/' . $service->service_image);
            // }
            $image = $data['service_image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'Services/Image';
            $image_path = $image->storeAs($rut, $fileName, 's3');

            $data['service_image'] = $image_path;
        }
        if (isset($data['cover_image'])) {
            // if ($service->cover_image != null) {
            //     Storage::delete('s3/' . $service->cover_image);
            // }
            $image = $data['cover_image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'Services/Cover';
            $image_path = $image->storeAs($rut, $fileName, 's3');

            $data['cover_image'] = $image_path;
        }

        $service->update($data);
        $service->load('skills');
        return $service;
    }

    public function delete($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();
    }

    public function attachSkills($serviceId, array $skillIds)
    {
        $service = Service::findOrFail($serviceId);

        // Get currently attached skill IDs
        $existingSkillIds = $service->skills()->pluck('skills.id')->toArray();

        // Filter out skill IDs that are already attached
        $newSkillIds = array_diff($skillIds, $existingSkillIds);

        // Attach only the new skill IDs
        if (!empty($newSkillIds)) {
            $service->skills()->attach($newSkillIds);
        }

        // Return all attached skills
        return $service->skills;
    }

    // Detach skills from a service
    public function detachSkills($serviceId, array $skillIds)
    {
        $service = Service::findOrFail($serviceId);

        // Get skills that are currently attached to the service and in the provided $skillIds
        $detachedSkills = $service->skills()->whereIn('skills.id', $skillIds)->get();

        if ($detachedSkills->isEmpty()) {
            return response()->json(['error' => 'No matching skills found attached to this service.'], 404);
        }

        // Detach the skills
        $service->skills()->detach($skillIds);

        // Return the detached skills
        return $detachedSkills;
    }


    // Get skills not attached to a service
    public function getSkillsNotInService($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        $skillIdsInService = $service->skills()->pluck('skills.id')->toArray();

        return Skill::where('status', 'active')->whereNotIn('id', $skillIdsInService)->get();
    }

    public function getSkillsNotInAnyService()
    {
        // Get IDs of skills that are associated with any service
        $skillIdsInServices = DB::table('service_skill')->pluck('skill_id')->toArray();

        // Fetch active skills that are not in the list of skill IDs associated with services
        return Skill::where('status', 'active')
            ->whereNotIn('id', $skillIdsInServices)
            ->get();
    }
    // Get all skills attached to a service
    public function getAllSkillsInService($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        return $service->skills()->get();
    }

    // Get all active and featured services
    public function getAllActiveFeatured()
    {
        return
            Service::where('featured', 1)
            ->orderBy('priority', 'asc')
            ->get();
    }

    // Get all active services
    public function getAllActiveServices()
    {
        return Service::where('status', 'active')->get();
    }

    public function syncAllServicesFromDy()
    {
        $dy = new DyService();
        $services = $dy->getServicesAndPackages();
        if (count($services['value']) > 0) {
            foreach ($services['value'] as $ser) {
                if ($ser['ProductDescription'] == 'S_Service') {
                    $service = Service::where('dy_item_number', $ser['ItemNumber'])->first();
                    if (!$service) {
                        $service = new Service();
                        $service->dy_item_number = $ser['ItemNumber'];
                        $service->dy_product_name = $ser['ProductName'];
                        $service->save();
                    }
                }
            }
        }
        return  $services = Service::all();
    }

    private function storeImages($files, string $path): array
    {
        $storedImages = [];

        foreach ($files as $file) {
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs($path, $fileName, 's3');
            $storedImages[] = $imagePath;
        }

        return $storedImages;
    }
}
