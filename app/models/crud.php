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

        if ($tableName) {
            if (strpos($tableName, '.') !== false) {
                preg_match('/^(.*)\.(.*)$/', $tableName, $m);
                $this->_tableSchema = $m[1];
                $this->_tableName = $m[2];
            } else {
                $this->_tableName = $tableName;
            }
        }
        $this->_tableInfo = $this->table();

        $this->_tableInfo = $this->info();

        $this->_primary = isset($this->_primary) ? $this->_primary : current($this->_tableInfo['primary']);
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
    public function table()
    {
        if (!$this->_table) {
            $this->_table = DB::table($this->_tableName);
        }
        return $this->_table;
    }

    public function info(){

        $this->connection();

        $result = mysql_query("SHOW COLUMNS FROM ". $this->_tableName." ");

//        $result->execute();
//        $rows = $result->result_metadata();
//
//        var_dump($rows->fetch_fields());

        if (!$result) {
            echo 'Could not run query: ' . mysql_error();
            exit;
        }
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {

                $info = $row;
            }
        }

        echo "<pre>";
        var_dump($info);exit;
        echo "</pre>";

        return $info;
    }

    /**
     * Mengembalikan object form untuk CRUD object ini
     * Override method ini kalau mau pakai form lain
     * @return Zend_Form
     */
    public function form()
    {
        $f = new Form();
        $f->setMethod('post')
            ->setDecorators(array(
                array('ViewScript', array('viewScript' => 'partials/form-crud.phtml'))
            ));
        // Generate form element berdasarkan $this->_tableInfo
        foreach ($this->_tableInfo['metadata'] as $column => $colInfo) {
            // primary key tidak dibuatkan form element
            if (!$colInfo['PRIMARY'] && !in_array($column, $this->_ignoreCols)) {

                //edited by irfan.muslim@sangkuriang.co.id
                //pengecualian element form
                if (isset($this->_exceptForm[$column]))
                {
                    $setting = $this->_exceptForm[$column];
                    $tampil = (isset($setting['tampil'])) ? $setting['tampil'] : true;

                    //salah satunya option-nya: tampil atau tidak?
                    if ($tampil){

                        $f->addElement($setting['formtype'],$column, array(
                                'label' => (isset($setting['label'])) ? $setting['label'] : $this->fieldToName($column),
                                'required' => (isset($setting['required'])) ? $setting['required'] : !$colInfo['NULLABLE'],
                                'multiple' => $setting['ismultiple'],
                                'multiOptions' => $setting['options'],
                                'validators' => $setting['validator'],
                                'class'	=> $setting['class'],
                                'filters' => $setting['filter'],
                            )
                        );
                    }
                }
                //end edited

                // langsung mengenali foreign key dan membuat element select
                elseif (isset($this->_foreignKeys[$column])) {
                    $setting = $this->_foreignKeys[$column];
                    $f->addElement('select', $column, array(
                        'label' => (isset($setting['label'])) ? $setting['label'] : $this->fieldToName($column),
                        'required' => !$colInfo['NULLABLE'],
                        'multiple' => false,
                        'multiOptions' => $this->_generateOptions($setting),
                        'validators' => array(
                            array('Db_RecordExists', false, array(
                                'schema' => $setting['schema'],
                                'table' => $setting['table'],
                                'field' => $setting['field'],
                            )),
                        ),
                    ));
                } else switch ($colInfo['DATA_TYPE']) {
                    case 'int4':
                    case 'int8':
                        $f->addElement('text', $column, array(
                            'label' => $this->fieldToName($column),
                            'required' => !$colInfo['NULLABLE'],
                            'filters' => array('Int'),
                            'validators' => array(
                            ),
                        ));
                        break;
                    case 'float4':
                    case 'float8':
                        $f->addElement('text', $column, array(
                            'label' => $this->fieldToName($column),
                            'required' => !$colInfo['NULLABLE'],
                            'filters' => array('StringTrim'),
                            'validators' => array(
                                array('Float'),
                            ),
                        ));
                        break;
                    case 'bool':
                        $f->addElement('checkbox', $column, array(
                            'label' => $this->fieldToName($column),
                            'required' => !$colInfo['NULLABLE'],
                        ));
                        break;
                    case 'varchar':
                        $f->addElement('text', $column, array(
                            'label' => $this->fieldToName($column),
                            'required' => !$colInfo['NULLABLE'],
                            'filters' => array('StringTrim'),
                            'validators' => array(
                                array('StringLength', false, array('min'=>0, 'max'=>$colInfo['LENGTH'])),
                            ),
                        ));
                        break;
                    case 'text':
                        $f->addElement('textarea', $column, array(
                            'label' => $this->fieldToName($column),
                            'required' => !$colInfo['NULLABLE'],
                            'filters' => array('StringTrim'),
                        ));
                        break;
                    default:
                        $f->addElement('text', $column, array(
                            'label' => $this->fieldToName($column),
                            'required' => !$colInfo['NULLABLE'],
                            'filters' => array('StringTrim'),
                        ));
                        break;
                }
            }
        }
        // terakhir ditambahkan tombol submit
        $f->addElement('submit', 'submit', array(
            'ignore' => true,
            'label'  => 'Simpan',
        ));
        return $f;
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
}