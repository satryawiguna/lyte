<?php

namespace App\Transformers\Calendar;

use App\Models\Calendar;
use App\Transformers\User\UserProfileTransformer;
use League\Fractal\TransformerAbstract;

class CalendarTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'user'
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Calendar $calendar)
    {
        return [
            'appointment_date' => $calendar->appointment_date,
            'description' => $calendar->description,
            'status' => $calendar->status
        ];
    }

    public function includeUserProfile(Calendar $calendar)
    {
        $user = $calendar->user;

        return $this->item($user, new UserProfileTransformer());
    }
}
