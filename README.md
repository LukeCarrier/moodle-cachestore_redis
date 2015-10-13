# Redis cache store for Moodle

A Redis driver for the Moodle Unified Cache, offering support for clustered
writes.

* * *

## Installation

1. Place this directory into ```/cache/stores/redis```.
2. Execute the Moodle upgrades.
3. Configure caching in Site administration -> Plugins -> Caching ->
   Configuration.

## Testing

This plugin can be tested both via PHPUnit and the Moodle cache store
performance testing tool.

### Via PHPUnit

Add the following options to the site's ```config.php```, somewhere above the
```/lib/setup.php``` include, and tailor to your configuration:

    define('TEST_CACHESTORE_REDIS_TESTREADSERVER',   '127.0.0.1:6379');
    define('TEST_CACHESTORE_REDIS_TESTWRITESERVERS', '127.0.0.1:6379' . PHP_EOL
                                                   . '127.0.0.1:6380');

Assuming your environment is correctly configured for PHPUnit testing, you can
now execute the tests as follows:

    $ vendor/bin/phpunit cachestore_redis_test cache/stores/redis/tests/redis_test.php

### Via the Moodle cache store performance test

1. Navigate to Site administration -> Plugins -> Caching -> Cache stores ->
   Redis and configure the read and write server options accordingly for your
   testing environment.
2. Navigate to Site administration -> Plugins -> Caching -> Test performance and
   fire off some tests.
