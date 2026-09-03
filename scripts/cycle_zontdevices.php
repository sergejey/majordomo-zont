<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// database connection is created by ./lib/loader.php ($db)
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'zontdevices/zontdevices.class.php');
$zontdevices_module = new zontdevices();
$zontdevices_module->getConfig();

if (empty($zontdevices_module->config['API_USERNAME']) || empty($zontdevices_module->config['API_PASSWORD'])) {
   echo "ZONT API credentials are not set\n";
   exit; // nothing to poll until the module is configured
}

echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check = 0;
$checkEvery = 5; // checking the polling schedule every 5 seconds
while (1)
{
   setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
   if ((time() - $latest_check) > $checkEvery) {
    $latest_check = time();
    $zontdevices_module->processCycle();
   }
   if (file_exists('./reboot') || isset($_GET['onetime']))
   {
      if (isset($db) && is_object($db)) {
         $db->Disconnect();
      }
      exit;
   }
   sleep(1);
}
