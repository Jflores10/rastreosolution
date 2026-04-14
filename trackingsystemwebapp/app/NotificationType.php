<?php

namespace App;

use Moloquent as Model;

class NotificationType extends Model
{
    protected $collection = 'notification_types';

    protected $fillable = array(
        'code', 'nombre', 'descripcion', 'activo',
    );

    protected $dates = array(
        'created_at', 'updated_at',
    );

    public function user_notification_settings()
    {
        return $this->hasMany('App\UserNotificationSetting', 'notification_type_id', '_id');
    }
}
