<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Petition extends Model
{
    protected $fillable = [
        'title',
        'description',
        'destinatary',
        'category_id',
        'user_id',
        'signeds',
        'status',
    ];

    // Relación con Category (antes categoria)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Relación con User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relación para contar firmas (withCount buscará 'signatures')
    public function signatures(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'petition_user')->withTimestamps();
    }

    // Relación con archivos
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }
}
