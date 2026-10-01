<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'type',
        'full_name',
        'phone',
        'second_phone',
        'location_note',
        'whatsapp_notifications',
        'city_id',
        'district_id',
        'user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'type', 'phone', 'second_phone', 'whatsapp_notifications'])
            ->logOnlyDirty()

            ->useLogName('clients');
        // Chain fluent methods for configuration options
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
