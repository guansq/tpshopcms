<?php

function jsonReturn($status=0,$msg='',$data=''){
    if(empty($data))
        $data = '';
    $info['status'] = $status ? 1 : $status;
    $info['msg'] = $msg;
    $info['result'] = $data;
    exit(json_encode($info));
}

/**
 *  根据规格ID获取对应名称
 * @param unknown $specs
 * @param unknown $keys
 * @return string
 */
 function getSpecNameById($specs , $keys){
    $specIds = explode('_', $keys['key']);
    $specName = "";
    foreach ($specIds as $k => $v){
        $specName .=$specs[$v]." ";
    }
    return $specName;
}