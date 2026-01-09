<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UnidadActualizada
{
    use InteractsWithSockets, SerializesModels;

    public $unidad;

    public function __construct(array $unidad)
    {
        $this->unidad = $unidad;
    }

    public function broadcastOn()
    {
        return new Channel('cooperativa.' . $this->unidad['cooperativa_id']);
    }

    public function broadcastAs()
    {
        return 'unidad.updated';
    }
}
