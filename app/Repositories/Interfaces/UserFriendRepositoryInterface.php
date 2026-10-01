<?php

namespace App\Repositories\Interfaces;

interface UserFriendRepositoryInterface
{
    public function list();
      public function following();
    public function add(int $friendId);
    public function remove(int $friendId);
}
