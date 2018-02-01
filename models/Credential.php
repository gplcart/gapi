<?php

/**
 * @package Google API
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\gapi\models;

use gplcart\core\interfaces\Crud as CrudInterface;
use gplcart\core\Config;
use gplcart\core\Handler;
use gplcart\core\Hook;
use gplcart\core\models\Translation as TranslationModel;

/**
 * Manages basic behaviors and data related Google API credentials
 */
class Credential implements CrudInterface
{
    /**
     * Database class instance
     * @var \gplcart\core\Database $db
     */
    protected $db;

    /**
     * Hook class instance
     * @var \gplcart\core\Hook $hook
     */
    protected $hook;

    /**
     * Translation model instance
     * @var \gplcart\core\models\Translation $translation
     */
    protected $translation;

    /**
     * @param Hook $hook
     * @param Config $config
     * @param TranslationModel $translation
     */
    public function __construct(Hook $hook, Config $config, TranslationModel $translation)
    {
        $this->hook = $hook;
        $this->db = $config->getDb();
        $this->translation = $translation;
    }

    /**
     * Loads a credential
     * @param int|array|string $id
     * @return array
     */
    public function get($id)
    {
        $sql = 'SELECT * FROM module_gapi_credential WHERE credential_id=?';
        return $this->db->fetch($sql, array($id), array('unserialize' => 'data'));
    }

    /**
     * Returns an array of credentials or counts them
     * @param array $options
     * @return array|integer
     */
    public function getList(array $options = array())
    {
        $sql = 'SELECT *';

        if (!empty($options['count'])) {
            $sql = 'SELECT COUNT(credential_id)';
        }

        $sql .= ' FROM module_gapi_credential WHERE credential_id IS NOT NULL';

        $conditions = array();

        if (isset($options['type'])) {
            $sql .= ' AND type=?';
            $conditions[] = $options['type'];
        }

        $allowed_order = array('asc', 'desc');
        $allowed_sort = array('name', 'credential_id', 'created', 'modified', 'type');

        if (isset($options['sort'])
            && in_array($options['sort'], $allowed_sort)
            && isset($options['order'])
            && in_array($options['order'], $allowed_order)) {
            $sql .= " ORDER BY {$options['sort']} {$options['order']}";
        } else {
            $sql .= ' ORDER BY created DESC';
        }

        if (!empty($options['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $options['limit']));
        }

        if (empty($options['count'])) {
            $fetch_options = array('index' => 'credential_id', 'unserialize' => 'data');
            $result = $this->db->fetchAll($sql, $conditions, $fetch_options);
        } else {
            $result = (int) $this->db->fetchColumn($sql, $conditions);
        }

        return $result;
    }

    /**
     * Adds a credential
     * @param array $data
     * @return int
     */
    public function add(array $data)
    {
        $data['created'] = $data['modified'] = GC_TIME;
        return $this->db->insert('module_gapi_credential', $data);
    }

    /**
     * Deletes a credential
     * @param integer $id
     * @return bool
     */
    public function delete($id)
    {
        return (bool) $this->db->delete('module_gapi_credential', array('credential_id' => $id));
    }

    /**
     * Delete "Service" credential
     * @param int
     * @return bool
     */
    public function deleteService($id)
    {
        $data = $this->get($id);

        if (empty($data)) {
            return false;
        }

        if (!$this->delete($id)) {
            return false;
        }

        $file = gplcart_file_absolute($data['data']['file']);

        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    /**
     * Updates a credential
     * @param integer $id
     * @param array $data
     * @return boolean
     */
    public function update($id, array $data)
    {
        $data['modified'] = GC_TIME;
        return (bool) $this->db->update('module_gapi_credential', $data, array('credential_id' => $id));
    }

    /**
     * Returns an array of credential handlers
     * @return array
     */
    public function getHandlers()
    {
        $handlers = array(
            'key' => array(
                'name' => $this->translation->text('API key'),
                'handlers' => array(
                    'delete' => array(__CLASS__, 'delete'),
                    'edit' => array('gplcart\\modules\\gapi\\controllers\\Credential', 'editKeyCredential')
                )
            ),
            'oauth' => array(
                'name' => $this->translation->text('OAuth client ID'),
                'handlers' => array(
                    'delete' => array(__CLASS__, 'delete'),
                    'edit' => array('gplcart\\modules\\gapi\\controllers\\Credential', 'editOauthCredential')
                )
            ),
            'service' => array(
                'name' => $this->translation->text('Service account key'),
                'handlers' => array(
                    'delete' => array(__CLASS__, 'deleteService'),
                    'edit' => array('gplcart\\modules\\gapi\\controllers\\Credential', 'editServiceCredential')
                )
            )
        );

        $this->hook->attach('module.gapi.credential.handlers', $handlers);
        return $handlers;
    }

    /**
     * Call a credential handler
     * @param string $handler_id
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function callHandler($handler_id, $name, array $arguments = array())
    {
        return Handler::call($this->getHandlers(), $handler_id, $name, $arguments);
    }
}
