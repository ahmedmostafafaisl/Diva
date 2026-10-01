<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatRoom extends Model
{
    use HasFactory;
    protected $fillable = ['type', 'name', 'image'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_room_user');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }


    public function getImageAttribute($value)
    {
        if (!$value) return null;

        return asset($value); // OR just asset($value) if you store relative to /public
    }
}
