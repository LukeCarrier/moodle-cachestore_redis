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

namespace cachestore_redis\session;

use cachestore_redis;
use core\session\exception;
use core\session\handler as core_handler;

/**
 * Base session handler.
 *
 * Modelled after {@link https://gist.github.com/dcai/fc0eab6479728140319a}.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_handler extends core_handler {
    /**
     * Get read connections.
     * @return \Redis[]
     */
    abstract public function get_read_connections();

    /**
     * Get write connections.
     * @return \Redis[]
     */
    abstract public function get_write_connections();

    /**
     * @override \core\session\handler
     */
    public function init() {
        if (!cachestore_redis::are_requirements_met()) {
            throw new exception('sessionhandlerproblem', 'error', '', null,
                'Redis cache store reports that its requirements are not met');
        }
    }

    /**
     * @override \core\session\handler
     */
    public function session_exists($sid) {
        $exists = false;

        foreach ($this->get_read_connections() as $connection) {
            if ($connection->exists($sid)) {
                $exists = true;
            }

            $connection->close();
        }

        return $exists;
    }

    /**
     * @override \core\session\handler
     */
    public function kill_all_sessions() {
        global $DB;

        $connections = $this->get_write_connections();
        $sessions    = $DB->get_recordset('sessions', null, 'id DESC', 'id, sid');

        foreach ($sessions as $session) {
            foreach ($connections as $connection) {
                $connection->delete($session->sid);
            }
        }
        $sessions->close();

        foreach ($connections as $connection) {
            $connection->close();
        }
    }

    /**
     * @override \core\session\handler
     */
    public function kill_session($sid) {
        foreach ($this->get_write_connections() as $connection) {
            $connection->delete($sid);
            $connection->close();
        }
    }
}
