[![Maintainability](https://api.codeclimate.com/v1/badges/b1ccae45336f26162bb1/maintainability)](https://codeclimate.com/github/CPS-IT/geo_location_service/maintainability)
[![CGL](https://github.com/CPS-IT/geo_location_service/actions/workflows/cgl.yaml/badge.svg)](https://github.com/CPS-IT/geo_location_service/actions/workflows/cgl.yaml)
[![Latest Stable Version](http://poser.pugx.org/cpsit/geo-location-service/v)](https://packagist.org/packages/cpsit/geo-location-service)
[![Total Downloads](http://poser.pugx.org/cpsit/geo-location-service/downloads)](https://packagist.org/packages/cpsit/geo-location-service)
[![License](http://poser.pugx.org/cpsit/geo-location-service/license)](LICENSE.md)

# Geo Location Service

> TYPO3 extension to resolve an address to a geo code via Google Maps API

This TYPO3 extension provides a geo coder service and an interface for geo codes.

## Installation

To install the Geo Location Service extension in your project, you have to require the package by using Composer:

```
composer require cpsit/geo-location-service
```

## Usage

You can implement the `GeoCodingInterface` in your location class and use the `GeoCoder` service to update or work with the geo information within that class. Check the source code for further information.
