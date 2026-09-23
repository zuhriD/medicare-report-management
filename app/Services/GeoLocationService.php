<?php

namespace App\Services;

use App\Models\Office;

class GeoLocationService
{
    /**
     * Earth radius in meters.
     */
    public const EARTH_RADIUS_METERS = 6371000;

    /**
     * Calculate great-circle distance between two GPS coordinates in meters using Haversine formula.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return round($angle * self::EARTH_RADIUS_METERS, 2);
    }

    /**
     * Check whether a coordinate is within a specific radius in meters from a target center.
     */
    public function isWithinRadius(
        float $userLat,
        float $userLon,
        float $targetLat,
        float $targetLon,
        int $radiusMeters
    ): bool {
        $distance = $this->calculateDistance($userLat, $userLon, $targetLat, $targetLon);
        return $distance <= $radiusMeters;
    }

    /**
     * Validate user GPS coordinates against the Office geofence radius.
     *
     * @return array{is_valid: bool, distance_meters: float, allowed_radius_meters: int, message: string}
     */
    public function validateOfficeGeofence(Office $office, ?float $userLat, ?float $userLon): array
    {
        if ($userLat === null || $userLon === null) {
            return [
                'is_valid' => false,
                'distance_meters' => 0.0,
                'allowed_radius_meters' => $office->attendance_radius_meter,
                'message' => 'GPS coordinates were not provided or could not be detected.',
            ];
        }

        if ($office->latitude === null || $office->longitude === null) {
            return [
                'is_valid' => false,
                'distance_meters' => 0.0,
                'allowed_radius_meters' => $office->attendance_radius_meter,
                'message' => 'Office GPS location has not been configured.',
            ];
        }

        $distance = $this->calculateDistance(
            $userLat,
            $userLon,
            $office->latitude,
            $office->longitude
        );

        $isValid = $distance <= $office->attendance_radius_meter;

        return [
            'is_valid' => $isValid,
            'distance_meters' => $distance,
            'allowed_radius_meters' => $office->attendance_radius_meter,
            'message' => $isValid
                ? "Location is within office radius ({$distance}m / {$office->attendance_radius_meter}m)."
                : "Location is outside office radius. You are {$distance}m away (max allowed: {$office->attendance_radius_meter}m).",
        ];
    }
}
