<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class VoyagerUsersController extends Controller
{
    public function signedPetitions()
    {
        $id = Auth::id();
        $user = User::findOrFail($id);
        $petitions = $user->petitions;

    }
}
