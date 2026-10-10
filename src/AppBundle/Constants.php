<?php

namespace AppBundle;

class Constants
{
    public static function mapsApiKey()
    {
        $key = getenv('GOOGLE_MAPS_SERVER_KEY');
        if (!$key) {
            throw new \RuntimeException('GOOGLE_MAPS_SERVER_KEY must be configured for geocoding');
        }

        return $key;
    }
}
