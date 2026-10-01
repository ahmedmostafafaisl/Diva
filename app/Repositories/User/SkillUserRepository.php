<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\Skill;
use App\Repositories\Interfaces\SkillUserRepositoryInterface;

class SkillUserRepository implements SkillUserRepositoryInterface
{
    public function getUserWithSkills($userId)
    {
        return User::with('skills')->findOrFail($userId);
    }

    public function assignSkillsToUser($userId, array $skills)
    {
        $user = User::findOrFail($userId);
        $user->skills()->syncWithoutDetaching($skills);
        return $user->load('skills');
    }

    public function updateUserSkills($userId, array $skills)
    {
        $user = User::findOrFail($userId);
        $user->skills()->sync($skills);
        return $user->load('skills');
    }

    public function detachSkillFromUser($skillId, $userId)
    {
        // Find the skill and user
        $skill = Skill::findOrFail($skillId);
        $user = User::findOrFail($userId);

        // Detach the skill from the user
        $skill->users()->detach($user);

        return  $user->load('skills');
    }

    public function removeAllSkillsFromUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->skills()->detach();
    }


    public function getUsersBelongingToSkill($skillId)
    {
        $skill = Skill::findOrFail($skillId);
        return   $users = $skill->fetchUsersBelongingToSkill();
    }

    public function getUsersNotBelongingToSkill($skillId)
    {
        $skill = Skill::findOrFail($skillId);
        return    $users = $skill->fetchUsersNotBelongingToSkill();
    }

    public function attachSkillToUsers(array $userIds, int $skillId)
    {
        $skill = Skill::findOrFail($skillId);

        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $user->skills()->attach($skill);
        }
        return $users;
    }
}
