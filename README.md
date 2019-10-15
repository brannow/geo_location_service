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
