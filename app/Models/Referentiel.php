<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referentiel extends Model
{
    protected $fillable = ['title', 'status', 'file_path'];

    public function modules()
    {
        return $this->hasMany(Module::class);
    }
}
