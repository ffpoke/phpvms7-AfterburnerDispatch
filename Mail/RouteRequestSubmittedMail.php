<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\RouteRequest\Models\RouteRequest;

class RouteRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public RouteRequest $routeRequest;
    public bool $isStatusUpdate;

    public function __construct(RouteRequest $routeRequest, bool $isStatusUpdate = false)
    {
        $this->routeRequest = $routeRequest;
        $this->isStatusUpdate = $isStatusUpdate;
    }

    public function build()
    {
        $dep = $this->routeRequest->dep_airport_id;
        $arr = $this->routeRequest->arr_airport_id ?: ($this->routeRequest->multiple_destinations ? 'MULTIPLE DESTINATIONS' : 'ANY/OPEN');
        $tripSuffix = $this->routeRequest->request_return ? ' [ROUND-TRIP]' : ' [ONE-WAY]';

        $subjectPrefix = $this->isStatusUpdate
            ? '[Route Request ' . strtoupper($this->routeRequest->status_label) . ']'
            : '[New Pilot Route Request]';

        $subject = sprintf(
            '%s %s -> %s%s (%s / Type Rating: %s)',
            $subjectPrefix,
            $dep,
            $arr,
            $tripSuffix,
            $this->routeRequest->realworld_airline,
            $this->routeRequest->aircraft_type
        );

        return $this->subject($subject)
                    ->view('routerequest::emails.request_submitted')
                    ->with([
                        'routeRequest'   => $this->routeRequest,
                        'isStatusUpdate' => $this->isStatusUpdate,
                    ]);
    }
}
