<?php
namespace CPSIT\GeoLocationService\Service;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Erik Rauchstein <erik.rauchstein@cps-it.de>
 *  (c) 2019 Elias Häußler <e.haeussler@familie-redlich.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CPSIT\GeoLocationService\Domain\Model\GeoCodableInterface;

/**
 * Geo coding service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GeoCoder
{
    /**
     * @var array Valid URL parameters for Google Geocoding API
     * @see https://developers.google.com/maps/documentation/geocoding/intro#GeocodingRequests
     */
    public const VALID_SERVICE_URL_PARAMETERS = [
        'address',
        'key',
        'bounds',
        'language',
        'region',
        'components',
    ];

    /**
     * Service Url
     *
     * @var string Base Url for geo coding service.
     */
    protected $serviceUrl = 'https://maps.googleapis.com/maps/api/geocode/json?&address=';

    /**
     * Api key for geo code api
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Configuration set by extension configuration.
     *
     * @var array
     */
    protected $extConf;

    /**
     * Returns the base url of the geo coding service
     *
     * @return string
     */
    public function getServiceUrl()
    {
        return $this->serviceUrl;
    }

    /**
     * Set the base url of the geo coding service
     *
     * @param $serviceUrl
     */
    public function setServiceUrl($serviceUrl)
    {
        $this->serviceUrl = $serviceUrl;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function __construct()
    {
        try {
            $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('geo_location_service');
        } catch (Exception $e) {
            $this->extConf = [];
        }
        $this->setApiKey($this->extConf['googleApiKey']);
    }

    /**
     * Get geo location encoded from Google Maps geocode service.
     *
     * @param string $address An address to encode.
     * @param array $additionalParameters
     * @return array|false Array containing geo location information
     */
    public function getLocation($address, array $additionalParameters = [])
    {
        // Build request URI
        $apiParameters = array_merge($additionalParameters, [
            'address' => urlencode($address),
            'key' => $this->getApiKey(),
        ]);
        $url = $this->buildServiceUrlWithParameters($apiParameters);

        $response_json = $this->getUrl($url);
        $response = json_decode($response_json, true);
        if ($response['status'] == 'OK') {
            return $response['results'][0]['geometry']['location'];
        } else {
            return false;
        }
    }

    /**
     * @param array $parameters
     * @return string
     */
    public function buildServiceUrlWithParameters(array $parameters = []): string
    {
        $uri = new Uri($this->serviceUrl);

        if (empty($parameters)) {
            return (string) $uri;
        }

        // Remove invalid URI parameters
        $parameters = array_filter($parameters, function ($parameterName) {
            return in_array($parameterName, self::VALID_SERVICE_URL_PARAMETERS);
        }, ARRAY_FILTER_USE_KEY);

        // Respect predefined parameters in service URI
        if (!empty($uri->getQuery())) {
            $parameters += GeneralUtility::explodeUrl2Array($uri->getQuery());
        }

        // Build URI with parameters
        $queryParams = http_build_query($parameters);
        $uri = $uri->withQuery($queryParams);

        return (string) $uri;
    }

    /**
     * Get url
     * Wrapper for GeneralUtility::getUrl to make it testable.
     *
     * @param string $url File/Url to fetch
     * @return mixed Response
     * @codeCoverageIgnore
     */
    public function getUrl($url)
    {
        return GeneralUtility::getUrl($url);
    }

    /**
     * calculate destination lat/lng given a starting point, bearing, and distance
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param $bearing
     * @param integer $distance Distance
     * @param string $units Units: default km. Any other value will result in computing with mile based constants.
     * @return array An array with lat and lng values
     * @codeCoverageIgnore
     */
    public function destination($lat, $lng, $bearing, $distance, $units = 'km')
    {
        $radius = strcasecmp($units, 'km') ? 3963.19 : 6378.137;
        $rLat = deg2rad($lat);
        $rLon = deg2rad($lng);
        $rBearing = deg2rad($bearing);
        $rAngDist = $distance / $radius;

        $rLatB = asin(sin($rLat) * cos($rAngDist) +
            cos($rLat) * sin($rAngDist) * cos($rBearing));

        $rLonB = $rLon + atan2(sin($rBearing) * sin($rAngDist) * cos($rLat),
                cos($rAngDist) - sin($rLat) * sin($rLatB));

        return array('lat' => rad2deg($rLatB), 'lng' => rad2deg($rLonB));
    }

    /**
     * calculate bounding box
     *
     * @param float $lat Latitude of location
     * @param float $lng Longitude of location
     * @param float $distance Distance around location
     * @param string $units Unit: default km. Any other value will result in computing with mile based constants.
     * @return array An array describing a bounding box
     * @codeCoverageIgnore
     */
    public function getBoundsByRadius($lat, $lng, $distance, $units = 'km')
    {
        return array('N' => $this->destination($lat, $lng, 0, $distance, $units),
            'E' => $this->destination($lat, $lng, 90, $distance, $units),
            'S' => $this->destination($lat, $lng, 180, $distance, $units),
            'W' => $this->destination($lat, $lng, 270, $distance, $units));
    }

    /**
     * calculate distance between two lat/lon coordinates
     *
     * @param float $latA Latitude of location A
     * @param float $lonA Longitude of location A
     * @param float $latB Latitude of location B
     * @param float $lonB Longitude of location B
     * @param string $units Units: default km. Any other value will result in computing with mile based constants.
     * @return float
     * @codeCoverageIgnore
     */
    public function distance($latA, $lonA, $latB, $lonB, $units = 'km')
    {
        $radius = strcasecmp($units, 'km') ? 3963.19 : 6378.137;
        $rLatA = deg2rad($latA);
        $rLatB = deg2rad($latB);
        $rHalfDeltaLat = deg2rad(($latB - $latA) / 2);
        $rHalfDeltaLon = deg2rad(($lonB - $lonA) / 2);

        return 2 * $radius * asin(sqrt(pow(sin($rHalfDeltaLat), 2) +
            cos($rLatA) * cos($rLatB) * pow(sin($rHalfDeltaLon), 2)));
    }

    /**
     * Update Geo Location
     * Sets latitude and longitude of an object. The object
     * must implement the GeoCodableInterface.
     * Will first read city and zip attributes then tries to
     * get geo location values and if succeeds update the latitude and
     * longitude values of the object.
     *
     * @var GeoCodableInterface $object
     */
    public function updateGeoLocation(GeoCodableInterface &$object)
    {
        $city = $object->getPlace();
        if (!empty($city)) {
            $address = '';
            $zip = $object->getZip();
            $street = $object->getAddress();
            $address .= (!empty($zip)) ? $zip . ' ' : null;
            $address .= (!empty($street)) ? $street . ' ' : null;
            $address .= $city;
            $geoLocation = $this->getLocation($address);
            if ($geoLocation) {
                $object->setLatitude($geoLocation['lat']);
                $object->setLongitude($geoLocation['lng']);
            }
        }
    }
}
