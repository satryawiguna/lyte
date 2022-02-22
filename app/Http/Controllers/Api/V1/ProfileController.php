<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Transformers\User\ProfileTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class ProfileController extends Controller
{
    public function actionProfile($id)
    {
        // Get profile
        $profile = Profile::whereHas('users', function($query) use($id) {
           return $query->where('id', '=', $id);
        });

        // Check if user is exists
        if (!$profile)
            return $this->responseUnprocessable(new MessageBag(['Profile is not found']));

        return fractal($profile, new ProfileTransformer)->toArray();
    }

    public function actionProfileUpdate(Request $request)
    {
        $id = $request->input('id');

        // Get profile
        $profile = Profile::whereHas('users', function($query) use($id) {
            return $query->where('id', '=', $id);
        });

        // Check if user is exists
        if (!$profile)
            return $this->responseUnprocessable(new MessageBag(['Profile is not found']));

        // Profile request validation
        $validatedProfile = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'nick_name' => 'xsx |string',
            'address' => 'string',
            'post_code' => 'integer'
        ]);

        if ($validatedProfile->fails()) {
            return $this->responseUnprocessable($validatedProfile->errors());
        }

        // Update profile
        $profile->contact->update([
            'nick_name' => $request->input('nick_name'),
            'full_name' => $request->input('full_name'),
            'post_code' => $request->input('post_code') ?: null,
            'country' => $request->input('country') ?: null,
            'state' => $request->input('state') ?: null,
            'city' => $request->input('city') ?: null,
            'address' => $request->input('address') ?: null
        ]);

        return fractal($profile, new ProfileTransformer())->toArray();
    }

    public function actionPhotoUpdate(Request $request)
    {
        // Get profile
        $profile = (new User())->with(['contact'])
            ->find($request->input('id'));

        // Check if user is exists
        if (!$profile)
            return $this->responseUnprocessable(new MessageBag(['Profile is not found']));

        // Photo request validation
        $validatedPhoto = Validator::make($request->all(), [
            'photo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validatedPhoto->fails()) {
            return $this->responseUnprocessable($validatedPhoto->errors());
        }

        if ($request->file('photo')) {
            // Check if any photo existing it will delete first
            if ($profile->contact->photo && File::exists(storage_path('app/profile/' . basename($profile->contact->photo))))
                File::delete(storage_path('app/profile/' . basename($profile->contact->photo)));

            // Upload photo
            $name = time() . '.' . $request->file('photo')->extension();
            $request->file('photo')->storeAs('profile', $name);

            // Update photo in database
            $profile->contact->update([
                'photo' => asset('photo/profile') . '/' . $name
            ]);
        } else {
            // Update photo in database
            $profile->contact->update([
                'photo' => null
            ]);
        }

        return response()->json([
            'photo' => $profile->contact->photo
        ]);
    }
}
