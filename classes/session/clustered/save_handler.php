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

use SessionHandlerInterface;

/**
 * Clustered save handler.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_handler implements SessionHandlerInterface {
    /**
     * Moodle session handler implementation.
     * @var \cachestore_redis\session\clustered\handler
     */
    protected $handler;

    /**
     * Initialiser.
     * @param \cachestore_redis\session\clustered\handler $handler
     */
    public function __construct(handler $handler) {
        $this->handler = $handler;
    }

    /**
     * @override \SessionHandlerInterface
     */
    public function open($savepath, $sessionid) {
        /* We don't need to do anything here -- we'll open and close our
         * connections as necessary for specific operations. */
        return true;
    }

    /**
     * @override \SessionHandlerInterface
     */
    public function close() {
        /* We don't need to do anything here -- we'll open and close our
         * connections as necessary for specific operations. */
        return true;
    }

    /**
     * @override \SessionHandlerInterface
     */
    public function gc($maxlifetime) {
        /* We're configuring expiration times on our session records, so
         * there's no need for additional garbage collection. */
        return true;
    }

    /**
     * @override \SessionHandlerInterface
     */
    public function write($sessionid, $sessiondata) {
        global $CFG;

        $status = true;

        /** @var \Redis[] $connections */
        $connections = $this->handler->get_write_connections();
        foreach ($connections as $connection) {
            $thisstatus = $connection->set($sessionid, $sessiondata);

            $this->maybe_debug($thisstatus, $connection);
            $status = $thisstatus && $status;

            $thisstatus = $connection->expire($sessionid, (int)$CFG->sessiontimeout);
            $this->maybe_debug($thisstatus, $connection);
            $status = $thisstatus && $status;

            $connection->close();
        }

        return $status;
    }

    /**
     * @override \SessionHandlerInterface
     */
    public function read($sessionid) {
        global $CFG;

        /** @var \Redis[] $connections */
        $connections = $this->handler->get_read_connections();

        $sessiondata = null;
        foreach ($connections as $connection) {
            $sessiondata = $connection->get($sessionid);
            $connection->close();
        }

        /** @var \Redis[] $connections */
        $connections = $this->handler->get_write_connections();
        foreach ($connections as $connection) {
            $thisstatus = $connection->expire($sessionid, (int)$CFG->sessiontimeout);
            $this->maybe_debug($thisstatus && $sessiondata, $connection);

            $connection->close();
        }

        return $sessiondata;
    }

    /**
     * @override \SessionHandlerInterface
     */
    public function destroy($sessionid) {
        $status = true;

        /** @var \Redis[] $connections */
        $connections = $this->handler->get_write_connections();
        foreach ($connections as $connection) {
            $thisstatus = $connection->del($sessionid) > 0;
            $this->maybe_debug($thisstatus, $connection);

            $connection->close();

            $status = $status && $thisstatus;
        }

        return $status;
    }

    /**
     * Print an error if the operation failed and debugging is enabled.
     * @param bool $status
     * @param \Redis $connection
     * @return void
     * @throws \coding_exception
     */
    protected function maybe_debug($status, $connection) {
        global $CFG;

        if (!$status && $CFG->debugdeveloper) {
            debugging(get_string('clusteredsessionhandlererror', 'cachestore_redis',
                                 $connection->getLastError()));
        }
    }
}
