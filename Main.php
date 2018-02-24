<?php

/**
 * @package Google API
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2018, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace gplcart\modules\gapi;

use Exception;
use gplcart\core\Config;
use gplcart\core\Container;
use gplcart\core\Library;

/**
 * Main class for Google API module
 */
class Main
{

    /**
     * Database class instance
     * @var \gplcart\core\Database $db
     */
    protected $db;

    /**
     * Library class instance
     * @var \gplcart\core\Library $library
     */
    protected $library;

    /**
     * @param Config $config
     * @param Library $library
     */
    public function __construct(Config $config, Library $library)
    {
        $this->library = $library;
        $this->db = $config->getDb();
        $this->db->addScheme($this->getDbScheme());
    }

    /**
     * Implements hook "library.list"
     * @param array $libraries
     */
    public function hookLibraryList(array &$libraries)
    {
        $libraries['gapi'] = array(
            'name' => 'Google API', // @text
            'description' => 'A PHP client library for accessing Google APIs', // @text
            'type' => 'php',
            'module' => 'gapi',
            'url' => 'https://github.com/google/google-api-php-client',
            'download' => 'https://github.com/google/google-api-php-client/releases/download/v2.2.1/google-api-php-client-2.2.1_PHP54.zip',
            'version_source' => array(
                'file' => 'vendor/google/apiclient/src/Google/Client.php',
                'pattern' => '/LIBVER(?:.*)"(.*)"/',
            ),
            'files' => array(
                'vendor/autoload.php',
            )
        );
    }

    /**
     * Implements hook "module.enable.after"
     */
    public function hookModuleEnableAfter()
    {
        $this->library->clearCache();
    }

    /**
     * Implements hook "module.disable.after"
     */
    public function hookModuleDisableAfter()
    {
        $this->library->clearCache();
    }

    /**
     * Implements hook "module.install.before"
     * @param null|string
     */
    public function hookModuleInstallBefore(&$result)
    {
        try {
            $this->db->importScheme('module_gapi_credential', $this->getDbScheme());
        } catch (Exception $ex) {
            $result = $ex->getMessage();
        }
    }

    /**
     * Implements hook "module.install.after"
     */
    public function hookModuleInstallAfter()
    {
        $this->library->clearCache();
    }

    /**
     * Implements hook "module.uninstall.after"
     */
    public function hookModuleUninstallAfter()
    {
        $this->library->clearCache();
        $this->db->deleteTable('module_gapi_credential');
    }

    /**
     * Implements hook "route.list"
     * @param mixed $routes
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/report/gapi'] = array(
            'menu' => array(
                'admin' => 'Google API credentials' // @text
            ),
            'access' => 'module_gapi_credential',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\gapi\\controllers\\Credential', 'listCredential')
            )
        );

        $routes['admin/module/settings/gapi/credential/add/(\w+)'] = array(
            'access' => 'module_gapi_credential_add',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\gapi\\controllers\\Credential', 'addCredential')
            )
        );

        $routes['admin/module/settings/gapi/credential/edit/(\d+)'] = array(
            'access' => 'module_gapi_credential_edit',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\gapi\\controllers\\Credential', 'editCredential')
            )
        );
    }

    /**
     * Implements hook "user.role.permissions"
     * @param array $permissions
     */
    public function hookUserRolePermissions(array &$permissions)
    {
        $permissions['module_gapi_credential'] = 'Google API credential: access'; // @text
        $permissions['module_gapi_credential_add'] = 'Google API credential: add'; // @text
        $permissions['module_gapi_credential_edit'] = 'Google API credential: edit'; // @text
        $permissions['module_gapi_credential_delete'] = 'Google API credential: delete'; // @text
    }

    /**
     * Returns Google API credential
     * @param int $id
     * @return array
     */
    public function getCredential($id)
    {
        return $this->getCredentialModel()->get($id);
    }

    /**
     * Returns an array of existing Google API credentials
     * @param array $options
     * @return array
     */
    public function getCredentials(array $options = array())
    {
        return $this->getCredentialModel()->getList($options);
    }

    /**
     * Returns Google Client object
     * @param null|int $credential_id
     * @param bool $use_own_certificate
     * @return \Google_Client
     */
    public function getGoogleClient($credential_id = null, $use_own_certificate = true)
    {
        return $this->getApiModel()->getClient($credential_id, $use_own_certificate);
    }

    /**
     * Returns a Google service class instance
     * @param string $service_name
     * @param \Google_Client $client
     * @return object \Google_Service_SERVICE-NAME
     */
    public function getGoogleService($service_name, \Google_Client $client)
    {
        return $this->getApiModel()->getService($service_name, $client);
    }

    /**
     * Returns an array of Google service names supported by the library
     */
    public function getGoogleServiceNames()
    {
        return $this->getApiModel()->getServiceNames();
    }

    /**
     * Returns the Credential model instance
     * @return \gplcart\modules\gapi\models\Credential
     */
    protected function getCredentialModel()
    {
        /** @var \gplcart\modules\gapi\models\Credential $instance */
        $instance = Container::get('gplcart\\modules\\gapi\\models\\Credential');
        return $instance;
    }

    /**
     * Returns the API model instance
     * @return \gplcart\modules\gapi\models\Api
     */
    protected function getApiModel()
    {
        /** @var \gplcart\modules\gapi\models\Api $instance */
        $instance = Container::get('gplcart\\modules\\gapi\\models\\Api');
        return $instance;
    }

    /**
     * Returns an array of database scheme
     * @return array
     */
    protected function getDbScheme()
    {
        return array(
            'module_gapi_credential' => array(
                'fields' => array(
                    'created' => array('type' => 'int', 'length' => 10, 'not_null' => true),
                    'name' => array('type' => 'varchar', 'length' => 255, 'not_null' => true),
                    'type' => array('type' => 'varchar', 'length' => 255, 'not_null' => true),
                    'data' => array('type' => 'blob', 'not_null' => true, 'serialize' => true),
                    'modified' => array('type' => 'int', 'length' => 10, 'not_null' => true, 'default' => 0),
                    'credential_id' => array('type' => 'int', 'length' => 10, 'auto_increment' => true, 'primary' => true)
                )
            )
        );
    }
}
