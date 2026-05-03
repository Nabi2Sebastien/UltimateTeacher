<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['numero', 'referentiel_id', 'code', 'parent_module', 'title', 'duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];

    public function referentiel()
    {
        return $this->belongsTo(Referentiel::class);
    }

    public function bibliographies()
    {
        return $this->hasMany(Bibliographie::class);
    }
}
