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

$string['readserver'] = 'Read server';
$string['readserver_help'] = 'Enter your server details here, host:post:timeout:persistentid:retrytimeout';
$string['writeservers'] = 'Write servers';
$string['writeservers_help'] = 'All servers listed here will have any write operations applied to this store cascaded across them, allowing for load balancing across Redis servers.';
$string['pluginname'] = 'Redis cachestore';
$string['exception_operationnotconnected'] = 'The requested operation cannot be performed as there is not an open connection to a Redis server';
$string['testreadserver'] = 'Test read server';
$string['testreadserver_desc'] = 'Enter the read server to use for testing - usually 127.0.0.1';
