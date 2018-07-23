<?php
/**
 * Zont
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.1 (wizard, 13:07:39 [Jul 13, 2018])
 */
//
//
class zontdevices extends module
{

    var $modbus;

    /**
     * zontdevices
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "zontdevices";
        $this->title = "Zont";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
        $this->getConfig();
        require(DIR_MODULES . $this->name . '/structure.inc.php');
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (IsSet($this->id)) {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->data_source)) {
            $p["data_source"] = $this->data_source;
        }
        if (IsSet($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $data_source;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($data_source)) {
            $this->data_source = $data_source;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['DATA_SOURCE'] = $this->data_source;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        $out['API_USERNAME'] = $this->config['API_USERNAME'];
        $out['API_PASSWORD'] = $this->config['API_PASSWORD'];
        $out['API_POLL'] = (int)$this->config['API_POLL'];
        if ($this->view_mode == 'update_settings') {
            $this->config['API_USERNAME'] = gr('api_username');
            $this->config['API_PASSWORD'] = gr('api_password');
            $this->config['API_POLL'] = gr('api_poll','int');
            $this->saveConfig();
            $this->refreshDevices();
            setGlobal('cycle_zontdevicesControl', 'restart');
            $this->redirect("?");
        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'zontdevices' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_zontdevices') {
                $this->search_zontdevices($out);
            }
            if ($this->view_mode == 'refresh_zontdevices') {
                $this->refreshDevices();
                $this->redirect("?");
            }
            if ($this->view_mode == 'edit_zontdevices') {
                $this->edit_zontdevices($out, $this->id);
            }
            if ($this->view_mode == 'delete_zontdevices') {
                $this->delete_zontdevices($this->id);
                $this->redirect("?data_source=zontdevices");
            }
        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'zontcommands') {
            if ($this->view_mode == '' || $this->view_mode == 'search_zontcommands') {
                $this->search_zontcommands($out);
            }
            if ($this->view_mode == 'edit_zontcommands') {
                $this->edit_zontcommands($out, $this->id);
            }
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    /**
     * zontdevices search
     *
     * @access public
     */
    function search_zontdevices(&$out)
    {
        require(DIR_MODULES . $this->name . '/zontdevices_search.inc.php');
    }

    /**
     * zontdevices edit/add
     *
     * @access public
     */
    function edit_zontdevices(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/zontdevices_edit.inc.php');
    }

    /**
     * zontdevices delete record
     *
     * @access public
     */
    function delete_zontdevices($id)
    {
        $rec = SQLSelectOne("SELECT * FROM zontdevices WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM zontdevices WHERE ID='" . $rec['ID'] . "'");
    }

    /**
     * zontcommands search
     *
     * @access public
     */
    function search_zontcommands(&$out)
    {
        require(DIR_MODULES . $this->name . '/zontcommands_search.inc.php');
    }

    /**
     * zontcommands edit/add
     *
     * @access public
     */
    function edit_zontcommands(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/zontcommands_edit.inc.php');
    }

    function propertySetHandle($object, $property, $value)
    {
        $this->getConfig();
        $properties = SQLSelect("SELECT * FROM zontcommands WHERE LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
        $total = count($properties);
        if ($total) {
            for ($i = 0; $i < $total; $i++) {
                //to-do
                $device_record=SQLSelectOne("SELECT * FROM zontdevices WHERE ID=".$properties[$i]['DEVICE_ID']);
                $this->writeDeviceCommand($device_record,$properties[$i],$value);
            }
        }
    }


    function requestZontAPI($command,$data=0) {

        $username=$this->config['API_USERNAME'];
        $password=$this->config['API_PASSWORD'];

        $ch = curl_init('https://zont-online.ru'.$command);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        if (is_array($data)) {
            $data_string = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        } else {
            $data_string='';
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     // bad style, I know...
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-ZONT-Client: '.$username,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        $result = curl_exec($ch);

        if (curl_errno($ch) && !$background) {
            //$errorInfo = curl_error($ch);
            $info = curl_getinfo($ch);
            dprint($info,false);
        }
        curl_close($ch);
        $res=json_decode($result,true);
        return $res;

    }


    function processDeviceData($data) {
        $device_rec=SQLSelectOne("SELECT * FROM zontdevices WHERE SERIAL_ID='".DBSafe($data['id'])."'");
        if (!$device_rec['ID']) {
            $device_rec['SERIAL_ID']=$data['id'];
            $device_rec['DEVICE_TYPE']=$data['device_type']['code'];
            $device_rec['TITLE']=$data['device_type']['name'];
            $device_rec['ID']=SQLInsert('zontdevices',$device_rec);
        }

        $commands=array();
        $has=array();
        foreach($data['capabilities'] as $feature) {
            $has[$feature]=1;
        }
        if ($has['has_gsm_balance'] && is_array($data['balance'])) {
            $command=array();
            $command['SYSTEM']='gsm_balance';
            $command['VALUE']=$data['balance']['value'];
            $commands[]=$command;
        }
        if ($has['has_multiple_thermometers'] && is_array($data['thermometers'])) {
            foreach($data['thermometers'] as $term) {
                $command=array();
                $serial=str_replace('%','',$term['serial']);
                $command['SYSTEM']=$serial.'_value';
                $command['TITLE']=$term['name'].', T';
                $command['VALUE']=$term['last_value'];
                $command['VALUE_TYPE']='temperature';
                if ($term['last_value_time']!='') {
                    $command['UPDATED']=date('Y-m-d H:i:s',$term['last_value_time']);
                }
                $commands[]=$command;
            }
        }
        if ($has['has_thermostat']) {
            if (isset($data['thermostat_mode'])) {
                $command=array();
                $command['SYSTEM']='thermostat_mode';
                $command['VALUE']=$data['thermostat_mode'];
                $commands[]=$command;
            }
            if (isset($data['thermostat_ext_mode'])) {
                $command=array();
                $command['SYSTEM']='thermostat_ext_mode';
                $command['VALUE']=$data['thermostat_ext_mode'];
                if (is_array($data['thermostat_ext_modes_config'])) {
                    $total_modes=count($data['thermostat_ext_modes_config']);
                    for($im=0;$im<$total_modes;$im++) {
                        if (!$data['thermostat_ext_modes_config'][$im]['name']) continue;
                        $command['COMMENTS'].=$im.' = '.$data['thermostat_ext_modes_config'][$im]['name'].'; ';
                    }
                }
                $commands[]=$command;
            }
        }
        if (is_array($data['rf_status'])) {
            foreach($data['rf_status'] as $k=>$v) {
                $serial=preg_replace('/^s_/','',$k);
                $serial=str_replace('%','',$serial);
                if (isset($v['bat_v'])) {
                    $command=array();
                    $command['SYSTEM']=$serial.'_rf_batt_v';
                    $command['TITLE']=$v['name'].', V';
                    $command['VALUE']=$v['bat_v'];
                    $commands[]=$command;
                }
                if (isset($v['dbm'])) {
                    $command=array();
                    $command['SYSTEM']=$serial.'_rf_dbm';
                    $command['TITLE']=$v['name'].', Dbm';
                    $command['VALUE']=$v['dbm'];
                    $commands[]=$command;
                }
            }
        }

        $command=array();
        $command['SYSTEM']='online';
        $command['TITLE']='Online';
        //$command['UPDATED']=date('Y-m-d H:i:s');
        if ($data['online']) {
            $command['VALUE']=1;
        } else {
            $command['VALUE']=0;
        }
        $commands[]=$command;

        foreach($commands as &$command) {
            if (!$command['SYSTEM']) continue;
            if (!$command['TITLE']) {
                $command['TITLE']=$command['SYSTEM'];
            }
            $command_rec=SQLSelectOne("SELECT * FROM zontcommands WHERE SYSTEM='".DBSafe($command['SYSTEM'])."' AND DEVICE_ID=".$device_rec['ID']);
            if (!$command['UPDATED'] && $command_rec['VALUE']!=$command['VALUE']) {
                $command['UPDATED']=date('Y-m-d H:i:s');
            }
            foreach($command as $k=>$v) {
                $command_rec[$k]=$v;
            }
            if (!$command_rec['ID']) {
                $command_rec['DEVICE_ID']=$device_rec['ID'];
                $command_rec['ID']=SQLInsert('zontcommands',$command_rec);
            } else {
                SQLUpdate('zontcommands',$command_rec);
            }

            if ($command_rec['LINKED_OBJECT'] && $command_rec['LINKED_PROPERTY']) {
                setGlobal($command_rec['LINKED_OBJECT'].'.'.$command_rec['LINKED_PROPERTY'], $command_rec['VALUE'], array($this->name=>'0')); //
            }
            if ($command_rec['LINKED_OBJECT'] && $command_rec['LINKED_METHOD']) {
                $params=array();
                $params['VALUE']=$command_rec['VALUE'];
                callMethod($command_rec['LINKED_OBJECT'].'.'.$command_rec['LINKED_METHOD'], $params);
            }

        }
        //dprint($commands);
        //dprint($data);
    }

    function refreshDevices() {
        $data=$this->requestZontAPI('/api/devices');
        if ($_GET['raw']) {
            dprint($data);
        }

        if (is_array($data['devices'])) {
            $total = count($data['devices']);
            for($i=0;$i<$total;$i++) {
                $this->processDeviceData($data['devices'][$i]);
            }
        }
    }

    function writeDeviceCommand($device_rec, $command_rec, $value)
    {
        if ($command_rec['SYSTEM']=='thermostat_ext_mode') {
            $data=array();
            $data['device_id']=$device_rec['SERIAL_ID'];
            $data['thermostat_ext_mode']=$value;
            $this->requestZontAPI('/api/update_device',$data);
        }
    }


    function processCycle()
    {
        $this->getConfig();
        $latest_iteration=(int)$this->cycle_time;
        if ((time()-$latest_iteration)>$this->config['API_POLL']) {
            $this->cycle_time=time();
            echo date('H:i:s')." Refreshing devices\n";
            $this->refreshDevices();
        }
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        parent::install();
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        SQLExec('DROP TABLE IF EXISTS zontdevices');
        SQLExec('DROP TABLE IF EXISTS zontcommands');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        zontdevices -
        zontcommands -
        */
        $data = <<<EOD
 zontdevices: ID int(10) unsigned NOT NULL auto_increment
 zontdevices: TITLE varchar(100) NOT NULL DEFAULT ''
 zontdevices: DEVICE_TYPE varchar(255) NOT NULL DEFAULT '' 
 zontdevices: SERIAL_ID varchar(255) NOT NULL DEFAULT ''
 
 zontcommands: ID int(10) unsigned NOT NULL auto_increment
 zontcommands: SYSTEM varchar(100) NOT NULL DEFAULT ''
 zontcommands: TITLE varchar(100) NOT NULL DEFAULT ''
 zontcommands: VALUE varchar(255) NOT NULL DEFAULT ''
 zontcommands: VALUE_TYPE varchar(255) NOT NULL DEFAULT '' 
 zontcommands: VALUE_RAW varchar(255) NOT NULL DEFAULT ''
 zontcommands: COMMENTS varchar(255) NOT NULL DEFAULT '' 
 zontcommands: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 zontcommands: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 zontcommands: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 zontcommands: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 zontcommands: UPDATED datetime
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVsIDEzLCAyMDE4IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
