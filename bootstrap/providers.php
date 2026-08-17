<?php

use App\Providers\AppServiceProvider;
use App\Providers\FederationServiceProvider;
use App\Providers\MissionServiceProvider;
use App\Providers\TransmissionServiceProvider;

return [
    AppServiceProvider::class,
    FederationServiceProvider::class,
    MissionServiceProvider::class,
    TransmissionServiceProvider::class,
];
