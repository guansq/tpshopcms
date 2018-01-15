<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/14
 * Time: 17:49
 */
namespace app\admin\logic;

use think\Model;
use think\Db;

class NavigationLogic extends Model
{
    /*
     * 获得指定导航下的数组
     */
    public function get_all_nav_list($nav_id = 0,$selected = 0,$re_type = false, $level = 0){

        static $res = NULL;
        if($res === NULL){
            $data = false;
            if($data === false){
                $where = "where 1=1";
                $sql = "SELECT n.*,COUNT(s.id) AS has_children FROM __PREFIX__navigation AS n ".
                    "LEFT JOIN __PREFIX__navigation AS s ON s.parent_id = n.id $where ".
                    " GROUP BY n.id  ORDER BY parent_id, sort";
                $res = DB::query($sql);
                //print_r($res);die;
            }else{
                $res = $data;
            }
        }
        if (empty($res) == true)
        {
            return $re_type ? '' : array();
        }
        $options = $this->nav_option($nav_id,$res);
        /* 截取到指定的缩减级别 */
        if ($level > 0)
        {
            if ($nav_id == 0)
            {
                $end_level = $level;
            }
            else
            {
                $first_item = reset($options); // 获取第一个元素
                $end_level  = $first_item['level'] + $level;
            }

            /* 保留level小于end_level的部分 */
            foreach ($options AS $key => $val)
            {
                if ($val['level'] >= $end_level)
                {
                    unset($options[$key]);
                }
            }
        }
        $pre_key = 0;
        foreach ($options AS $key => $value)
        {
            $options[$key]['has_children'] = 0;
            if ($pre_key > 0)
            {
                if ($options[$pre_key]['id'] == $options[$key]['parent_id'])
                {
                    $options[$pre_key]['has_children'] = 1;
                }
            }
            $pre_key = $key;
        }

        if ($re_type == true)
        {
            $select = '';
            foreach ($options AS $var)
            {
                $select .= '<option value="' . $var['id'] . '" ';
                //$select .= ' cat_type="' . $var['cat_type'] . '" ';
                $select .= ($selected == $var['id']) ? 'selected="selected"' : '';
                $select .= '>';
                if ($var['level'] > 0)
                {
                    $select .= str_repeat('&nbsp;', $var['level'] * 4);
                }
                $select .= htmlspecialchars(addslashes($var['name'])) . '</option>';
            }

            return $select;
        }
        else
        {
            foreach ($options AS $key => $value)
            {
                ///$options[$key]['url'] = build_uri('article_cat', array('acid' => $value['cat_id']), $value['cat_name']);
            }
            return $options;
        }
        //print_r($options);
    }

    /*
     * 获得指定分类下的数组
     */
    public function nav_option($spec_cat_id, $arr){
        static $cat_options = array();
        if(isset($cat_options[$spec_cat_id])){
            return $cat_options[$spec_cat_id];
        }
        if(!isset($cat_options[0])){
            $level = $last_cat_id = 0;
            $options = $cat_id_array = $level_array = array();
            while(!empty($arr)){
                foreach($arr AS $key=>$value){
                    $cat_id = $value['id'];
                    if($level == 0 && $last_cat_id == 0){
                        if($value['parent_id'] > 0){
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['name'];
                        unset($arr[$key]);
                        if($value['has_children'] == 0){
                            continue;
                        }
                        $last_cat_id  = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }
                    if($value['parent_id'] == $last_cat_id){
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['name'];
                        unset($arr[$key]);
                        if($value['has_children'] > 0){
                            if (end($cat_id_array) != $last_cat_id)
                            {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id    = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif($value['parent_id'] > $last_cat_id){
                        break;
                    }
                }
                $count = count($cat_id_array);
                if ($count > 1)
                {
                    $last_cat_id = array_pop($cat_id_array);
                }
                elseif ($count == 1)
                {
                    if ($last_cat_id != end($cat_id_array))
                    {
                        $last_cat_id = end($cat_id_array);
                    }
                    else
                    {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }
                if ($last_cat_id && isset($level_array[$last_cat_id]))
                {
                    $level = $level_array[$last_cat_id];
                }
                else
                {
                    $level = 0;
                    break;
                }

            }

            $cat_options[0] = $options;

        }
        else
        {
            $options = $cat_options[0];
        }

        if (!$spec_cat_id)
        {
            return $options;
        }
        else
        {
            if (empty($options[$spec_cat_id]))
            {
                return array();
            }

            $spec_cat_id_level = $options[$spec_cat_id]['level'];

            foreach ($options AS $key => $value)
            {
                if ($key != $spec_cat_id)
                {
                    unset($options[$key]);
                }
                else
                {
                    break;
                }
            }

            $spec_cat_id_array = array();
            foreach ($options AS $key => $value)
            {
                if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                    ($spec_cat_id_level > $value['level']))
                {
                    break;
                }
                else
                {
                    $spec_cat_id_array[$key] = $value;
                }
            }
            $cat_options[$spec_cat_id] = $spec_cat_id_array;

            return $spec_cat_id_array;
        }
    }
}