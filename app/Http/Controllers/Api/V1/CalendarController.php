<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Transformers\Calendar\CalendarTransformer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class CalendarController extends Controller
{
    public function actionCalendars()
    {
        if (!Auth::user()->can('view_by_admin', [Calendar::class])) {
            return $this->responseUnauthorized();
        }

        $calendars = (new Calendar())->orderBy('appointment_date', 'asc')
            ->get();

        if (!$calendars)
            return $this->responseUnprocessable(new MessageBag(['Calendar is not found']));

        return fractal($calendars, new CalendarTransformer())
            ->toArray();
    }

    public function actionCalendarsListSearch(Request $request)
    {
        if (!Auth::user()->can('view', [Calendar::class])) {
            return $this->responseUnauthorized();
        }

        $search = $request->input('search');
        $user_id = $request->input('$user_id');
        $start_appointment_date = $request->input('start_appointment_date');
        $end_appointment_date = $request->input('end_appointment_date');

        $calendars = new Calendar();

        if ($search) {
            $calendars = $calendars->where([
                ['description','LIKE','%'. $search .'%']
            ]);
        }

        if ($user_id) {
            $calendars = $calendars->where([
                ['user_id','=',$user_id]
            ]);
        }

        if ($start_appointment_date && $end_appointment_date) {
            $calendars = $calendars->whereBetween('appointment_date', [$start_appointment_date, $end_appointment_date]);
        }

        $calendars = $calendars->orderBy('id', 'desc')
            ->get();

        return fractal($calendars, new CalendarTransformer())
            ->toArray();
    }

    public function actionCalendarPageSearch(Request $request)
    {
        if (!Auth::user()->can('view', [Calendar::class])) {
            return $this->responseUnauthorized();
        }

        $search = $request->input('search');
        $user_id = $request->input('$user_id');
        $start_appointment_date = $request->input('start_appointment_date');
        $end_appointment_date = $request->input('end_appointment_date');
        $perPage = $request->input('per_page') ?: 5;

        $calendars = new Calendar();

        if ($search) {
            $calendars = $calendars->where([
                ['description','LIKE','%'. $search .'%']
            ]);
        }

        if ($user_id) {
            $calendars = $calendars->where([
                ['user_id','=',$user_id]
            ]);
        }

        if ($start_appointment_date && $end_appointment_date) {
            $calendars = $calendars->whereBetween('appointment_date', [$start_appointment_date, $end_appointment_date]);
        }

        $calendars = $calendars->orderBy('id', 'desc')
            ->paginate($perPage);

        return fractal($calendars, new CalendarTransformer())
            ->toArray();
    }

    public function actionCalendar($id)
    {
        if (!Auth::user()->can('view', [Calendar::class])) {
            return $this->responseUnauthorized();
        }

        $calendar = Calendar::find($id);

        if (!$calendar)
            return $this->responseUnprocessable(new MessageBag(['Calendar is not found']));

        return fractal($calendar, new CalendarTransformer())
            ->toArray();
    }

    public function actionCalendarStore(Request $request)
    {
        if (!Auth::user()->can('create', [Calendar::class])) {
            return $this->responseUnauthorized();
        }

        // Appointment request validation
        $validatedUserStore = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'appointment_date' => 'required|date',
            'status' => 'required'
        ]);

        if ($validatedUserStore->fails()) {
            return $this->responseUnprocessable($validatedUserStore->errors());
        }

        try {
            // Check the appointment date
            $tolerance_start_date = Carbon::parse($request->input('appointment_date'))->subMinutes(30);
            $tolerance_end_date = Carbon::parse($request->input('appointment_date'))->addMinutes(30);

            $calendar = Calendar::whereBetween('appointment_date', [$tolerance_start_date, $tolerance_end_date])
                ->where([
                    ['status', '<>', 'cancel']
                ])
                ->get();

            if ($calendar->count() > 0) {
                return $this->responseUnprocessable(new MessageBag(['Appointment date is not available']));
            }

            // Store into calendar
            $calendar = new Calendar([
                'user_id' => $request->input('user_id'),
                'appointment_date' => $request->input('appointment_date'),
                'description' => $request->input('description'),
                'status' => 'pending'
            ]);
            $calendar->save();

            return fractal($calendar, new CalendarTransformer)
                ->toArray();
        } catch (Exception $e) {
            return $this->responseServerError($e->getMessage());
        }
    }

    public function actionCalendarUpdate($id, Request $request)
    {
        if (!Auth::user()->can('update', [Calendar::class])) {
            return $this->responseUnauthorized();
        }

        $validatedUserUpdate = Validator::make($request->all(), [
            'appointment_date' => 'required|date',
            'status' => 'required'
        ]);

        if ($validatedUserUpdate->fails()) {
            return $this->responseUnprocessable($validatedUserUpdate->errors());
        }

        try {
            // Check the appointment date
            $tolerance_start_date = Carbon::parse($request->input('appointment_date'))->subMinutes(30);
            $tolerance_end_date = Carbon::parse($request->input('appointment_date'))->addMinutes(30);

            $calendar = Calendar::whereBetween('appointment_date', [$tolerance_start_date, $tolerance_end_date])
                ->where([
                    ['status', '<>', 'cancel']
                ])
                ->get();

            if ($calendar->count() > 0) {
                return $this->responseUnprocessable(new MessageBag(['Appointment date is not available']));
            }

            $calendar = Calendar::find($id);

            if (!$calendar)
                return $this->responseUnprocessable(new MessageBag(['User is not found']));

            $calendar->update([
                "appointment_date" => $request->input('appointment_date') ?: $calendar->appointment_date,
                "description" => $request->input('description') ?: $calendar->description,
                "status" => $request->input('status') ?: $calendar->status
            ]);

            return fractal($calendar, new CalendarTransformer())
                ->toArray();

        } catch (Exception $e) {
            return $this->responseServerError($e->getMessage());
        }
    }

    public function actionCalendarDelete($id)
    {
        if (!Auth::user()->can('delete', [Calendar::class])) {
            return $this->responseUnauthorized();
        }

        $calendar = Calendar::find($id);

        if (!$calendar)
            return $this->responseUnprocessable(new MessageBag(['Calendar is not found']));

        $calendar->delete();

        return $this->responseSuccess('Calendar deleted');
    }
}
