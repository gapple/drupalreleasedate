Drupal Release Date
===================

System for tracking issue counts against the next version of Drupal core, and
estimating a release date based on collected samples.

Access the site at http://drupalreleasedate.com/


## Data API ##

Public JSON feeds are provided for access to all of the site's data.

*__Note__: This project is still in active development, and so the data response
format is subject to change at any time, though backwards compatibility will be
maintained as best as possible.*


### Endpoints ###

__/data/samples.json__

```
[
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
```

__/data/changes.json__

```
{
    "modified":"2014-01-25 00:17:01",
    "data":{
        "critical":{
            "day":2,
            "week":2,
            "month":18,
            "quarter":17,
            "half":-1
        }
    }
}
```

__/data/estimates.json__

```
[
    {
        "when": "2013-05-31 08:46:00",
        "estimate": "2013-08-24 04:35:22"
    }
]
```

### JSONP ###

To get a response as JSONP, specify a `callback` parameter in the request.
For example: `/data/samples.json?callback=samples_jsonp_callback`

A [CORS](https://en.wikipedia.org/wiki/Cross-origin_resource_sharing) header is
returned for all data requests, so JSONP is only required for support of older
browsers.

## Installation and Setup ##

 1. Install dependencies with [Composer](http://getcomposer.org/) by running
    `composer install` in the root directory of the project.
 2. Initialize a database with the schema file in `/src/schema.sql`
 3. Copy `/config/default.config.php` to `/config/config.php`, and adjust as
    needed.
 4. Setup Apache to serve `/web` as the document root
