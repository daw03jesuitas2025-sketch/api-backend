<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    // 1:N (muchas peticiones pueden compartir la misma categoría)
    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    // 1:N Un usuario puede crear muchas peticiones
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    // N:M Muchos usuarios pueden firmar muchas peticiones
    public function signedUsers()
    {
        return $this->belongsToMany('App\Models\User', 'petition_user')->withTimestamps();
    }

    // ✅ 1:N Una petición puede tener varios archivos (aunque uses 1)
    public function files()
    {
        return $this->hasMany('App\Models\File');
    }
}
