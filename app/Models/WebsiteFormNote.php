<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteFormNote extends Model
{
    protected $fillable = ['website_form_submission_id', 'author_id', 'note'];

    public function submission()
    {
        return $this->belongsTo(WebsiteFormSubmission::class, 'website_form_submission_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
