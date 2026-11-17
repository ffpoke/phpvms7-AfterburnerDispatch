<?php

namespace Modules\RouteRequest\Models;

use App\Contracts\Model;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\Typerating;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Modules\RouteRequest\Support\FlightAwareParser;

class RouteRequest extends Model
{
    public $table = 'route_requests';

    /**
     * Preloaded Flight models injected by the controller to eliminate N+1 queries.
     */
    public ?Collection $preloadedFlights = null;

    protected $fillable = [
        'user_id',
        'typerating_id',
        'flight_id',
        'generated_flights',
        'multiple_destinations',
        'request_return',
        'aircraft_type',
        'realworld_airline',
        'realworld_flight_number',
        'dep_airport_id',
        'arr_airport_id',
        'recipient_email',
        'notes',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'multiple_destinations' => 'boolean',
        'request_return'        => 'boolean',
        'generated_flights'     => 'array',
    ];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved', 'in_progress' => 'Accepted (In Progress)',
            'completed'               => 'Completed (Route Active)',
            'rejected'                => 'Rejected / Unable',
            'cancelled'               => 'Cancelled by Pilot',
            default                   => 'Pending Dispatch Review',
        };
    }

    public function getAllGeneratedFlightIds(): array
    {
        $ids = [];
        if (is_array($this->generated_flights)) {
            $ids = array_merge($ids, $this->generated_flights);
        }
        if (!empty($this->flight_id)) {
            $ids[] = $this->flight_id;
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public function getGeneratedFlightModelsAttribute(): Collection
    {
        if ($this->preloadedFlights !== null) {
            return $this->preloadedFlights;
        }

        $ids = $this->getAllGeneratedFlightIds();
        if (empty($ids)) {
            return collect();
        }

        try {
            return Flight::with('airline')->whereIn('id', $ids)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Batch-hydrate `preloadedFlights` across a collection/paginator of RouteRequest models in 1 single SQL query!
     */
    public static function hydrateGeneratedFlights($requests): void
    {
        $allIds = [];
        foreach ($requests as $req) {
            foreach ($req->getAllGeneratedFlightIds() as $fid) {
                $allIds[$fid] = $fid;
            }
        }

        if (empty($allIds)) {
            foreach ($requests as $req) {
                $req->preloadedFlights = collect();
            }
            return;
        }

        try {
            $flightsById = Flight::with('airline')
                ->whereIn('id', array_values($allIds))
                ->get()
                ->keyBy('id');

            foreach ($requests as $req) {
                $models = collect();
                foreach ($req->getAllGeneratedFlightIds() as $fid) {
                    if ($flightsById->has($fid)) {
                        $models->push($flightsById->get($fid));
                    }
                }
                $req->preloadedFlights = $models;
            }
        } catch (\Throwable $e) {
            foreach ($requests as $req) {
                $req->preloadedFlights = collect();
            }
        }
    }

    public function getFlightAwareUrlsAttribute(): array
    {
        if (!class_exists(FlightAwareParser::class, false)) {
            require_once __DIR__ . '/../Support/FlightAwareParser.php';
        }
        return FlightAwareParser::extractUrls($this->notes);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function typerating(): BelongsTo
    {
        return $this->belongsTo(Typerating::class, 'typerating_id');
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class, 'flight_id');
    }

    public function dep_airport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'dep_airport_id', 'id');
    }

    public function arr_airport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'arr_airport_id', 'id');
    }
}
