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
 * This file belongs to the redis cache store and contains the settings for this plugin.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$settings->add(
    new admin_setting_heading(
        'cachestore_redis/session',
        new lang_string('session', 'cachestore_redis'),
        new lang_string('session_desc', 'cachestore_redis')
    )
);

$settings->add(
    new admin_setting_heading(
        'cachestore_redis/test',
        new lang_string('test', 'cachestore_redis'),
        new lang_string('test_desc', 'cachestore_redis')
    )
);

$settings->add(
    new admin_setting_configcheckbox(
        'cachestore_redis/testpersistentconnection',
        new lang_string('testpersistentconnection', 'cachestore_redis'),
        new lang_string('testpersistentconnection_desc', 'cachestore_redis'),
        false
    )
);

$settings->add(
    new admin_setting_configtext(
        'cachestore_redis/testreadserver',
        new lang_string('testreadserver', 'cachestore_redis'),
        new lang_string('testreadserver_desc', 'cachestore_redis'),
        '', PARAM_RAW
    )
);

$settings->add(
    new admin_setting_configtextarea(
        'cachestore_redis/testwriteservers',
        new lang_string('testwriteservers', 'cachestore_redis'),
        new lang_string('testwriteservers_desc', 'cachestore_redis'),
        '', PARAM_RAW, 60, 3
    )
);
