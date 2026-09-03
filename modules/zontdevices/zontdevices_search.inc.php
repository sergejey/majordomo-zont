<?php
/*
* @version 0.2 (wizard)
*/
 global $session;
  if (isset($this->owner->name) && $this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if (isset($session) && is_object($session)) {
   if (!empty($save_qry) && isset($session->data['zontdevices_qry'])) {
    $qry=$session->data['zontdevices_qry'];
   } else {
    $session->data['zontdevices_qry']=$qry;
   }
  }
  if (!$qry) $qry="1";
  $sortby_zontdevices="TITLE";
  $out['SORTBY']=$sortby_zontdevices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM zontdevices WHERE $qry ORDER BY ".$sortby_zontdevices);
  if (count($res)) {
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    $device_type=isset($res[$i]['DEVICE_TYPE']) ? $res[$i]['DEVICE_TYPE'] : '';
    if (!empty($this->device_types[$device_type]['TITLE'])) {
     $res[$i]['TITLE'].=' ('.$this->device_types[$device_type]['TITLE'].')';
    } elseif ($device_type!=='') {
     // unknown device type: showing its code so that it can be added to structure.inc.php
     $res[$i]['TITLE'].=' ('.$device_type.')';
    }
    if (!empty($this->device_types[$device_type]['manual'])) {
     $res[$i]['MANUAL']=$this->device_types[$device_type]['manual'];
    }
    $res[$i]['DATA']='';
    $res[$i]['UPDATED']='';
    $res[$i]['ONLINE']=0;
    $data=SQLSelect("SELECT * FROM zontcommands WHERE DEVICE_ID=".(int)$res[$i]['ID']);
    $max_update=0;
    foreach($data as $v) {
     $title=isset($v['TITLE']) ? (string)$v['TITLE'] : '';
     $value=isset($v['VALUE']) ? (string)$v['VALUE'] : '';
     if ($title==='' || preg_match('/^_config/',$title)) continue;
     $tm=!empty($v['UPDATED']) ? (int)strtotime($v['UPDATED']) : 0;
     if ($tm>$max_update) {
      $max_update=$tm;
      $res[$i]['UPDATED']=$v['UPDATED'];
     }
     if (isset($v['SYSTEM']) && $v['SYSTEM']=='online') {
      // reported by the device itself, shown as a status label instead of a value
      $res[$i]['ONLINE']=($value=='1') ? 1 : 0;
      continue;
     }
     $res[$i]['DATA'].=htmlspecialchars($title).': <b>'.htmlspecialchars($value).'</b>; ';
    }
   }
   $out['RESULT']=$res;
  }
