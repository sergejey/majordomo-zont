<?php

$this->device_types = array(
    'T100' => array(
        'TITLE' => 'ZONT H-1/H-1V',
        'manual' => '',
        'commands' =>
            array()
    ),
);

foreach($this->device_types as $k=>$v) {
    if ($v['copy']) {
        foreach($v as $kv=>$vv) {
            if (!isset($this->device_types[$k][$kv])) {
                $this->device_types[$k][$kv]=$this->device_types[$v['copy']][$kv];
            }
        }
    }
}