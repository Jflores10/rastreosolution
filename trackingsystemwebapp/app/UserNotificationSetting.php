<?php

namespace App;

use Moloquent as Model;

class UserNotificationSetting extends Model
{
    protected $collection = 'user_notification_settings';

    protected $fillable = array(
        'user_id', 'notification_type_id', 'enabled',
    );

    protected $dates = array(
        'created_at', 'updated_at',
    );

    public function usuario()
    {
        return $this->belongsTo('App\User', 'user_id', '_id');
    }

    public function notification_type()
    {
        return $this->belongsTo('App\NotificationType', 'notification_type_id', '_id');
    }
}
