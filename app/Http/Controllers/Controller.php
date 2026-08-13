<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;

abstract class Controller
{
    public ?User $user;

    public function __construct(){
         $this->user = Auth::guard("web")->user();
    }
}
