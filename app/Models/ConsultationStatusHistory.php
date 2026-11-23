<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationStatusHistory extends Model
{
    public $timestamps = false;
    protected $fillable = ['consultation_request_id', 'changed_by_id', 'from_status', 'to_status', 'note', 'changed_at'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function actor() { return $this->belongsTo(User::class, 'changed_by_id'); }
}
