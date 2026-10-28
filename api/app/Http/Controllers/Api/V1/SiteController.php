<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\SiteResource;
use Illuminate\Http\Request;

/** Sajten med kommuner, primärkommun och inställningar. */
class SiteController extends ApiController
{
    public function __invoke(Request $request): SiteResource
    {
        return SiteResource::make($this->site($request));
    }
}
