<?php

namespace App\Policies;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CalendarPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function view_by_admin(User $user)
    {
        return $user->role_id == 1;
    }

    public function view_by_customer(User $user, $userId)
    {
        return $user->role_id == 1 || $user->id == $userId;
    }

    public function detail(User $user, $calendarId)
    {
        $calendar = Calendar::find($calendarId);

        return $user->role_id == 1 || $user->id == $calendar->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user)
    {
        return true;
    }

    public function delete(User $user)
    {
        return $user->role_id == 1;
    }
}
