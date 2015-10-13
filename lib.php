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
 * This file belongs to the redis cache store and contains the redis cache store class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The redis cache store class.
 *
 * @copyright 2014 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis extends cache_store implements cache_is_configurable {

    /**
     * The Redis cache store instance name.
     * @var string
     */
    protected $name;

    /**
     * Set to true if this should be a persistent connection.
     * @var bool
     */
    protected $persistentconnection = false;

    /**
     * Set to true is we are going to authenticate when opening a connection.
     * @var bool
     */
    protected $authenticate = false;

    /**
     * The password to use for authentication if your server requires it.
     * @var null|string
     */
    protected $authpassword = null;

    /**
     * The database to use, specified as an integer.
     * @var int
     */
    protected $database = 0;

    /**
     * Gets set to true once the redis cache store is in a usable state.
     * @var bool
     */
    protected $isready = false;

    /**
     * Gets set to true once this redis instance has been initialised.
     * @var bool
     */
    protected $isinitialised = false;

    /**
     * Connection to read server.
     * @var cachestore_redis_driver
     */
    protected $readconnection = null;

    /**
     * Connections to write servers.
     * @var cachestore_redis_driver[]
     */
    protected $writeconnections = array();

    /**
     * Read server connection information.
     *
     * @var cachestore_redis_connection_details
     */
    protected $readserver = null;

    /**
     * Write server connection information.
     *
     * @var cachestore_redis_connection_details[]
     */
    protected $writeservers = array();

    /**
     * Cache definition.
     *
     * We need to store this as we need it to configure the write connections
     * later.
     *
     * @var cache_definition
     */
    protected $definition;

    /**
     * Static method to check if the store requirements are met.
     *
     * @return bool True if the stores software/hardware requirements have been met and it can be used. False otherwise.
     */
    public static function are_requirements_met() {
        return extension_loaded('redis');
    }

    /**
     * Static method to check if a store is usable with the given mode.
     *
     * @param int $mode One of cache_store::MODE_*
     * @return bool
     */
    public static function is_supported_mode($mode) {
        return ($mode === self::MODE_APPLICATION || $mode === self::MODE_SESSION);
    }

    /**
     * Returns the supported features as a binary flag.
     *
     * @param array $configuration The configuration of a store to consider specifically.
     * @return int The supported features.
     */
    public static function get_supported_features(array $configuration = array()) {
        return self::SUPPORTS_DATA_GUARANTEE;
    }

    /**
     * Returns the supported modes as a binary flag.
     *
     * @param array $configuration The configuration of a store to consider specifically.
     * @return int The supported modes.
     */
    public static function get_supported_modes(array $configuration = array()) {
        return self::MODE_APPLICATION + self::MODE_SESSION;
    }

    /**
     * Generates an instance of the cache store that can be used for testing.
     *
     * Returns an instance of the cache store, or false if one cannot be created.
     *
     * @param cache_definition $definition
     * @return cache_store|false
     */
    public static function initialise_test_instance(cache_definition $definition) {
        if (!self::are_requirements_met()) {
            return false;
        }

        $config = get_config('cachestore_redis');
        if (empty($config->testreadserver)) {
            return false;
        }

        $configuration = array(
            'persistentconnection' => $config->testpersistentconnection,
            'readserver' => $config->testreadserver,
            'writeservers' => $config->testwriteservers,
        );

        $store = new cachestore_redis('Test redis', $configuration);
        $store->initialise($definition);

        return $store;
    }

    /**
     * Constructs a new Redis cache store instance.
     *
     * @param string $name The name of the cache store
     * @param array $configuration The configuration for this store instance.
     */
    public function __construct($name, array $configuration = array()) {
        $this->name = $name;

        $this->persistentconnection = $configuration['persistentconnection'];

        $this->readserver = $this->get_connection_details($configuration['readserver']);

        if (isset($configuration['authpassword']) && !empty($configuration['authpassword'])) {
            $this->authenticate = true;
            $this->authpassword = (string)$configuration['authpassword'];
        }

        if (isset($configuration['database']) && !empty($configuration['database'])) {
            $this->database = (int)$configuration['database'];
        }

        $this->isready = $this->ensure_connection_ready();
        if ($this->isready && debugging()) {
            $this->isready = $this->readconnection->ping();
        }

        // We'll prepare these details, but only connect when we want to write.
        if (!empty($configuration['writeservers'])) {
            // During testing this has to be declared as a string.
            $writeservers = is_array($configuration['writeservers'])
                    ? $configuration['writeservers']
                    : explode(PHP_EOL, $configuration['writeservers']);

            foreach ($writeservers as $writeserver) {
                $this->writeservers[] = $this->get_connection_details($writeserver);
            }
        }
    }

    /**
     * Split a server entry into its 5 parts.
     *
     * @param string $serverline
     * @return mixed[]
     */
    protected function get_connection_details($serverline) {
        $connection = new cachestore_redis_connection_details();
        $parts = explode(':', $serverline);

        if ($parts[0]) {
            $connection->host = (string)$parts[0];
        }

        if (isset($parts[1])) {
            $connection->port = (int)$parts[1];
        }

        if (isset($parts[2])) {
            $connection->timeout = (float)$parts[2];
        }

        if (isset($parts[3])) {
            $connection->persistentid = (string)$parts[3];
        }

        if (isset($parts[4])) {
            $connection->retryinterval = (int)$parts[4];
        }

        return $connection;
    }

    protected function connect(cachestore_redis_connection_details $details) {
        $connection = cachestore_redis_driver::instance(
            $details->host,
            $details->port,
            $this->database,
            $details->timeout,
            $details->persistentid,
            $details->retryinterval
        );
        if ($connection->is_connected() && $this->authenticate) {
            $connection->authenticate($this->authpassword);
        }

        return $connection;
    }

    /**
     * Ensures the Redis read connection is ready for use.
     * @return bool
     */
    protected function ensure_connection_ready() {
        if ($this->readconnection === null) {
            $this->readconnection = $this->connect($this->readserver);
        }
        return $this->readconnection->is_connected();
    }

    /**
     * Ensures the Redis write connections are ready for use.
     * @return bool
     */
    protected function ensure_write_ready() {
        if (count($this->writeservers) && !count($this->writeconnections)) {
            foreach ($this->writeservers as $writeserver) {
                $connection = $this->connect($writeserver);
                $connection->set_interaction_instance(
                        'hash', $this->definition);

                $this->writeconnections[] = $connection;
            }
        }
    }

    /**
     * Returns the name of this store instance.
     * @return string
     */
    public function my_name() {
        return $this->name;
    }

    /**
     * Initialises a new instance of the cache store given the definition the instance is to be used for.
     *
     * This function should be used to run any definition specific setup the store instance requires.
     * Tasks such as creating storage areas, or creating indexes are best done here.
     *
     * Its important to note that the initialise method is expected to always succeed.
     * If there are setup tasks that may fail they should be done within the __construct method
     * and should they fail is_ready should return false.
     *
     * @param cache_definition $definition
     */
    public function initialise(cache_definition $definition) {
        $this->definition = $definition;

        $this->readconnection->set_interaction_instance('hash', $definition);
        $this->isinitialised = true;
    }

    /**
     * Returns true if this cache store instance has been initialised.
     * @return bool
     */
    public function is_initialised() {
        return $this->isinitialised;
    }

    /**
     * Returns true if this cache store instance is ready to use.
     * @return bool
     */
    public function is_ready() {
        return $this->isready;
    }

    /**
     * Retrieves an item from the cache store given its key.
     *
     * @param string $key The key to retrieve
     * @return mixed The data that was associated with the key, or false if the key did not exist.
     */
    public function get($key) {
        return $this->readconnection->get($key);
    }

    /**
     * Retrieves several items from the cache store in a single transaction.
     *
     * If not all of the items are available in the cache then the data value for those that are missing will be set to false.
     *
     * @param array $keys The array of keys to retrieve
     * @return array An array of items from the cache. There will be an item for each key, those that were not in the store will
     *      be set to false.
     */
    public function get_many($keys) {
        return $this->readconnection->get_many($keys);
    }

    /**
     * Sets an item in the cache given its key and data value.
     *
     * @param string $key The key to use.
     * @param mixed $data The data to set.
     * @return bool True if the operation was a success false otherwise.
     */
    public function set($key, $data) {
        $this->ensure_write_ready();

        $status = true;

        foreach ($this->writeconnections as $writeconnection) {
            $status = $status && $writeconnection->set($key, $data);
        }

        return $status;
    }

    /**
     * Sets many items in the cache in a single transaction.
     *
     * @param array $keyvaluearray An array of key value pairs. Each item in the array will be an associative array with two
     *      keys, 'key' and 'value'.
     * @return int The number of items successfully set. It is up to the developer to check this matches the number of items
     *      sent ... if they care that is.
     */
    public function set_many(array $keyvaluearray) {
        $this->ensure_write_ready();

        $values = array();
        foreach ($keyvaluearray as $pair) {
            $values[$pair['key']] = $pair['value'];
        }

        $count = 0;
        foreach ($this->writeconnections as $writeconnection) {
            $count += $writeconnection->set_many($values);
        }

        return $count / count($this->writeconnections);
    }

    /**
     * Deletes an item from the cache store.
     *
     * @param string $key The key to delete.
     * @return bool Returns true if the operation was a success, false otherwise.
     */
    public function delete($key) {
        $this->ensure_write_ready();

        $status = true;
        foreach ($this->writeconnections as $writeconnection) {
            $status = $status && $writeconnection->delete($key);
        }

        return $status;
    }

    /**
     * Deletes several keys from the cache in a single action.
     *
     * @param array $keys The keys to delete
     * @return int The number of items successfully deleted.
     */
    public function delete_many(array $keys) {
        $this->ensure_write_ready();

        $count = 0;
        foreach ($this->writeconnections as $writeconnection) {
            $count += $writeconnection->delete_many($keys);
        }

        return $count / count($this->writeconnections);
    }

    /**
     * Purges the cache deleting all items within it.
     *
     * @return boolean True on success. False otherwise.
     */
    public function purge() {
        $this->ensure_write_ready();

        $status = true;
        foreach ($this->writeconnections as $writeconnection) {
            $status = $status && $writeconnection->purge();
        }

        return $status;
    }

    /**
     * Given the data from the add instance form this function creates a configuration array.
     *
     * @param stdClass $data
     * @return array
     */
    public static function config_get_configuration_array($data) {
        return array(
            'persistentconnection' => (bool) $data->persistentconnection,
            'readserver' => $data->readserver,
            'writeservers' => explode(PHP_EOL, $data->writeservers),
        );
    }

    /**
     * Allows the cache store to set its data against the edit form before it is shown to the user.
     *
     * @param moodleform $editform
     * @param array $config
     */
    public static function config_set_edit_form_data(moodleform $editform, array $config) {
        $data = array();

        if (!empty($config['persistentconnection'])) {
            $data['persistentconnection'] = $config['persistentconnection'];
        }
        
        if (!empty($config['readserver'])) {
            $data['readserver'] = $config['readserver'];
        }

        if (!empty($config['writeservers'])) {
            $data['writeservers'] = implode(PHP_EOL, $config['writeservers']);
        }

        $editform->set_data($data);
    }

    /**
     * Performs any necessary clean up when the store instance is being deleted.
     */
    public function instance_deleted() {
        $this->ensure_connection_ready();
        $this->ensure_write_ready();

        foreach ($this->writeconnections as $writeconnection) {
            $writeconnection->purge();
            $writeconnection->close();
            $writeconnection = null;
        }

        $this->readconnection->close();
        $this->readconnection = null;
    }
}
