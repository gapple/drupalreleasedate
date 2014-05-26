Drupal Release Date
===================

System for tracking issue counts against the next version of Drupal core, and
estimating a release date based on collected samples.

Access the site at http://drupalreleasedate.com/


## Data API ##

Public JSON feeds are provided for access to all of the site's data.

Array values are typically returned ordered from oldest to newest values, but
this may not be guaranteed.

*__Note__: This project is still in active development, and so the data response
format is subject to change at any time, though backwards compatibility will be
maintained as best as possible.*


### Endpoints ###

__/data/samples.json__

```
{
    "modified": "2014-01-25 00:17:01",
    "data": [
        {
            "when": "2013-05-29 12:35:00",
            "critical_bugs": 26,
            "critical_tasks": 46,
            "major_bugs": 125,
            "major_tasks": 157,
            "normal_bugs": null,
            "normal_tasks": null
        }
    ]
}
```

__/data/historical-samples.json__

```
{
    "modified": "2014-03-21 12:17:02",
    "data": {
        "current": {
            "critical_bugs": 39,
            "critical_tasks": 78,
            "major_bugs": 231,
            "major_tasks": 253,
            "normal_bugs": 1938,
            "normal_tasks": 2946
        },
        "day": {
            "critical_bugs": 42,
            "critical_tasks": 77,
            "major_bugs": 231,
            "major_tasks": 253,
            "normal_bugs": 1935,
            "normal_tasks": 2946
        },
        "week": {
            "critical_bugs": 42,
            "critical_tasks": 73,
            "major_bugs": 232,
            "major_tasks": 262,
            "normal_bugs": 1927,
            "normal_tasks": 2931
        },
        "month": {
            "critical_bugs": 38,
            "critical_tasks": 78,
            "major_bugs": 228,
            "major_tasks": 258,
            "normal_bugs": 1954,
            "normal_tasks": 2948
        },
        "quarter": {
            "critical_bugs": 33,
            "critical_tasks": 87,
            "major_bugs": 211,
            "major_tasks": 279,
            "normal_bugs": null,
            "normal_tasks": 3011
        },
        "half": {
            "critical_bugs": 41,
            "critical_tasks": 84,
            "major_bugs": 199,
            "major_tasks": 209,
            "normal_bugs": 1925,
            "normal_tasks": 3130
        }
    }
}
```

__/data/estimates.json__

Optional Parameters:
- __limit__ *(integer)*
  Restrict the results to the requested number of newest values.

```
{
    "modified": "2014-01-25 02:48:34",
    "data": [
        {
            "when": "2013-05-31 08:46:00",
            "estimate": "2013-08-24 04:35:22"
        }
    ]
}
```

### JSONP ###

To get a response as JSONP, specify a `callback` parameter in the request.
For example: `/data/samples.json?callback=samples_jsonp_callback`

A [CORS](https://en.wikipedia.org/wiki/Cross-origin_resource_sharing) header is
returned for all data requests, so JSONP is only required for support of older
browsers.

## Installation and Setup ##

 1. Install dependencies with [Composer](http://getcomposer.org/) by running
    `composer install` in the root directory of the project
 2. Copy `config/default.config.php` to `config/config.php`, and adjust as
    needed
 3. Run `bin/console install` to set up the database
 4. Configure Apache to serve `web` as the document root

## Running Tests ##

PHPUnit tests can be run with `vendor/bin/phpunit`

Some global constants affect the behaviour of tests, particularly those that
check the aggregate outcome of random results.  To change these constants, copy
`phpunit.xml.dist` to `phpunit.xml`, and adjust the values as needed.
