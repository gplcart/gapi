<?php

/**
 * @package Backup
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\modules\gapi\controllers;

use Exception;
use gplcart\core\models\FileTransfer as FileTransferModel;
use gplcart\modules\gapi\models\Credential as ModuleGapiCredentialModel;
use gplcart\core\controllers\backend\Controller as BackendController;

/**
 * Handles incoming requests and outputs data related to Google API credentials
 */
class Credential extends BackendController
{

    /**
     * Credential model class instance
     * @var \gplcart\modules\gapi\models\Credential $credential
     */
    protected $credential;

    /**
     * File transfer module instance
     * @var \gplcart\core\models\FileTransfer $file_transfer
     */
    protected $file_transfer;

    /**
     * Pager limit
     * @var array
     */
    protected $data_limit;

    /**
     * The current updating credential
     * @var array
     */
    protected $data_credential = array();

    /**
     * Credential type
     * @var string
     */
    protected $data_type;

    /**
     * @param ModuleGapiCredentialModel $credential
     * @param FileTransferModel $file_transfer
     */
    public function __construct(ModuleGapiCredentialModel $credential, FileTransferModel $file_transfer)
    {
        parent::__construct();

        $this->credential = $credential;
        $this->file_transfer = $file_transfer;
    }

    /**
     * Route page callback
     * Displays the add credential page
     * @param string $type
     */
    public function addCredential($type)
    {
        $this->data_type = $type;

        $this->setTitleEditCredential();
        $this->setBreadcrumbEditCredential();

        $this->credential->callHandler($type, 'edit');
    }

    /**
     * Handler callback
     * Edit "API key" credential page
     */
    public function editKeyCredential()
    {
        if ($this->validateKeyCredential()) {
            $this->saveCredential();
        }

        $this->output('gapi|credential/edit/key');
    }

    /**
     * Handler callback
     * Edit "OAuth" credential page
     */
    public function editOauthCredential()
    {
        if ($this->validateOauthCredential()) {
            $this->saveCredential();
        }

        $this->output('gapi|credential/edit/oauth');
    }

    /**
     * Handler callback
     * Edit "Service" credential page
     */
    public function editServiceCredential()
    {
        if ($this->validateServiceCredential()) {
            $this->saveCredential();
        }

        $this->output('gapi|credential/edit/service');
    }

    /**
     * Route page callback
     * Displays the edit credential page
     * @param int $credential_id
     */
    public function editCredential($credential_id)
    {
        $this->setCredential($credential_id);

        $this->setTitleEditCredential();
        $this->setBreadcrumbEditCredential();

        $this->setData('credential', $this->data_credential);

        $this->submitDeleteCredential();
        $this->credential->callHandler($this->data_credential['type'], 'edit');
    }

    /**
     * Sets the credential data
     * @param $credential_id
     */
    protected function setCredential($credential_id)
    {
        if (is_numeric($credential_id)) {
            $this->data_credential = $this->credential->get($credential_id);
            if (empty($this->data_credential)) {
                $this->outputHttpStatus(404);
            }
        }
    }

    /**
     * Set titles on the edit credential page
     */
    protected function setTitleEditCredential()
    {
        if (isset($this->data_credential['name'])) {
            $text = $this->text('Edit %name', array('%name' => $this->data_credential['name']));
        } else {
            $text = $this->text('Add credential');
        }

        $this->setTitle($text);
    }

    /**
     * Set breadcrumbs on the edit credential page
     */
    protected function setBreadcrumbEditCredential()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $breadcrumbs[] = array(
            'text' => $this->text('Google API credentials'),
            'url' => $this->url('admin/report/gapi')
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Validates "API key" credential data
     * @return bool
     */
    protected function validateKeyCredential()
    {
        if (!$this->isPosted('save')) {
            return false;
        }

        $this->setSubmitted('credential');
        $this->validateElement(array('name' => $this->text('Name')), 'length', array(1, 255));
        $this->validateElement(array('data.key' => $this->text('Key')), 'length', array(1, 255));

        return !$this->hasErrors();
    }

    /**
     * Validates "OAuth" credential data
     * @return bool
     */
    protected function validateOauthCredential()
    {
        if (!$this->isPosted('save')) {
            return false;
        }

        $this->setSubmitted('credential');

        $this->validateElement(array('name' => $this->text('Name')), 'length', array(1, 255));
        $this->validateElement(array('data.id' => $this->text('Client ID')), 'length', array(1, 255));
        $this->validateElement(array('data.secret' => $this->text('Client secret')), 'length', array(1, 255));

        return !$this->hasErrors();
    }

    /**
     * Validates "Service" credential data
     * @return bool
     */
    protected function validateServiceCredential()
    {
        if (!$this->isPosted('save')) {
            return false;
        }

        $this->setSubmitted('credential');
        $this->validateElement(array('name' => $this->text('Name')), 'length', array(1, 255));

        $this->validateFileUploadCredential();

        return !$this->hasErrors();
    }

    /**
     * Validates JSON file upload
     * @return bool
     */
    protected function validateFileUploadCredential()
    {
        $upload = $this->request->file('file');

        if (empty($upload)) {

            if (!isset($this->data_credential['credential_id'])) {
                $this->setError('data.file', $this->text('File is required'));
                return false;
            }

            return null;
        }

        if ($this->isError()) {
            return null;
        }

        $result = $this->file_transfer->upload($upload, false, gplcart_file_private_module('gapi'));

        if ($result !== true) {
            $this->setError('data.file', $result);
            return false;
        }

        $file = $this->file_transfer->getTransferred();
        $decoded = json_decode(file_get_contents($file), true);

        if (empty($decoded['private_key'])) {
            unlink($file);
            $this->setError('data.file', $this->text('Failed to read JSON file'));
            return false;
        }

        if (isset($this->data_credential['data']['file'])) {
            $existing = gplcart_file_absolute($this->data_credential['data']['file']);
            if (file_exists($existing)) {
                unlink($existing);
            }
        }

        $this->setSubmitted('data.file', gplcart_file_relative($file));
        return true;
    }

    /**
     * Saves the submitted credential data
     */
    protected function saveCredential()
    {
        $submitted = $this->getSubmitted();

        // Update
        if (isset($this->data_credential['credential_id'])) {
            $this->controlAccess('module_gapi_credential_edit');
            if ($this->credential->update($this->data_credential['credential_id'], $submitted)) {
                $this->redirect('admin/report/gapi', $this->text('Credential has been updated'), 'success');
            }
            $this->redirect('', $this->text('Credential has not been updated'), 'warning');
        }

        // Add
        $this->controlAccess('module_gapi_credential_add');
        $submitted['type'] = $this->data_type;
        if ($this->credential->add($submitted)) {
            $this->redirect('admin/report/gapi', $this->text('Credential has been added'), 'success');
        }

        $this->redirect('', $this->text('Credential has not been added'), 'warning');
    }

    /**
     * Delete a submitted credential
     */
    protected function submitDeleteCredential()
    {
        if ($this->isPosted('delete') && isset($this->data_credential['credential_id'])) {
            $this->controlAccess('module_gapi_credential_delete');
            if ($this->credential->callHandler($this->data_credential['type'], 'delete', array($this->data_credential['credential_id']))) {
                $this->redirect('admin/report/gapi', $this->text('Credential has been deleted'), 'success');
            }
            $this->redirect('', $this->text('Credential has not been deleted'), 'warning');
        }
    }

    /**
     * Route callback
     * Displays the credential overview page
     */
    public function listCredential()
    {
        $this->actionListCredential();
        $this->setTitleListCredential();
        $this->setBreadcrumbListCredential();

        $this->setFilterListCredential();
        $this->setPagerListCredential();

        $this->setData('credentials', $this->getListCredential());
        $this->setData('handlers', $this->credential->getHandlers());
        $this->outputListCredential();
    }

    /**
     * Applies an action to the selected credentials
     */
    protected function actionListCredential()
    {
        list($selected, $action) = $this->getPostedAction();

        $deleted = 0;

        foreach ($selected as $id) {
            if ($action === 'delete' && $this->access('module_gapi_credential_delete')) {
                $credential = $this->credential->get($id);
                $deleted += (int) $this->credential->callHandler($credential['type'], 'delete', array($credential['credential_id']));
            }
        }

        if ($deleted > 0) {
            $message = $this->text('Deleted %num item(s)', array('%num' => $deleted));
            $this->setMessage($message, 'success');
        }
    }

    /**
     * Sets filter parameters
     */
    protected function setFilterListCredential()
    {
        $this->setFilter(array('created', 'name', 'credential_id', 'type'));
    }

    /**
     * Sets pager
     * @return array
     */
    protected function setPagerListCredential()
    {
        $options = $this->query_filter;
        $options['count'] = true;

        $pager = array(
            'query' => $this->query_filter,
            'total' => (int) $this->credential->getList($options)
        );

        return $this->data_limit = $this->setPager($pager);
    }

    /**
     * Returns an array of credentials
     * @return array
     */
    protected function getListCredential()
    {
        $options = $this->query_filter;
        $options['limit'] = $this->data_limit;

        return $this->credential->getList($options);
    }

    /**
     * Sets title on the credential overview page
     */
    protected function setTitleListCredential()
    {
        $this->setTitle($this->text('Google API credentials'));
    }

    /**
     * Sets breadcrumbs on the credential overview page
     */
    protected function setBreadcrumbListCredential()
    {
        $breadcrumb = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $this->setBreadcrumb($breadcrumb);
    }

    /**
     * Render and output the credential overview page
     */
    protected function outputListCredential()
    {
        $this->output('gapi|credential/list');
    }

}
