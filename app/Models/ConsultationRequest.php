<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['New', 'Under Review', 'Contacted', 'Consultation Scheduled', 'In Progress', 'Completed', 'Cancelled'];

    protected $fillable = [
        'reference_number', 'full_name', 'company', 'job_title', 'email', 'phone',
        'product_group', 'export_status', 'support_needed', 'preferred_consultation_time',
        'privacy_consent', 'status', 'assigned_to',
    ];

    protected function casts(): array
    {
        return ['product_group' => 'array', 'privacy_consent' => 'boolean'];
    }

    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function notes() { return $this->hasMany(ConsultationNote::class)->latest(); }
    public function statusHistory() { return $this->hasMany(ConsultationStatusHistory::class)->latest('changed_at'); }
}
