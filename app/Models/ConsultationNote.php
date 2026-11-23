<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationNote extends Model
{
    protected $fillable = ['consultation_request_id', 'author_id', 'note'];

    public function consultation() { return $this->belongsTo(ConsultationRequest::class, 'consultation_request_id'); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
