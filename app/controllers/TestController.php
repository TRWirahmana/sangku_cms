<?php
/**
 * Created by PhpStorm.
 * User: Sangkuriang
 * Date: 4/24/14
 * Time: 10:51 AM
 */

class TestController extends App_CrudController
{

    public function index()
    {

    }

    public function add()
    {

        $this->_add(null, 'index', 'Cms_Berita');

        return View::make('test.test');
    }
}