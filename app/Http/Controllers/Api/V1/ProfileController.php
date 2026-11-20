<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->name = $request->string('name')->toString();
        $user->email = $request->string('email')->lower()->toString();

        if (filled($request->input('password'))) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();

        return new UserResource($user);
    }
}
