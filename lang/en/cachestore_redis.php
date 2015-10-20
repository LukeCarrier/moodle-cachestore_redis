<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file belongs to the redis cache store and contains strings belonging to this plugin.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['persistentconnection'] = 'Use persistent connections?';
$string['persistentconnection_help'] = 'Persistent connections yield marginally better performance, as they do not require Moodle to reconnect to the Redis server once per request, but are not compatible with all configurations';
$string['readserver'] = 'Read server';
$string['readserver_help'] = 'Enter your server details here, host:post:timeout:persistentid:retrytimeout';
$string['writeservers'] = 'Write servers';
$string['writeservers_help'] = 'All servers listed here will have any write operations applied to this store cascaded across them, allowing for load balancing across Redis servers.';
$string['pluginname'] = 'Redis';
$string['exception_operationnotconnected'] = 'The requested operation cannot be performed as there is not an open connection to a Redis server';
$string['session'] = 'Session handling';
$string['session_desc'] = 'This plugin also provides two session handler implementations.

<h1>Non-clustered</h1>

In this configuration, session storage is load balanced across a set of weighted Redis servers. Place the following configuration options into <code>config.php</code>, above the include of <code>/lib/setup.php</code>:
<pre>
    <code>
        $CFG->session_handler_class   = \'\cachestore_redis\session\nonclustered\handler\';
        $CFG->session_redis_save_path = implode(\', \', array(
            \'tcp://127.0.0.1:6379?weight=1&persistent=0&timeout=30&prefix=abc\',
            \'tcp://127.0.0.1:6380?weight=1&persistent=0&timeout=30&prefix=abc\',
        ));
    </code>
</pre>

For formatting detail, see the <a href="https://github.com/phpredis/phpredis#php-session-handler">Redis session handler</a> documentation.

<h1>Clustered (experimental)</h1>

In this configuration, each application server reads its session data from a single server, and writes to session storage are synchronised across a set of Redis servers. To enable this configuration, place the following configuration options into <code>config.php</code>, above the include of <code>/lib/setup.php</code>:
<pre>
    <code>
        $CFG->session_handler_class = \'cachestore_redis_clustered_session_handler\';
        $CFG->session_redis_clustered_readserver = \'\';
        $CFG->session_redis_clustered_writeservers = \'\';
    </code>
</pre>
';
$string['test'] = 'Testing configuration';
$string['test_desc'] = 'Configuration to use during the cache store performance test. For PHPUnit configuration, see the directions in <code>README.md</code>.';
$string['testpersistentconnection'] = 'Use persistent connections during testing';
$string['testpersistentconnection_desc'] = 'Persistent connections yield marginally better performance, as they do not require Moodle to reconnect to the Redis server once per request, but are not compatible with all configurations';
$string['testreadserver'] = 'Test read server';
$string['testreadserver_desc'] = 'Enter the read server to use for testing - usually 127.0.0.1';
$string['testwriteservers'] = 'Test write server';
$string['testwriteservers_desc'] = 'Enter the write servers to use for testing';
$string['clusteredsessionhandlerproblem'] = 'Initialising the Redis session handler failed. Please notify the server administrator.';
$string['clusteredsessionhandlererror'] = 'A Redis operation failed; the error was "{$a}"';
