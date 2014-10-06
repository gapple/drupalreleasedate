Data API
========

Version parameters can be specified as a major version (e.g. `8`), or a major
and minor version (e.g. `8.0` or `8.1`).

Date parameters can be specified in any valid [PHP DateTime format](http://php.net/manual/en/datetime.formats.php)

The response object contains the normalized values of any parameters to the
request, including default values. For example, dates are returned in ISO8601 format.

*__Note__: This project is still in active development, and so the data response
format is subject to change at any time, though backwards compatibility will be
maintained as best as possible.*


## JSONP ##

To get a response as JSONP, specify a `callback` parameter in the request.
For example: `/data/samples.json?callback=samples_jsonp_callback`

A [CORS](https://en.wikipedia.org/wiki/Cross-origin_resource_sharing) header is
returned for all data requests, so JSONP is only required for support of older
browsers.

--

## Endpoints ##

### /data/samples.json ###

Retrieve a set of samples.

Keys may vary within each sample according to what values were fetched at the
time the sample was taken. A value may be `null` if an attempt to fetch the value
was made but failed.

Optional Parameters:
- __version__ *(string)*
- __from__ *(date)*
  Restrict the results to values on or after the specified date
- __to__ *(date)*
  Restrict the results to values on or before the specified date

```
{
    "version": "8.0",
    "modified": "2014-05-31T18:17:01-0700",
    "data": [
        {
            "when": "2013-05-29T12:35:00-0700",
            "critical_bugs": 26,
            "critical_tasks": 46,
            "major_bugs": 125,
            "major_tasks": 157
        }
    ]
}
```

### /data/historical-samples.json ###

Retrieve a set of samples at periods from the latest estimate.

Optional Parameters:
- __version__ *(string)*


```
{
    "version": "8.0",
    "modified": "2014-05-31T18:17:01-0700",
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

### /data/estimates.json ###

Retrieve basic information for a set of estimates.

Optional Parameters:
- __version__ *(string)*
- __from__ *(date)*
  Restrict the results to values on or after the specified date
- __to__ *(date)*
  Restrict the results to values on or before the specified date
- __limit__ *(integer)*
  Restrict the results to the requested number of newest values

```
{
    "version": "8.0",
    "modified": "2014-05-31T02:42:59-0700",
    "data": [
        {
            "when": "2013-05-31T08:46:00-0700",
            "estimate": "2013-08-24"
        }
    ]
}
```

### /data/estimate.json ###

Retrieve the detailed information for a single estimate.  If a specific date
isn't provided, the latest estimate is used.

Optional Parameters:
- __version__ *(string)*
- __date__ *(date)*

```
{
    "version": "8.0",
    "date": "2014-09-20T00:17:01-0700",
    "modified": "2014-09-20T00:17:01-0700",
    "data": {
        "when": "2014-09-20T00:17:01-0700",
        "estimate": "2026-09-03",
        "distribution": [
            {
                "duration": 4838400,
                "when": "2014-11-14",
                "count": 1
            },
            {
                "duration": 4924800,
                "when": "2014-11-15",
                "count": 1
            },
        ]
    }
}
```
