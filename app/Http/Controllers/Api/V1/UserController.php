<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\Loan;
use App\Models\Profile;
use App\Models\User;
use App\Transformers\Auth\RegisterTransformer;
use App\Transformers\Calendar\CalendarTransformer;
use App\Transformers\Loan\LoanTransformer;
use App\Transformers\User\UserTransformer;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function actionUsers()
    {
        if (!Auth::user()->can('view', [User::class])) {
            return $this->responseUnauthorized();
        }

        $users = (new User())->orderBy('id', 'desc')
            ->get();

        return fractal($users, new UserTransformer())
            ->includeProfile()
            ->includeRole()
            ->toArray();
    }

    public function actionUsersListSearch(Request $request)
    {
        if (!Auth::user()->can('view', [User::class])) {
            return $this->responseUnauthorized();
        }

        $search = $request->input('search');
        $role_id = $request->input('role_id');
        $gender = $request->input('gender');

        $users = new User();

        if ($search) {
            $users = $users->where([
                ['email','LIKE','%'. $search .'%']
            ])->orWhereHas('profile', function($query) use($search) {
                $query->where('identity_number', 'LIKE', '%'. $search . '%')
                    ->orWhere('full_name', 'LIKE', '%'. $search . '%')
                    ->orWhere('nick_name', 'LIKE', '%'. $search . '%')
                    ->orWhere('nationality', 'LIKE', '%'. $search . '%')
                    ->orWhere('address', 'LIKE', '%'. $search . '%')
                    ->orWhere('post_code', 'LIKE', '%'. $search . '%')
                    ->orWhere('phone', 'LIKE', '%'. $search . '%');
            });
        }

        if ($role_id) {
            $users = $users->where([
                ['role_id','=',$role_id]
            ]);
        }

        if ($gender) {
            $users = $users->whereHas('profile', function($query) use($gender) {
                return $query->where('gender', '=', $gender);
            });
        }

        $users = $users->orderBy('id', 'desc')
            ->get();

        return fractal($users, new UserTransformer())
            ->includeProfile()
            ->includeRole()
            ->toArray();
    }

    public function actionUsersPageSearch(Request $request)
    {
        if (!Auth::user()->can('view', [User::class])) {
            return $this->responseUnauthorized();
        }

        $search = $request->input('search');
        $role_id = $request->input('role_id');
        $gender = $request->input('gender');
        $perPage = $request->input('per_page') ?: 5;

        $users = new User();

        if ($search) {
            $users = $users->where([
                ['email','LIKE','%'. $search .'%']
            ])->orWhereHas('profile', function($query) use($search) {
                $query->where('identity_number', 'LIKE', '%'. $search . '%')
                    ->orWhere('full_name', 'LIKE', '%'. $search . '%')
                    ->orWhere('nick_name', 'LIKE', '%'. $search . '%')
                    ->orWhere('nationality', 'LIKE', '%'. $search . '%')
                    ->orWhere('address', 'LIKE', '%'. $search . '%')
                    ->orWhere('post_code', 'LIKE', '%'. $search . '%')
                    ->orWhere('phone', 'LIKE', '%'. $search . '%');
            });
        }

        if ($role_id) {
            $users = $users->where([
                ['role_id','=',$role_id]
            ]);
        }

        if ($gender) {
            $users = $users->whereHas('profile', function($query) use($gender) {
                return $query->where('gender', '=', $gender);
            });
        }

        $users = $users->orderBy('id', 'desc')
            ->paginate($perPage);

        return fractal($users, new UserTransformer())
            ->includeProfile()
            ->includeRole()
            ->toArray();
    }

    public function actionUser($id)
    {
        if (!Auth::user()->can('view', [User::class])) {
            return $this->responseUnauthorized();
        }

        $user = User::find($id);

        if (!$user)
            return $this->responseUnprocessable(new MessageBag(['User is not found']));

        return fractal($user, new UserTransformer())
            ->includeProfile()
            ->includeRole()
            ->toArray();
    }

    public function actionUserStore(Request $request)
    {
        if (!Auth::user()->can('create', [User::class])) {
            return $this->responseUnauthorized();
        }

        // Register request validation
        $validatedUserStore = Validator::make($request->all(), [
            'role_id' => 'required|integer',
            'username' => 'required|unique:users|max:255',
            'email' => 'required|unique:users|max:255|email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'identity_number' => 'required|integer',
            'full_name' => 'required'
        ]);

        if ($validatedUserStore->fails()) {
            return $this->responseUnprocessable($validatedUserStore->errors());
        }

        try {
            // Store into user
            $user = new User([
                'role_id' => $request->input('role_id'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password'))
            ]);
            $user->save();

            // Store into contact
            $user->contact()->save(new Profile([
                'identity_number' => $request->input('identity_number'),
                'full_name' => $request->input('full_name')
            ]));

            // Send email verification
            event(new Registered($user));

            return fractal($user, new RegisterTransformer)
                ->includeProfile()
                ->toArray();
        } catch (Exception $e) {
            return $this->responseServerError($e->getMessage());
        }
    }

    public function actionUserUpdate(Request $request)
    {
        if (!Auth::user()->can('update', [User::class])) {
            return $this->responseUnauthorized();
        }

        $validatedUserUpdate = Validator::make($request->all(), [
            'role_id' => 'required|integer',
            'status' => 'required'
        ]);

        if ($validatedUserUpdate->fails()) {
            return $this->responseUnprocessable($validatedUserUpdate->errors());
        }

        $user = User::find($request->input('id'));

        if (!$user)
            return $this->responseUnprocessable(new MessageBag(['User is not found']));

        try {
            $user->update([
                "role_id" => $request->input('role_id') ?: $user->role_id,
                "status" => $request->input('status') ?: $user->status
            ]);

            return fractal($user, new UserTransformer())
                ->includeProfile()
                ->includeRole()
                ->toArray();

        } catch (Exception $e) {
            return $this->responseServerError($e->getMessage());
        }
    }

    public function actionUserDelete($id)
    {
        if (!Auth::user()->can('delete', [User::class])) {
            return $this->responseUnauthorized();
        }

        $user = User::find($id);

        if (!$user)
            return $this->responseUnprocessable(new MessageBag(['User is not found']));

        $user->delete();

        return $this->responseSuccess('User deleted');
    }

    public function actionUserCalendars($id)
    {
        if (!Auth::user()->can('view_by_customer', [Calendar::class, $id])) {
            return $this->responseUnauthorized();
        }

        $calendars = Calendar::where('user_id', '=', $id)->get();

        return fractal($calendars, new CalendarTransformer())
            ->toArray();
    }
}
