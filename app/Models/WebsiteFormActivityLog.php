<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteFormActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'website_form_submission_id', 'actor_id', 'action', 'details', 'created_at',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
