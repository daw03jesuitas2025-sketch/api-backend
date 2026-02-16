<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['name', 'file_path', 'petition_id'];

    public function petition()
    {
        return $this->belongsTo('App\Models\Petition');
    }
}
