<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['zontdevices_qry'];
  } else {
   $session->data['zontdevices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_zontdevices="TITLE";
  $out['SORTBY']=$sortby_zontdevices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM zontdevices WHERE $qry ORDER BY ".$sortby_zontdevices);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
    //dprint($this->structure[$res[$i]['DEVICE_TYPE']],false);
    $res[$i]['TITLE'].=' ('.$this->device_types[$res[$i]['DEVICE_TYPE']]['TITLE'].')';
    if ($this->device_types[$res[$i]['DEVICE_TYPE']]['manual']) {
     $res[$i]['MANUAL']=$this->device_types[$res[$i]['DEVICE_TYPE']]['manual'];
    }
    $data=SQLSelect("SELECT * FROM zontcommands WHERE DEVICE_ID=".$res[$i]['ID']);
    $max_update=0;
    foreach($data as $k=>$v) {
     if (preg_match('/^_config/',$v['TITLE'])) continue;
     $res[$i]['DATA'].=$v['TITLE'].': <b>'.$v['VALUE'].'</b>; ';
     $tm=strtotime($v['UPDATED']);
     if ($tm>$max_update) {
      $max_update=$tm;
      $res[$i]['UPDATED']=$v['UPDATED'];
     }
    }
    if ($max_update>0 && (time()-$max_update)<5*60) {
     $res[$i]['ONLINE']=1;
    }
   }
   $out['RESULT']=$res;
  }
