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

use core\session\exception;
use core\session\handler;

/**
 * The Redis cache store session driver class.
 *
 * Modelled after {@link https://gist.github.com/dcai/fc0eab6479728140319a}.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis_session_handler extends handler {
    /**
     * Save path delimiter.
     * @var string
     */
    const SAVE_PATH_DELIMITER = ',';

    /**
     * Lock acquisition timeout.
     * @var int
     */
    protected $acquiretimeout;

    /**
     * Save path (server list, with options).
     * @var string
     */
    protected $savepath;

    /**
     * Server connection details.
     * @var \cachestore_redis_connection_details
     */
    protected $servers;

    /**
     * @override \core\session\handler
     */
    public function __construct() {
        global $CFG;

        $this->acquiretimeout = property_exists($CFG, 'session_redis_acquire_timeout')
                ? $CFG->session_redis_acquire_timeout : ini_get('max_execution_time');
        $this->savepath = property_exists($CFG, 'session_redis_save_path')
                ? $CFG->session_redis_save_path : '';

        $this->servers = $this->get_connection_details();
    }

    /**
     * @override \core\session\handler
     */
    public function init() {
        if (!cachestore_redis::are_requirements_met()) {
            throw new exception('sessionhandlerproblem', 'error', '', null,
                                'Redis cache store reports that its requirements are not met');
        }

        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path',    $this->savepath);
    }

    /**
     * @override \core\session\handler
     */
    public function start() {
        set_time_limit($this->acquiretimeout);

        return parent::start();
    }

    /**
     * @override \core\session\handler
     */
    public function session_exists($sid) {
        $exists = false;

        foreach ($this->get_connections() as $connection) {
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

        $connections = $this->get_connections();
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
        foreach ($this->get_connections() as $connection) {
            $connection->delete($sid);
            $connection->close();
        }
    }

    /**
     * Parse save path for connection details.
     * @return \cachestore_redis
     */
    protected function get_connection_details() {
        $paths   = explode(static::SAVE_PATH_DELIMITER, $this->savepath);
        $result = array();

        foreach ($paths as $path) {
            $path = trim(rtrim($path));
            $uri  = parse_url($path);

            $query = array();
            parse_str($uri['query'], $query);

            $details = new cachestore_redis_connection_details();
            $details->host       = array_key_exists('host', $uri) ? $uri['host'] : null;
            $details->port       = array_key_exists('port', $uri) ? $uri['port'] : 6379;

            $details->weight     = array_key_exists('weight',     $query) ? $query['weight']     : null;
            $details->timeout    = array_key_exists('timeout',    $query) ? $query['timeout']    : null;
            $details->persistent = array_key_exists('persistent', $query) ? $query['persistent'] : null;
            $details->prefix     = array_key_exists('prefix',     $query) ? $query['prefix']     : null;
            $details->auth       = array_key_exists('auth',       $query) ? $query['auth']       : null;
            $details->database   = array_key_exists('database',   $query) ? $query['database']   : null;

            $result[] = $details;
        }

        return $result;
    }

    /**
     * Connect to all servers using connection details parsed from the save path.
     * @return \Redis[]
     */
    protected function get_connections() {
        $connections = array();

        foreach ($this->servers as $details) {
            $connection = new Redis();
            $connection->connect($details->host, $details->port, $this->acquiretimeout);
            $connection->setOption(Redis::OPT_PREFIX, $details->prefix);

            $connections[] = $connection;
        }

        return $connections;
    }
}
