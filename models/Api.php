<?php

/**
 * @package Google API
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\gapi\models;

use gplcart\core\Library;
use RuntimeException;
use OutOfRangeException;
use gplcart\modules\gapi\models\Credential as ModuleGapiCredentialModel;

/**
 * Manages basic behaviors and data related Google API methods
 */
class Api
{

    /**
     * Library class instance
     * @var \gplcart\core\Library $library
     */
    protected $library;

    /**
     * Credential model instance
     * @var \gplcart\modules\gapi\models\Credential $credential
     */
    protected $credential;

    /**
     * @param Library $library
     * @param ModuleGapiCredentialModel $credential
     */
    public function __construct(Library $library, ModuleGapiCredentialModel $credential)
    {
        $this->library = $library;
        $this->credential = $credential;
    }

    /**
     * Returns Google Client object
     * @param array $config
     * @param null|int $credential_id
     * @return \Google_Client
     * @throws RuntimeException
     * @throws OutOfRangeException
     */
    public function getClient(array $config = array(), $credential_id = null)
    {
        if (isset($credential_id)) {

            $credential = $this->credential->get($credential_id);

            if (empty($credential['data']['file'])) {
                throw new OutOfRangeException('Credential file path is empty');
            }

            $config += array('setAuthConfig' => gplcart_path_absolute($credential['data']['file']));
        }

        $this->library->load('gapi');

        if (!class_exists('Google_Client')) {
            throw new RuntimeException('Class \Google_Client not found');
        }

        $client = new \Google_Client;

        if (empty($config)) {
            return $client;
        }

        foreach ($config as $method => $args) {

            if (!is_callable(array($client, $method))) {
                continue;
            }

            if (!is_array($args)) {
                $args = array($args);
            }

            call_user_func_array(array($client, $method), $args);
        }

        return $client;
    }

    /**
     * Returns a Google service class instance
     * @param string $service_name
     * @param \Google_Client $client
     * @return object
     * @throws RuntimeException
     */
    public function getService($service_name, \Google_Client $client)
    {
        $this->library->load('gapi');

        $class = "Google_Service_$service_name";

        if (!class_exists($class)) {
            throw new RuntimeException("Class $class not found");
        }

        return new $class($client);
    }

    /**
     * Returns an array of Google service names supported by the library
     * @return array
     * @throws RuntimeException
     */
    public function getServiceNames()
    {
        $dir = __DIR__ . '/../vendor/google/apiclient-services/src/Google/Service';

        if (!is_dir($dir) || !is_readable($dir)) {
            throw new RuntimeException("Cannot read directory $dir");
        }

        $this->library->load('gapi');

        $names = array();

        foreach (glob("$dir/*.php") as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $class = "Google_Service_$name";
            if (class_exists($class) && $class instanceof \Google_Service) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
