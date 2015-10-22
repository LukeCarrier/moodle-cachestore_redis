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
 * This file belongs to the redis cache store and contains the redis driver class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cachestore_redis;

/**
 * The Redis cache store driver class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connection_details {
    /**
     * Hostname.
     * @var string
     */
    public $host;

    /**
     * Port number.
     * @var int
     */
    public $port;

    /**
     * Timeout period.
     * @var int
     */
    public $timeout;

    /**
     * Is the connection persistent?
     * @var bool
     */
    public $persistent;

    /**
     * Persistent connection ID.
     * @var string
     */
    public $persistentid;

    /**
     * Retry interval.
     * @var int
     */
    public $retryinterval;

    /**
     * Server weight.
     * @var int
     */
    public $weight;

    /**
     * Authentication password.
     * @var string
     */
    public $auth;

    /**
     * Alternate database.
     * @var int
     */
    public $database;
}
