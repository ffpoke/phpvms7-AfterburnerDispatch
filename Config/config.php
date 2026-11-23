<?php

declare(strict_types=1);

use Modules\RouteRequest\Support\RealWorldAirlines;

if (!class_exists(RealWorldAirlines::class, false)) {
    require_once __DIR__ . '/../Support/RealWorldAirlines.php';
}

return [
    'name'                    => 'RouteRequest',
    'version'                 => '3.1.0-beta.4',
    'edition'                 => 'Afterburner Cloud DRM Edition',
    'attribution'             => 'Made by Alexander Leard of Fantasy Air VA & Regal Artistic Avenue',
    'github_repo'             => env('ROUTEREQUEST_GITHUB_REPO', 'ffpoke/phpvms7-RouteRequest'),
    'license_key'             => env('ROUTEREQUEST_LICENSE_KEY', ''),
    'master_api_url'          => env('ROUTEREQUEST_MASTER_API', 'https://crew.flyfantasyair.net/api/routerequest-drm'),
    'recipient_email'         => env('ROUTEREQUEST_EMAIL', 'admin@flyfantasyair.net'),
    'allow_custom_recipient'  => env('ROUTEREQUEST_ALLOW_CUSTOM_EMAIL', false),
    'cc_pilot'                => env('ROUTEREQUEST_CC_PILOT', true),
    'max_multi_return_pairs'  => 4,
    'max_routes_per_request'  => 15,
    'realworld_airlines'      => RealWorldAirlines::all(),
];
