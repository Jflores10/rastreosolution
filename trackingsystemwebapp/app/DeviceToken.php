<?php

namespace App;

use Moloquent as Model;

class DeviceToken extends Model
{
    protected $collection = 'devices_token';

    protected $fillable = [
        'user_id', 'token', 'platform', 'device_id', 'active',
    ];

    protected $dates = [
        'created_at', 'updated_at',
    ];

    public function usuario()
    {
        return $this->belongsTo('App\User', 'user_id', '_id');
    }
}
