<?php
/*
* @version 0.2 (wizard)
*/
  if (isset($this->owner->name) && $this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='zontdevices';
  $id=(int)$id;
  $rec=array();
  if ($id) {
   $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID=".$id);
  }
  if (!is_array($rec)) $rec=array();

  if ($this->tab=='data' && gr('refresh')) {
      $this->refreshDevices();
      $this->redirect("?view_mode=".$this->view_mode."&id=".(int)(isset($rec['ID'])?$rec['ID']:0)."&tab=".$this->tab);
      return;
  }

  $new_rec=0;
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
  //updating '<%LANG_TITLE%>' (varchar, required)
   $rec['TITLE']=gr('title');
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }

  }
  // step: data
  if ($this->tab=='data') {
  }
  //UPDATING RECORD
   if ($ok) {
    if (!empty($rec['ID'])) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
    }
    $out['OK']=1;
    if ($new_rec) {
        $this->redirect("?id=".(int)$rec['ID']."&view_mode=".$this->view_mode."&tab=data&refresh=1");
        return;
    }
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  }
  // step: data
  if ($this->tab=='data') {
   //dataset2
   global $delete_id;
   if (!empty($delete_id)) {
    SQLExec("DELETE FROM zontcommands WHERE ID='".(int)$delete_id."'");
   }
   $properties=array();
   if (!empty($rec['ID'])) {
    $properties=SQLSelect("SELECT * FROM zontcommands WHERE DEVICE_ID=".(int)$rec['ID']." ORDER BY SYSTEM, TITLE, ID");
   }
   $total=count($properties);
   $to_set=array();
   for($i=0;$i<$total;$i++) {
    if ($this->mode=='update') {
      // remembering current links BEFORE they are overwritten with the submitted values
      $old_linked_object=isset($properties[$i]['LINKED_OBJECT']) ? $properties[$i]['LINKED_OBJECT'] : '';
      $old_linked_property=isset($properties[$i]['LINKED_PROPERTY']) ? $properties[$i]['LINKED_PROPERTY'] : '';

      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim((string)(${'linked_object'.$properties[$i]['ID']} ?? ''));
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim((string)(${'linked_property'.$properties[$i]['ID']} ?? ''));
      global ${'linked_method'.$properties[$i]['ID']};
      $properties[$i]['LINKED_METHOD']=trim((string)(${'linked_method'.$properties[$i]['ID']} ?? ''));
      SQLUpdate('zontcommands', $properties[$i]);

      if ($old_linked_object && $old_linked_property &&
          ($old_linked_object!=$properties[$i]['LINKED_OBJECT'] || $old_linked_property!=$properties[$i]['LINKED_PROPERTY'])) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }

      global ${'set'.$properties[$i]['ID']};
        $set_value=${'set'.$properties[$i]['ID']} ?? '';
        if ($set_value!=='') {
            $to_set[$properties[$i]['ID']]=$set_value;
        }
     }

       if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
           addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
       }
       $device_type=isset($rec['DEVICE_TYPE']) ? $rec['DEVICE_TYPE'] : '';
       $command=isset($this->device_types[$device_type]['commands'][$properties[$i]['SYSTEM']])
                ? $this->device_types[$device_type]['commands'][$properties[$i]['SYSTEM']]
                : array();
       $properties[$i]['SDEVICE_TYPE']='';
       if (isset($properties[$i]['VALUE_TYPE']) && $properties[$i]['VALUE_TYPE']=='temperature') {
           $properties[$i]['SDEVICE_TYPE']='sensor_temp';
       }
       $properties[$i]['CANSET']=!empty($command['CANSET']) ? 1 : 0;
       if (empty($properties[$i]['COMMENTS']) && !empty($command['COMMENTS'])) {
           $properties[$i]['COMMENTS']=$command['COMMENTS'];
       }

   }
   $out['PROPERTIES']=$properties;

      if (count($to_set)>0) {
          foreach($to_set as $k=>$v) {
              $property=SQLSelectOne("SELECT * FROM zontcommands WHERE DEVICE_ID=".(int)$rec['ID']." AND ID=".(int)$k);
              if (is_array($property) && !empty($property['ID'])) {
                  $this->writeDeviceCommand($rec,$property,$v);
              }
          }
          $this->redirect("?view_mode=".$this->view_mode."&id=".(int)$rec['ID']."&tab=".$this->tab."&refresh=1");
          return;
      }

  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars((string)$v);
    }
   }
  }
  outHash($rec, $out);

foreach($this->device_types as $k=>$v) {
    $out['DEVICE_TYPES'][]=array('NAME'=>$k,'TITLE'=>$v['TITLE']);
}
