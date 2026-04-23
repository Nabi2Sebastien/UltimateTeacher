<?php

namespace App\Http\Controllers;

use App\Models\Referentiel;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function settingsIndex()
    {
        $referentiels = Referentiel::latest()->get();
        return view('settings-modules', compact('referentiels'));
    }
}
