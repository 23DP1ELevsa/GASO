<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function currentActor(Request $request): Client|Employee|null
    {
        return $request->attributes->get('actor');
    }

    protected function currentActorType(Request $request): ?string
    {
        return $request->attributes->get('actorType');
    }
}
