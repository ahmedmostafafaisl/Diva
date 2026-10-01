<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gate extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'desc',
        'image',
        'status',
        'popular',
        'parent'
    ];

    public function banners()
    {
        return $this->hasMany(GateBanner::class);
    }

    public function SubCategories()
    {
        return $this->hasMany(SubCategory::class)->orderBy('priority', 'asc');
    }

    public function suggestions()
    {
        return $this->hasMany(GateSuggestion::class);
    }
}
