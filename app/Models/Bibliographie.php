<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bibliographie extends Model
{
    protected $fillable = [
        'module_id',
        'author',
        'title',
        'publisher',
        'year',
        'pages',
        'raw_text',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
