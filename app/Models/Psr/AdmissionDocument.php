<?php

namespace App\Models\Psr;

use App\Models\Concerns\LocksWhenDischarged;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDocument extends Model
{
    use LocksWhenDischarged;

    protected $table = 'psr_admission_documents';

    protected $fillable = [
        'psr_admission_id', 'document_type', 'original_name',
        'file_path', 'mime_type', 'file_size', 'uploaded_by',
    ];

    public function admission(): BelongsTo { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function uploader(): BelongsTo  { return $this->belongsTo(User::class, 'uploaded_by'); }
}
