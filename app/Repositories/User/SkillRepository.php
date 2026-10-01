<?php

namespace App\Repositories\User;

use App\Models\Skill;
use App\Repositories\Interfaces\SkillRepositoryInterface;

class SkillRepository implements SkillRepositoryInterface
{
    public function getAllSkills()
    {
        return Skill::all();
    }

    public function getSkillById($id)
    {
        return Skill::with('users')->findOrFail($id);
    }

    public function createSkill(array $data)
    {
        return Skill::create($data);
    }

    public function updateSkill($id, array $data)
    {
        $skill = Skill::findOrFail($id);
        $skill->update($data);
        return $skill;
    }

    public function deleteSkill($id)
    {
        $skill = Skill::findOrFail($id);
        $skill->delete();
    }

    public function  activeSkills()
    {

        return Skill::where('status', 'active')->get();
    }
}
