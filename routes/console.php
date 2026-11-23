<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:consultations', function (): void {
    $this->line('Consultation Registration Management System API');
})->purpose('Display application information');
