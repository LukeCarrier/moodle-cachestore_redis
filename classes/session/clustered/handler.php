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

namespace cachestore_redis\session\clustered;

use cachestore_redis;
use cachestore_redis\session\base_handler;
use cachestore_redis_connection_details;
use core\session\exception;
use Redis;

/**
 * The Redis cache store clustered session driver class.
 *
 * Modelled after {@link https://gist.github.com/dcai/fc0eab6479728140319a}.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class handler extends base_handler {
    /**
     * Use persistent connections?
     * @var bool
     */
    protected $persistent;

    /**
     * Read server connection details.
     * @var cachestore_redis_connection_details
     */
    protected $readserver;

    /**
     * Write server connection details.
     * @var cachestore_redis_connection_details
     */
    protected $writeservers;

    /**
     * Lock acquisition timeout.
     * @var int
     */
    protected $acquiretimeout;

    /**
     * Initialiser.
     */
    public function __construct() {
        global $CFG;

        $this->acquiretimeout = $CFG->session_redis_clustered_acquiretimeout;
        $this->persistent     = $CFG->session_redis_clustered_persistent;

        $this->readserver = cachestore_redis::get_connection_details(
                $CFG->session_redis_clustered_readserver);

        $this->writeservers = array();
        $writeservers = explode(
                PHP_EOL, $CFG->session_redis_clustered_writeservers);
        foreach ($writeservers as $writeserver) {
            $this->writeservers[] = cachestore_redis::get_connection_details(
                    $writeserver);
        }
    }

    /**
     * @override \cachestore_redis\session\base_handler
     */
    public function init() {
        if (!session_set_save_handler(new save_handler($this))) {
            throw new exception('clusteredsessionhandlerproblem',
                                'cachestore_redis');
        }

        parent::init();
    }

    /**
     * @override \cachestore_redis\session\base_handler
     */
    public function get_read_connections() {
        return array(
            $this->make_connection($this->readserver),
        );
    }

    /**
     * @override \cachestore_redis\session\base_handler
     */
    public function get_write_connections() {
        $connections = array();
        foreach ($this->writeservers as $details) {
            $connections[] = $this->make_connection($details);
        }

        return $connections;
    }

    /**
     * Create a connection object.
     * @param \cachestore_redis_connection_details $details
     * @return \Redis
     */
    protected function make_connection(cachestore_redis_connection_details $details) {
        $connection = new Redis();
        $connection->connect($details->host, $details->port, $details->timeout);

        return $connection;
    }
}
