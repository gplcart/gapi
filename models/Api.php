<?php

/**
 * @package Google API
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\gapi\models;

use gplcart\core\Library;
use gplcart\modules\gapi\models\Credential as ModuleGapiCredentialModel;
use GuzzleHttp\Client;
use LogicException;
use OutOfBoundsException;
use RuntimeException;

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
     * @param null|int $credential_id
     * @param bool $use_own_certificate
     * @return \Google_Client
     * @throws LogicException
     * @throws OutOfBoundsException
     */
    public function getClient($credential_id = null, $use_own_certificate = true)
    {
        $this->library->load('gapi');

        if (!class_exists('Google_Client')) {
            throw new LogicException('Class \Google_Client not found');
        }

        $client = new \Google_Client;

        if ($use_own_certificate) {
            $http = new Client(array('verify' => __DIR__ . '/../certificates/cacert.pem'));
            $client->setHttpClient($http);
        }

        if (isset($credential_id)) {

            $credential = $this->credential->get($credential_id);

            if (empty($credential['data']['file'])) {
                throw new OutOfBoundsException('Credential file path is empty');
            }

            $client->setAuthConfig(gplcart_file_absolute($credential['data']['file']));
        }


        return $client;
    }

    /**
     * Returns a Google service class instance
     * @param string $service_name
     * @param \Google_Client $client
     * @return object
     * @throws LogicException
     */
    public function getService($service_name, \Google_Client $client)
    {
        $this->library->load('gapi');

        $class = "Google_Service_$service_name";

        if (!class_exists($class)) {
            throw new LogicException("Class $class not found");
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
