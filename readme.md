Drupal Release Date
===================

System for tracking issue counts against the next version of Drupal core, and
estimating a release date based on collected samples.

Access the site at http://drupalreleasedate.com/

[![Build Status](https://img.shields.io/travis/gapple/drupalreleasedate/master.svg?style=flat)](https://travis-ci.org/gapple/drupalreleasedate)
[![Coverage Status](https://img.shields.io/coveralls/gapple/drupalreleasedate/master.svg?style=flat)](https://coveralls.io/r/gapple/drupalreleasedate?branch=master)


## Data API ##

Public JSON feeds are provided for access to all of the site's data.

For more information on available endpoints and the response format, [view the API documentation](api.md).


## Installation and Setup ##

 1. Install dependencies with [Composer](http://getcomposer.org/) by running
    `composer install` in the root directory of the project
 2. Copy `config/default.config.php` to `config/config.php`, and adjust as
    needed
 3. Run `bin/console install` to set up the database
 4. Configure Apache to serve `web` as the document root

## Running Tests ##

PHPUnit tests can be run with `vendor/bin/phpunit`
