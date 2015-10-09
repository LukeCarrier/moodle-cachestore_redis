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
 * This file belongs to the redis cache store and contains the add instance form for the redis plugin.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cache/forms.php');

/**
 * The Redis add instance form used when adding/editing a Redis cache store instance.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis_addinstance_form extends cachestore_addinstance_form {

    /**
     * Add the desired form elements.
     */
    protected function configuration_definition() {
        $form = $this->_form;

        $form->addElement('advcheckbox', 'persistentconnection', get_string('persistentconnection', 'cachestore_redis'));
        $form->addHelpButton('persistentconnection', 'persistentconnection', 'cachestore_redis');

        $form->addElement('text', 'readserver', get_string('readserver', 'cachestore_redis'));
        $form->addHelpButton('readserver', 'readserver', 'cachestore_redis');
        $form->addRule('readserver', get_string('required'), 'required');
        $form->setType('readserver', PARAM_RAW);

        $form->addElement('textarea', 'writeservers', get_string('writeservers', 'cachestore_redis'));
        $form->addHelpButton('writeservers', 'writeservers', 'cachestore_redis');
        $form->setType('writeservers', PARAM_RAW);
    }
}
