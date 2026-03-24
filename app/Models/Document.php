<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'documentable_id',
        'documentable_type',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        if ($this->file_path) {
            // توليد رابط أمن صالح لمدة ساعة
            return URL::signedRoute(
                'documents.download',
                ['document' => $this->id],
                now()->addMinutes(60)
            );
        }
        return null;
    }

    public function documentable()
    {
        return $this->morphTo();
    }
}
