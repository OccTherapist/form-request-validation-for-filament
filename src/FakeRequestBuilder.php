<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Livewire\Component;

class FakeRequestBuilder
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function build(array $input, Component $livewire): Request
    {
        $request = Request::create('/', 'POST', $input);
        $route = new Route(['POST'], '/', fn () => null);

        $route->bind($request);

        if (method_exists($livewire, 'getRecord')) {
            $record = $livewire->getRecord();

            if ($record !== null) {
                $route->setParameter($record->getRouteKeyName(), $record->getRouteKey());
                $route->setParameter('record', $record);
            }
        }

        $request->setRouteResolver(fn (): Route => $route);
        $request->setUserResolver(fn () => auth()->user());

        return $request;
    }
}
