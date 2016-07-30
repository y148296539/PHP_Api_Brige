<?php
namespace Brige\Utils;
//定义桥接配置文件的文件夹位置
defined('BRIGE_CONFIG_DIR') || define('BRIGE_CONFIG_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Config'.DIRECTORY_SEPARATOR);
/**
 * 配置文件环境覆盖统一管理
 *
 * @author cao_zl
 */
class Config {
    
    private static $_configList = array();

    private $_file_name = '';
    
    private $_file_ext = '.php';
    
    private $_configRegister = array();

    /**
     * 读取配置文件的指定值
     * @param string $fileName 文件名
     * @param string $fileExt 文件名后缀
     * @return \Brige\Utils\Config
     */
    public static function getFile($fileName , $fileExt='.php'){
        if(!isset(self::$_configList[$fileName.$fileExt])){
            self::$_configList[$fileName.$fileExt] = new self($fileName , $fileExt);
            self::$_configList[$fileName.$fileExt]->_loadFile();
            self::$_configList[$fileName.$fileExt]->_mergeEnvironmentFile();
        }
        return self::$_configList[$fileName.$fileExt];
    }
    
    
    private function __construct($fileName , $fileExt) {
        $this->_file_name = $fileName;
        $this->_file_ext = $fileExt;
    }

    /**
     * 读取共有配置
     * @throws Exception
     */
    private function _loadFile(){
        $filePath = BRIGE_CONFIG_DIR.$this->_file_name.$this->_file_ext;
        if(!file_exists($filePath)){
            throw new Exception('config file not found - '.$filePath);
        }
        $this->_configRegister = include $filePath;
    }
    
    /**
     * 读取并将私有配置合并进共有配置
     * @throws Exception
     */
    private function _mergeEnvironmentFile(){
        $coverFilePath = BRIGE_CONFIG_DIR.\Brige\Utils\Environment::getEnvironent().DIRECTORY_SEPARATOR.$this->_file_name.$this->_file_ext;
        if(file_exists($coverFilePath)){
            $coverConfig = include $coverFilePath;
            if(!is_array($coverConfig)){
                throw new Exception('cover config file format failed! - '.$coverFilePath);
            }
            $this->_configRegister = \Brige\Utils\Map::mergeArray($this->_configRegister , $coverConfig);
        }
    }
    
    /**
     * 返回配置中指定键名的值
     * 
     * @param string $config_key
     * @param mixed $default_value
     * @return mixed
     */
    public function get($config_key , $default_value=null){
        return isset($this->_configRegister[$config_key]) ? $this->_configRegister[$config_key] : $default_value;
    }


}