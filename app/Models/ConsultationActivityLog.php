<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['consultation_request_id', 'actor_id', 'action', 'details', 'created_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
