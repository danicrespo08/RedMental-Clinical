<?php

namespace App\Models\Psr;

use Illuminate\Database\Eloquent\Model;

class NoteTemplate extends Model
{
    protected $table = 'psr_note_templates';

    protected $fillable = [
        'client_id', 'name', 'slug', 'description',
        'sections', 'is_system', 'is_active',
    ];

    protected $casts = [
        'sections'  => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];
}
