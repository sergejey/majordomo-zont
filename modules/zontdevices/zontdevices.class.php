<?php
/**
 * Zont
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.2 (PHP 8 compatibility & usability update)
 */
//
//
class zontdevices extends module
{
    /** Base URL of ZONT online API */
    const API_URL = 'https://zont-online.ru';
    /** Default polling period, seconds */
    const DEFAULT_POLL = 60;
    /** Minimal allowed polling period, seconds */
    const MIN_POLL = 10;

    /** @var array supported device types (loaded from structure.inc.php) */
    public $device_types = array();
    /** @var int|string current record id */
    public $id;
    /** @var string module title */
    public $title;
    /** @var string module category */
    public $module_category;
    /** @var int timestamp of the latest polling iteration */
    public $cycle_time = 0;
    /** @var string description of the latest API failure */
    public $last_error = '';

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
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->data_source)) {
            $p["data_source"] = $this->data_source;
        }
        if (isset($this->tab)) {
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
        if (isset($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (isset($this->owner->name)) {
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
        if ($this->view_mode == 'update_settings') {
            $this->config['API_USERNAME'] = gr('api_username');
            $new_password = gr('api_password');
            if ($new_password !== '') {
                // an empty field means "keep the stored password"
                $this->config['API_PASSWORD'] = $new_password;
            }
            $poll = gr('api_poll', 'int');
            if ($poll < self::MIN_POLL) {
                $poll = self::DEFAULT_POLL;
            }
            $this->config['API_POLL'] = $poll;
            $this->saveConfig();
            $this->refreshDevices();
            setGlobal('cycle_zontdevicesControl', 'restart');
            $this->redirect("?");
            return;
        }

        $out['API_USERNAME'] = isset($this->config['API_USERNAME']) ? $this->config['API_USERNAME'] : '';
        $out['API_POLL'] = isset($this->config['API_POLL']) ? (int)$this->config['API_POLL'] : self::DEFAULT_POLL;
        $out['API_PASSWORD_SET'] = (isset($this->config['API_PASSWORD']) && $this->config['API_PASSWORD'] !== '') ? 1 : 0;
        if ($out['API_USERNAME'] === '' || !$out['API_PASSWORD_SET']) {
            $out['NOT_CONFIGURED'] = 1;
        }
        if (!empty($this->config['LAST_ERROR'])) {
            $out['API_ERROR'] = $this->config['LAST_ERROR'];
        }
        $out['MIN_POLL'] = self::MIN_POLL;

        if (isset($this->data_source) && !isset($_GET['data_source']) && !isset($_POST['data_source'])) {
            $out['SET_DATASOURCE'] = 1;
        }

        if ($this->data_source == 'zontdevices' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_zontdevices') {
                $this->search_zontdevices($out);
            }
            if ($this->view_mode == 'refresh_zontdevices') {
                $this->refreshDevices();
                $this->redirect("?");
                return;
            }
            if ($this->view_mode == 'edit_zontdevices') {
                $this->edit_zontdevices($out, $this->id);
            }
            if ($this->view_mode == 'delete_zontdevices') {
                $this->delete_zontdevices($this->id);
                $this->redirect("?data_source=zontdevices");
                return;
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
        $id = (int)$id;
        if (!$id) {
            return;
        }
        $rec = SQLSelectOne("SELECT * FROM zontdevices WHERE ID=" . $id);
        if (!is_array($rec) || empty($rec['ID'])) {
            return;
        }
        // releasing linked properties of the related values
        $commands = SQLSelect("SELECT * FROM zontcommands WHERE DEVICE_ID=" . (int)$rec['ID']);
        foreach ($commands as $command) {
            if (!empty($command['LINKED_OBJECT']) && !empty($command['LINKED_PROPERTY'])) {
                removeLinkedProperty($command['LINKED_OBJECT'], $command['LINKED_PROPERTY'], $this->name);
            }
        }
        SQLExec("DELETE FROM zontcommands WHERE DEVICE_ID=" . (int)$rec['ID']);
        SQLExec("DELETE FROM zontdevices WHERE ID=" . (int)$rec['ID']);
    }

    /**
     * Called by the core when a linked object property is changed somewhere else
     */
    function propertySetHandle($object, $property, $value)
    {
        $this->getConfig();
        $properties = SQLSelect("SELECT * FROM zontcommands WHERE LINKED_OBJECT='" . DBSafe($object) . "' AND LINKED_PROPERTY='" . DBSafe($property) . "'");
        foreach ($properties as $property_rec) {
            $device_record = SQLSelectOne("SELECT * FROM zontdevices WHERE ID=" . (int)$property_rec['DEVICE_ID']);
            if (!is_array($device_record) || empty($device_record['ID'])) {
                continue;
            }
            $this->writeDeviceCommand($device_record, $property_rec, $value);
        }
    }

    /**
     * Performing a request to ZONT online API
     *
     * @param string $command API path, e.g. '/api/devices'
     * @param array|int $data optional request payload
     * @return array|null decoded answer or null on failure
     */
    function requestZontAPI($command, $data = 0)
    {
        $this->last_error = '';

        $username = isset($this->config['API_USERNAME']) ? trim($this->config['API_USERNAME']) : '';
        $password = isset($this->config['API_PASSWORD']) ? $this->config['API_PASSWORD'] : '';

        if ($username === '' || $password === '') {
            $this->last_error = 'ZONT API username/password is not configured';
            return null;
        }
        if (!function_exists('curl_init')) {
            $this->last_error = 'PHP cURL extension is not installed';
            return null;
        }

        $data_string = is_array($data) ? json_encode($data) : '';

        $ch = curl_init(self::API_URL . $command);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        if ($data_string !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MajorDoMo/zontdevices');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-ZONT-Client: ' . $username,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            $this->last_error = 'Connection error: ' . $errstr . ' (curl #' . $errno . ')';
            DebMes('zontdevices: ' . $command . ' - ' . $this->last_error, 'zont');
            return null;
        }
        if ($http_code == 401 || $http_code == 403) {
            $this->last_error = 'Authorization failed (HTTP ' . $http_code . '), please check API username and password';
            DebMes('zontdevices: ' . $command . ' - ' . $this->last_error, 'zont');
            return null;
        }
        if ($http_code < 200 || $http_code >= 300) {
            $this->last_error = 'Unexpected answer from ZONT API (HTTP ' . $http_code . ')';
            DebMes('zontdevices: ' . $command . ' - ' . $this->last_error, 'zont');
            return null;
        }

        $res = json_decode((string)$result, true);
        if (!is_array($res)) {
            $this->last_error = 'Unable to parse ZONT API answer';
            DebMes('zontdevices: ' . $command . ' - ' . $this->last_error, 'zont');
            return null;
        }
        if (isset($res['ok']) && !$res['ok']) {
            if (!empty($res['error_ui'])) {
                $this->last_error = (string)$res['error_ui'];
            } elseif (!empty($res['error'])) {
                $this->last_error = (string)$res['error'];
            } else {
                $this->last_error = 'ZONT API returned an error';
            }
            DebMes('zontdevices: ' . $command . ' - ' . $this->last_error, 'zont');
        }

        return $res;
    }

    /**
     * Storing the latest error message in the module config (only when it changes)
     *
     * @param string $error
     */
    function storeLastError($error)
    {
        $error = (string)$error;
        $stored = isset($this->config['LAST_ERROR']) ? (string)$this->config['LAST_ERROR'] : '';
        if ($stored === $error) {
            return;
        }
        $this->config['LAST_ERROR'] = $error;
        $this->saveConfig();
    }

    /**
     * Processing 'thermostat_work' data received through /api/load_data
     */
    function processDeviceDataResponse($data)
    {
        if (!is_array($data)) {
            return;
        }
        if (isset($data['device_id'])) {
            $serial = $data['device_id'];
        } elseif (isset($data['id'])) {
            $serial = $data['id'];
        } else {
            return;
        }

        $device_rec = SQLSelectOne("SELECT * FROM zontdevices WHERE SERIAL_ID='" . DBSafe($serial) . "'");
        if (!is_array($device_rec) || empty($device_rec['ID'])) {
            // device records are created in processDeviceData(), this answer contains no data to create one
            return;
        }

        $work = (isset($data['thermostat_work']) && is_array($data['thermostat_work'])) ? $data['thermostat_work'] : array();

        // system name => value handling ('int' - integer counter, 'temperature' - fractional temperature)
        $fields = array(
            'power' => '',
            'fail' => 'int',
            'boiler_work_time' => 'int',
            'target_temp' => 'temperature',
            'pza_t' => 'temperature',
        );

        $commands = array();
        foreach ($fields as $key => $kind) {
            if (!isset($work[$key][0][1])) {
                continue;
            }
            $command = array();
            $command['SYSTEM'] = $key;
            if ($kind == 'int') {
                $command['VALUE'] = (int)$work[$key][0][1];
            } else {
                $command['VALUE'] = $work[$key][0][1];
            }
            if ($kind == 'temperature') {
                $command['VALUE_TYPE'] = 'temperature';
            }
            $commands[] = $command;
        }

        $this->processCommandsArray($device_rec['ID'], $commands);
    }

    /**
     * Processing one device record received through /api/devices
     */
    function processDeviceData($data)
    {
        if (!is_array($data) || !isset($data['id'])) {
            return;
        }

        $device_rec = SQLSelectOne("SELECT * FROM zontdevices WHERE SERIAL_ID='" . DBSafe($data['id']) . "'");
        if (!is_array($device_rec)) {
            $device_rec = array();
        }
        if (empty($device_rec['ID'])) {
            $device_rec['SERIAL_ID'] = $data['id'];
            $device_rec['DEVICE_TYPE'] = isset($data['device_type']['code']) ? $data['device_type']['code'] : '';
            if (isset($data['name']) && $data['name'] !== '') {
                $device_rec['TITLE'] = $data['name'];
            } elseif (isset($data['device_type']['name'])) {
                $device_rec['TITLE'] = $data['device_type']['name'];
            } else {
                $device_rec['TITLE'] = (string)$data['id'];
            }
            $device_rec['ID'] = SQLInsert('zontdevices', $device_rec);
        }

        $commands = array();
        $has = array();
        if (isset($data['capabilities']) && is_array($data['capabilities'])) {
            foreach ($data['capabilities'] as $feature) {
                if (is_string($feature) || is_int($feature)) {
                    $has[$feature] = 1;
                }
            }
        }

        if (isset($has['has_gsm_balance']) && isset($data['balance']['value'])) {
            $command = array();
            $command['SYSTEM'] = 'gsm_balance';
            $command['VALUE'] = $data['balance']['value'];
            $commands[] = $command;
        }

        if (isset($has['has_gtw_reports'])) {
            // system name => array(title, value type)
            $gtw_fields = array(
                'gtw_t_air_int_sensor' => array('Int. air sensor, T', 'temperature'),
                'gtw_t_air_ext_sensor' => array('Ext. air sensor, T', 'temperature'),
                'gtw_t_water_sensor' => array('Water sensor, T', 'temperature'),
                'gtw_t_air_set_disp' => array('Auto Air target, T', 'temperature'),
                'gtw_t_water_set_disp' => array('Auto Water target, T', 'temperature'),
                'gtw_p_water' => array('Water pressure', ''),
                'gtw_t_water' => array('Water target', 'temperature'),
                'gtw_t_air' => array('Air target', 'temperature'),
                'gtw_gvs' => array('Mode GVS', ''),
            );
            foreach ($gtw_fields as $key => $info) {
                if (!isset($data[$key]) || is_array($data[$key])) {
                    continue;
                }
                $command = array();
                $command['SYSTEM'] = $key;
                $command['TITLE'] = $info[0];
                $command['VALUE'] = $data[$key];
                if ($info[1] !== '') {
                    $command['VALUE_TYPE'] = $info[1];
                }
                $commands[] = $command;
            }
            if (isset($data['gtw_mode']['current'])) {
                $command = array();
                $command['SYSTEM'] = 'gtw_mode';
                $command['TITLE'] = 'Mode';
                $command['VALUE'] = $data['gtw_mode']['current'];
                $commands[] = $command;
            }
        }

        if (isset($has['has_multiple_thermometers']) && isset($data['thermometers']) && is_array($data['thermometers'])) {
            foreach ($data['thermometers'] as $term) {
                if (!is_array($term) || !isset($term['serial'])) {
                    continue;
                }
                $serial = str_replace('%', '', (string)$term['serial']);
                $command = array();
                $command['SYSTEM'] = $serial . '_value';
                $command['TITLE'] = (isset($term['name']) && $term['name'] !== '' ? $term['name'] : $serial) . ', T';
                $command['VALUE'] = isset($term['last_value']) ? $term['last_value'] : '';
                $command['VALUE_TYPE'] = 'temperature';
                if (!empty($term['last_value_time'])) {
                    $command['UPDATED'] = date('Y-m-d H:i:s', (int)$term['last_value_time']);
                }
                $commands[] = $command;
            }
        }

        if (isset($has['has_thermostat'])) {
            if (isset($data['thermostat_mode'])) {
                $command = array();
                $command['SYSTEM'] = 'thermostat_mode';
                $command['VALUE'] = $data['thermostat_mode'];
                $commands[] = $command;
            }
            if (isset($data['thermostat_ext_mode'])) {
                $command = array();
                $command['SYSTEM'] = 'thermostat_ext_mode';
                $command['VALUE'] = $data['thermostat_ext_mode'];
                $comments = '';
                if (isset($data['thermostat_ext_modes_config']) && is_array($data['thermostat_ext_modes_config'])) {
                    foreach ($data['thermostat_ext_modes_config'] as $im => $mode_config) {
                        if (empty($mode_config['name'])) {
                            continue;
                        }
                        $comments .= $im . ' = ' . $mode_config['name'] . '; ';
                    }
                }
                if ($comments !== '') {
                    $command['COMMENTS'] = $comments;
                }
                $commands[] = $command;
            }
        }

        if (isset($data['rf_status']) && is_array($data['rf_status'])) {
            foreach ($data['rf_status'] as $k => $v) {
                if (!is_array($v)) {
                    continue;
                }
                $serial = preg_replace('/^s_/', '', (string)$k);
                $serial = str_replace('%', '', $serial);
                $name = (isset($v['name']) && $v['name'] !== '') ? $v['name'] : $serial;
                if (isset($v['bat_v'])) {
                    $command = array();
                    $command['SYSTEM'] = $serial . '_rf_batt_v';
                    $command['TITLE'] = $name . ', V';
                    $command['VALUE'] = $v['bat_v'];
                    $commands[] = $command;
                }
                if (isset($v['dbm'])) {
                    $command = array();
                    $command['SYSTEM'] = $serial . '_rf_dbm';
                    $command['TITLE'] = $name . ', Dbm';
                    $command['VALUE'] = $v['dbm'];
                    $commands[] = $command;
                }
            }
        }

        $command = array();
        $command['SYSTEM'] = 'online';
        $command['TITLE'] = 'Online';
        $command['VALUE'] = !empty($data['online']) ? 1 : 0;
        $commands[] = $command;

        $this->processCommandsArray($device_rec['ID'], $commands);
    }

    /**
     * Storing a set of device values and pushing them to the linked objects
     */
    function processCommandsArray($device_id, $commands)
    {
        $device_id = (int)$device_id;
        if (!$device_id || !is_array($commands)) {
            return;
        }

        foreach ($commands as $command) {
            if (!isset($command['SYSTEM']) || $command['SYSTEM'] === '') {
                continue;
            }
            if (!isset($command['TITLE']) || $command['TITLE'] === '') {
                $command['TITLE'] = $command['SYSTEM'];
            }
            $command['VALUE'] = isset($command['VALUE']) ? (string)$command['VALUE'] : '';

            $command_rec = SQLSelectOne("SELECT * FROM zontcommands WHERE SYSTEM='" . DBSafe($command['SYSTEM']) . "' AND DEVICE_ID=" . $device_id);
            if (!is_array($command_rec)) {
                $command_rec = array();
            }
            $stored_rec = $command_rec;

            if (!isset($command['UPDATED'])) {
                $old_value = isset($stored_rec['VALUE']) ? $stored_rec['VALUE'] : null;
                if ($old_value === null || $old_value != $command['VALUE']) {
                    $command['UPDATED'] = date('Y-m-d H:i:s');
                }
            }

            foreach ($command as $k => $v) {
                $command_rec[$k] = $v;
            }

            if (empty($command_rec['ID'])) {
                $command_rec['DEVICE_ID'] = $device_id;
                $command_rec['ID'] = SQLInsert('zontcommands', $command_rec);
            } else {
                // writing to the database only when something has really changed
                $modified = false;
                foreach ($command_rec as $k => $v) {
                    if (!array_key_exists($k, $stored_rec) || $stored_rec[$k] != $v) {
                        $modified = true;
                        break;
                    }
                }
                if ($modified) {
                    SQLUpdate('zontcommands', $command_rec);
                }
            }

            if (!empty($command_rec['LINKED_OBJECT']) && !empty($command_rec['LINKED_PROPERTY'])) {
                setGlobal($command_rec['LINKED_OBJECT'] . '.' . $command_rec['LINKED_PROPERTY'], $command_rec['VALUE'], array($this->name => '0'));
            }
            if (!empty($command_rec['LINKED_OBJECT']) && !empty($command_rec['LINKED_METHOD'])) {
                $params = array();
                $params['VALUE'] = $command_rec['VALUE'];
                callMethod($command_rec['LINKED_OBJECT'] . '.' . $command_rec['LINKED_METHOD'], $params);
            }
        }
    }

    /**
     * Reading the current state of all devices from ZONT online API
     *
     * @return bool true when the device list has been received
     */
    function refreshDevices()
    {
        $raw = (isset($_GET['raw']) && $_GET['raw']) ? 1 : 0;

        $data = $this->requestZontAPI('/api/devices');
        if ($raw) {
            dprint($data, false);
        }

        if (!is_array($data) || !isset($data['devices']) || !is_array($data['devices'])) {
            $error = ($this->last_error !== '') ? $this->last_error : 'Device list is not available';
            $this->storeLastError($error);
            if ($raw) {
                dprint($error);
            }
            return false;
        }
        $this->storeLastError('');

        $requests = array();
        foreach ($data['devices'] as $device) {
            if (!is_array($device) || !isset($device['id'])) {
                continue;
            }
            if (!$raw) {
                $this->processDeviceData($device);
            }
            $last_receive_time = isset($device['last_receive_time']) ? (int)$device['last_receive_time'] : 0;
            if ($last_receive_time <= 0) {
                // the device has never reported anything, there is nothing to load
                continue;
            }
            $requests[] = array(
                'device_id' => $device['id'],
                'data_types' => array('thermostat_work'),
                'maxtime' => $last_receive_time,
                'mintime' => $last_receive_time - 30 * 60,
            );
        }

        if (!count($requests)) {
            if ($raw) {
                dprint('No devices with reported data');
            }
            return true;
        }

        $data = $this->requestZontAPI('/api/load_data', array('requests' => $requests));
        if ($raw) {
            dprint($data);
            return true;
        }
        if (!is_array($data) || !isset($data['responses']) || !is_array($data['responses'])) {
            // detailed data is optional, the device list has already been processed
            return true;
        }
        foreach ($data['responses'] as $response) {
            $this->processDeviceDataResponse($response);
        }

        return true;
    }

    /**
     * Sending a command to the device
     *
     * @return bool
     */
    function writeDeviceCommand($device_rec, $command_rec, $value)
    {
        if (!is_array($device_rec) || !is_array($command_rec)) {
            return false;
        }
        $device_type = isset($device_rec['DEVICE_TYPE']) ? $device_rec['DEVICE_TYPE'] : '';
        $system = isset($command_rec['SYSTEM']) ? $command_rec['SYSTEM'] : '';
        if ($system === '' || empty($this->device_types[$device_type]['commands'][$system]['CANSET'])) {
            return false;
        }

        $data = array();
        $data['device_id'] = isset($device_rec['SERIAL_ID']) ? $device_rec['SERIAL_ID'] : '';
        if ($system == 'gtw_mode') {
            $data[$system]['current'] = $value;
            $data[$system]['old'] = $value;
        } else {
            $data[$system] = $value;
        }

        $result = $this->requestZontAPI('/api/update_device', $data);
        if (!is_array($result) || (isset($result['ok']) && !$result['ok'])) {
            $error = ($this->last_error !== '') ? $this->last_error : 'Command was rejected by ZONT API';
            $this->storeLastError($error);
            return false;
        }
        return true;
    }

    /**
     * Called from scripts/cycle_zontdevices.php
     */
    function processCycle()
    {
        $this->getConfig();
        $poll = isset($this->config['API_POLL']) ? (int)$this->config['API_POLL'] : 0;
        if ($poll < self::MIN_POLL) {
            $poll = self::DEFAULT_POLL;
        }
        $latest_iteration = (int)$this->cycle_time;
        if ((time() - $latest_iteration) < $poll) {
            return;
        }
        $this->cycle_time = time();
        $this->refreshDevices();
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
