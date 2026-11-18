<?php

namespace Modules\RouteRequest\Widgets;

use App\Contracts\Widget;
use App\Models\Airport;
use Illuminate\Support\Facades\Auth;
use Modules\RouteRequest\Support\RealWorldAirlines;

class RouteRequestWidget extends Widget
{
    protected $config = [
        'dep_icao' => null,
    ];

    public function run()
    {
        $user = Auth::user();
        if ($user) {
            $user->loadMissing('typeratings');
        }

        $pilotTypeRatings = $user && $user->typeratings
            ? $user->typeratings->where('active', true)->sortBy('type')
            : collect();

        $realworldAirlines = RealWorldAirlines::all();

        $defaultDep = strtoupper($this->config['dep_icao'] ?? ($user->curr_airport_id ?? ''));
        $defaultDepAirport = $defaultDep ? Airport::find($defaultDep) : null;

        return view('routerequest::widgets.form_widget', [
            'user'              => $user,
            'pilotTypeRatings'  => $pilotTypeRatings,
            'realworldAirlines' => $realworldAirlines,
            'defaultDep'        => $defaultDep,
            'defaultDepAirport' => $defaultDepAirport,
            'defaultEmail'      => config('routerequest.recipient_email', 'admin@flyfantasyair.net') ?: 'admin@flyfantasyair.net',
        ]);
    }
}
