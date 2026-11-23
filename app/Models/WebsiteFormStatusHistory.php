<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteFormStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'website_form_submission_id', 'changed_by_id', 'from_status',
        'to_status', 'note', 'changed_at',
    ];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
