<?php
/**
 * Model Dasar CRUD
 *
 * Bisa generate secara otomatis Form yang digunakan
 * Batasan dari model ini:
- table harus punya 1 kolom yang jadi primary key
- semua kolom akan dijadikan field form (kalau pakai default form)
- tidak mengenali foreign key (hanya akan jadi form field text biasa)
 *
 * @author Taofik Ridwan
 * Date: 4/24/14
 * Time: 10:52 AM
 */

class Cms_Model_Crud
{
    protected $_id;
    protected $_values; // array associative menyimpan data pada baris database

    protected $_primary = null;
    protected $_tableSchema = null;
    protected $_tableName = '';
    protected $_table = null;
    protected $_tableInfo = array(); // schema,name,cols,primary,metadata

    protected $_ignoreCols = array(); // nama kolom yg di dalam sini tidak akan dimasukkan form
    protected $_foreignKeys = array(); // kolom yg masuk di sini akan dibuatkan element select
    protected $_exceptForm = array(); // kolom yg masuk di sini akan dibuatkan element form sendiri

    public function connection(){
        $link = mysql_connect('localhost', 'root', '');
        if (!$link) {
            die('Not connected : ' . mysql_error());
        }

        // seleksi database
        $db_selected = mysql_select_db('hukor', $link);
        if (!$db_selected) {
            die ('Can\'t use foo : ' . mysql_error());
        }
    }

    public function __construct($tableName = null, $id = null)
    {

        //memanggil koneksi database
        $this->connection();

//        if ($tableName) {
//            if (strpos($tableName, '.') !== false) {
//                preg_match('/^(.*)\.(.*)$/', $tableName, $m);
//                $this->_tableSchema = $m[1];
//                $this->_tableName = $m[2];
//            } else {
//                $this->_tableName = $tableName;
//            }
//        }

//        $this->_tableInfo = $this->table();

        $this->_tableInfo = $this->info();

//        $meta = array($this->_tableInfo);
//
//        var_dump($e);exit;

        $this->_primary = isset($this->_primary) ? $this->_primary : current($this->_tableInfo->primary_key);

        if (isset($id)) {
            // coba cari
            $table = $this->table();
            $rowset = $table->find($id);

            if (count($rowset) > 0) {

                $this->_id = $id;
                $this->setFromRow($rowset->current());
            } else {
                $this->_id = null;
            }
        } else {
            $this->_id = null;
        }
    }

    /**
     * Mengembalikan object dbtable utama
     * @return Zend_Db_Table
     */
//    public function table()
//    {
//        if (!$this->_table) {
//            $this->_table = DB::table($this->_tableName);
//        }
//        return $this->_table;
//    }

    public function info(){

        $result = mysql_query("SELECT * FROM ". $this->_tableName." ");

        if (!$result) {
            echo 'Could not run query: ' . mysql_error();
            exit;
        }

        $i = 0;
        while ($i < mysql_num_fields($result)) {
//            echo "Information for column $i:<br />\n";
            $meta = mysql_fetch_field($result, $i);
            if (!$meta) {
                echo "No information available<br />\n";
            }

            return $meta;
            $i++;
        }

//        mysql_free_result($result);
//        while ($i < mysql_num_fields($result)) {
//            $meta = mysql_fetch_field($result, $i);
//            if (!$meta) {
//                echo "No information available<br />\n";
//            }
////            var_dump($meta);exit;
////            return $meta;
//
//            $a = array($meta);
//
//            $i++;
//        }
//
//
//        var_dump($result);exit;
    }

    /**
     * Mengembalikan object form untuk CRUD object ini
     * Override method ini kalau mau pakai form lain
     * @return Zend_Form
     */
    public function form()
    {
//        $f = new Form();
//        $f->setMethod('post')
//            ->setDecorators(array(
//                array('ViewScript', array('viewScript' => 'partials/form-crud.phtml'))
//            ));
        // Generate form element berdasarkan $this->_tableInfo

        $p = array($this->_tableInfo);

        foreach ($p as $column => $colInfo) {
            var_dump($colInfo);exit;
                switch ($colInfo->type) {
                    case 'int4':
                    case 'int8':
                    case 'int':
//                    echo "integer";exit;
                        $label_name = $this->fieldToName($colInfo->name);
                        $f = $this->type_text($label_name);
                        break;
                    case 'float4':
                    case 'float8':
                        $label_name = $this->fieldToName($colInfo->name);
                        $f = $this->type_text($label_name);
                        break;
                    case 'varchar':
                    case 'blob':
//                        echo "varchar";exit;
                        $label_name = $this->fieldToName($colInfo->name);
                        $f = $this->type_text($label_name);
                        break;
                    case 'text':
                        $label_name = $this->fieldToName($colInfo->name);
                        $f = $this->type_textarea($label_name);
                        break;
                    default:
//                        echo 'default';exit;
                        $label_name = $this->fieldToName($colInfo->name);
                        $f = $this->type_text($label_name);
                        break;
                }
            return $f;
        }

        // terakhir ditambahkan tombol submit
//        $f = $this->submit_button("submit");


    }

    /**
     *
     * @param array $setting salah satu settingan foreign key
     */
    protected function _generateOptions($setting)
    {
        $db = $this->table()->getAdapter();
        $select = $db->select()
            ->from("{$setting['schema']}.{$setting['table']}",
                array($setting['field'], $setting['display']))
            ->order($setting['display'].' ASC')
        ;
        $raw = $db->fetchAll($select);
        $result = array('' => '-');
        foreach ($raw as $row) {
            $result[$row[$setting['field']]] = $row[$setting['display']];
        }
        return $result;
    }

    /**
     * Mengembalikan object datatables untuk berkomunikasi berhubungan dengan benda ini
     * Override method ini kalau mau pakai datatables spesifik
     * @return App_Datatables
     */
    public function datatables()
    {
        // @TODO
    }

    /**
     * Convert string seperti "sonar_name" menjadi "Sonar Name"
     */
    public function fieldToName($field)
    {
        // di lowercase, ganti '_' jadi ' ', uppercase huruf awal tiap kata
        return ucwords(str_replace('_', ' ', strtolower($field)));
    }

    /**
     * Apakah object ini ada di database?
     * @return bool
     */
    public function exists()
    {
        return $this->_id !== null;
    }

    // ================ GETTERS ==================
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Mengembalikan nilai satu kolom
     * @return null|mixed
     */
    public function get($col)
    {
        return isset($this->_values[$col]) ? $this->_values[$col] : null;
    }

    /**
     * Memetakan input field milik form ke property sendiri
     * Konvensi: Nama field milik form harus sama dengan nama field milik table
     * @param Zend_Form|array $form
     */
    public function setFromForm($form)
    {

        if (is_a($form, 'Zend_Form')) {
            $values = $form->getValues();
            foreach ($values as $name => $val) {
                if ($val === '' && !$form->getElement($name)->isRequired()) {
                    unset($values[$name]);
                }
            }
            $form = $values;
        }
        $this->_values = $form;
//        print_r( $this->_values );
//        exit;
    }

    /**
     * Kembalikan array dengan nama key sesuai nama input field form
     * Konvensi: Nama field milik form harus sama dengan nama field milik table
     * @return array
     */
    public function toFormArray()
    {
        return $this->_values;
    }

    /**
     * Memetakan kolom-kolom dari row database ke property
     * Konvensi: Nama field milik form harus sama dengan nama field milik table
     * @param Zend_Db_Table_Row|array $row
     */
    public function setFromRow($row)
    {
        if (is_a($row, 'Zend_Db_Table_Row')) {
            $row = $row->toArray();
        }
        if (isset($row[$this->_primary])) {
            $this->_id = $row[$this->_primary];
        }
        unset($row[$this->_primary]);
        $this->_values = $row;
    }

    /**
     * Kembalikan array dengan nama key sesuai nama kolom
     * Konvensi: Nama field milik form harus sama dengan nama field milik table
     * @return array
     */
    public function toRowArray($withId = false)
    {
        $row = $this->_values;
        if ($withId) {
            $row[$this->_primary] = $this->_id;
        }
        return array_intersect_key($row, $this->_tableInfo['metadata']);
    }

    /**
     * Mengembalikan kondisi where untuk object ini
     */
    public function where()
    {
        $db = $this->table()->getAdapter();
        return $db->quoteInto($db->quoteIdentifier($this->_primary) . " = ?", $this->_id);
    }

    /**
     * Simpan penambahan/perubahan ke database
     */
    public function save()
    {
        $table = $this->table();
        if ($this->exists()) {
            $table->update($this->toRowArray(), $this->where());
        } else {
            $table->insert($this->toRowArray());
            $this->_id = $table->getAdapter()->lastInsertId();
        }
    }

    /**
     * Hapus object ini
     */
    public function delete()
    {
        if (!$this->exists()) return;
        $table = $this->table();
        $table->delete($this->where());
        $this->_id = null;
    }

    /**
     * Kembalikan array dengan nama key sesuai nama kolom
     * Konvensi: Nama field milik form harus sama dengan nama field milik table
     * @return array
     * @author irfan.muslim@sangkuriang.co.id
     */

    public function toViewArray()
    {
        $result = array();
        foreach ($this->_values as $key => $val)
        {
            $name = $this->fieldToName($key);
            $tampil = true;
            if (isset($this->_exceptForm[$key])){
                $setting = $this->_exceptForm[$key];
                $tampil = (isset($setting['tampil'])) ? $setting['tampil'] : true;
                $name = (isset($setting['label'])) ? $this->fieldToName($setting['label']):$this->fieldToName($key);
            }

            if (isset($this->_foreignKeys[$key]))
            {
                $setting = $this->_foreignKeys[$key];
                if ($val)
                {
                    $db = $this->table()->getAdapter();
                    $select = $db->select()
                        ->from("{$setting['schema']}.{$setting['table']}",
                            array($setting['field'], $setting['display']))
                        ->where('"'.$setting['field'].'" = ?',$val)
                    ;

                    $row = $db->fetchRow($select);

                    if ($row != NULL)
                    {
                        $val = $row[$setting['display']];
                    }
                }
                $name = $this->fieldToName($setting['label']);
            }

            if ($tampil){
                $result[$name] = $val;
            }
        }

        return $result;
    }

    public function type_text($name){

        $input = "<label>". $name ."</label> : <input type='text' name=". $name .">";

        return $input;
    }

    public function type_textarea($name){

        $input = "<textarea name=". $name ."></textarea>";

        return $input;
    }

    public function submit_button($name){

        $input = "<input type='submit' value=". $name .">";

        return $input;
    }

}