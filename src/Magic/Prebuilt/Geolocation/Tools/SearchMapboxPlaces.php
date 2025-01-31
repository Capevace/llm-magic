<?php

namespace Mateffy\Magic\Prebuilt\Geolocation\Tools;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mateffy\Magic;
use Mateffy\Magic\Functions\ClosureMagicFunction;

class SearchMapboxPlaces extends ClosureMagicFunction
{
    public static function callback(): Closure
    {
        return function (string $query, ?float $proximity_latitude, ?float $proximity_longitude) {
            $session = Str::uuid()->toString();

            $response = Http::get(
                "https://api.mapbox.com/search/searchbox/v1/suggest",
                array_filter([
                    'q' => $query,
                    'language' => 'en',
                    'proximity' => ($proximity_latitude && $proximity_longitude)
                        ? "{$proximity_longitude},{$proximity_latitude}"
                        : null,
                    'session_token' => $session,
                    'access_token' => config('services.mapbox.access_token')
                ])
            );

            $text = $response->getBody()->getContents();
            $json = json_decode($text, associative: true);

            if ($json && $suggestions = $json['suggestions'] ?? null) {
                $markers = collect($suggestions)
                    ->map(function (array $suggestion) use ($session) {
                        $mapbox_id = $suggestion['mapbox_id'];

                        $response = Http::get(
                            "https://api.mapbox.com/search/searchbox/v1/retrieve/{$mapbox_id}",
                            [
                                'language' => 'en',
                                'session_token' => $session,
                                'access_token' => config('services.mapbox.access_token')
                            ]
                        );

                        if (!$response->ok()) {
                            report(new \Exception('Invalid response: '.$response->json()));
                            return null;
                        }

                        $feature = collect($response->json('features'))
                            ->first();

                        [$long, $lat] = Arr::get($feature, 'geometry.coordinates', []);

                        if (! $long || ! $lat) {
                            return null;
                        }

                        return [
                            'coordinates' => [$lat, $long],
                            'label' => Arr::get($feature, 'properties.name'),
                            'color' => '#ff0000',
                        ];
                    })
                    ->filter();

                return Magic::end([
                    'center' => [$proximity_latitude, $proximity_longitude],
                    'zoom' => 16,
                    'markers' => $markers->all(),
                    'js' => null,
                ]);
            }

            return [
                'response' => $json ?? $text,
                'params' => [
                    'query' => $query,
                    'proximity_latitude' => $proximity_latitude,
                    'proximity_longitude' => $proximity_longitude,
                ]
            ];
        };
    }

}