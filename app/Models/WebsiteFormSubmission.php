<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteFormSubmission extends Model
{
    use SoftDeletes;

    public const FORM_TYPES = ['contact', 'sourcing', 'meeting', 'event'];
    public const STATUSES = ['New', 'Under Review', 'Contacted', 'In Progress', 'Completed', 'Cancelled'];

    protected $fillable = [
        'reference_number', 'form_type', 'name', 'company', 'email', 'phone',
        'payload', 'status', 'assigned_to',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes()
    {
        return $this->hasMany(WebsiteFormNote::class)->latest();
    }

    public function statusHistory()
    {
        return $this->hasMany(WebsiteFormStatusHistory::class)->latest('changed_at');
    }

    public function activities()
    {
        return $this->hasMany(WebsiteFormActivityLog::class)->latest('created_at');
    }
}
