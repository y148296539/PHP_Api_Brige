<?php
namespace Brige\Utils;
/**
 * 数组相关工具
 *
 * @author caozlThird
 */
class Map {
    
    /**
     * 多维关联数组合并
     * - 数组1为主体
     * - 数组2中如果有数组一中的对应键，则后者覆盖前者
     * - 可以是多维关联数组
     * 
     * @param array $array1
     * @param array $array2
     * 
     * @return array
     */
    public static function mergeArray($array1 , $array2){
        foreach($array2 as $key => $value){
            if(is_integer($key)){
                isset($array1[$key]) ? $array1[]=$value : $array1[$key]=$value;
            }elseif(is_array($value) && isset($array1[$key])){
                $array1[$key] = self::mergeArray($array1[$key], $value);
            }else{
                $array1[$key] = $value;
            }            
        }
        return $array1;
    }
    
    
    /**
     * 将给定目标字符串编码转换为指定编码 - 支持多维数组和对象
     * 
     * @param mixd $target 原始目标，可以是字符串、数组或者对象
     * @param string $in_charset 原始字符编码，默认GBK
     * @param string $out_charset 希望转成的字符编码，默认UTF-8
     */
    public static function changeTargeEncoding(&$target , $in_charset='GBK' , $out_charset='UTF-8'){
        if($target){
            if(is_string($target) && !is_numeric($target)){
                $target = iconv($in_charset, $out_charset.'//IGNORE', $target);
            }elseif(is_array($target)){
                foreach($target as $k => $v){
                    self::changeTargeEncoding($target[$k], $in_charset, $out_charset);
                }
            }elseif(is_object($target)){
                foreach($target as $k => $v){
                    self::changeTargeEncoding($target->$k, $in_charset, $out_charset);
                }
            }
        }
    }
    
    
    
}
