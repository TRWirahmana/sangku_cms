<?php
/**
 * Created by PhpStorm.
 * Menyediakan Method-method yang dipakai untuk CRUD
 * @author: Taofik Ridwan
 * Date: 4/24/14
 * Time: 11:00 AM
 */

class App_CrudController extends BaseController
{

    public function init()
    {
        parent::init();
        if (!isset($this->_redirector)) {
            $this->_redirector = $this->_helper->getHelper('Redirector');
        }
        $alert =  $this->_helper->flashMessenger->getMessages();

        if (count($alert)) {

            $specAlert = explode( "-", $alert[0]);

            if(strtolower($specAlert[0]) == 'error')
            {
                unset($specAlert[0]);
                $this->view->errorAlert = implode( "", $specAlert);
            }
            else
            {
                $this->view->successAlert = $alert[0];
            }
        }
    }

    /**
     * Membantu addAction
     * @param string $tableName kalau pakai Cms_Model_Crud harus dimasukkan nama tabel
     *                kalau pakai class turunan biasanya sudah punya nama table, jadi parameter ini bisa null
     * @param string $listAction kalau selesai menyimpan, redirect ke halaman ini
     * @param string $crudClass kalau mau pakai class selain Cms_Model_Crud
     * @param array $options option untuk redirect
     * @param array $dest destinasi untuk file upload
     */
    protected function _add($tableName, $listAction, $crudClass = 'Cms_Model_Crud', $options = array(), $dest = null)
    {
        $crud = new $crudClass($tableName);
        $form = $crud->form();

        return $form;

    }
}