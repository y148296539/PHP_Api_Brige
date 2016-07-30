<?php
namespace Brige\Utils;
/**
 * 环境变量检测
 * 
 * @author caozl
 */
class Environment {
    /**
     * 服务器环境变量标记键名
     */
    const SERVICE_FLAG_KEY = 'APPLICATION_ENVIRONMENT';
    /**
     * 开发环境标记
     */
    const FLAG_development = 'development';
    /**
     * 测试环境标记
     */
    const FLAG_testing = 'testing';
    /**
     * 生产环境标记
     */
    const FLAG_production = 'production';
    /**
     * 预发布环境标记
     */
    const FLAG_production2 = 'production2';
    /**
     * 保存当前运行环境的标记
     * @var string
     */
    static $_env_flag = null;
    /**
     * 返回环境标记
     * @return string
     */
    public static function getEnvironent(){
        if(self::$_env_flag === null && function_exists('getenv')){
            self::$_env_flag = getenv(self::SERVICE_FLAG_KEY);
        }
        if(in_array(self::$_env_flag, array(self::FLAG_development , self::FLAG_testing , self::FLAG_production2))){
            return ENVIRONMENT;
        }
        return self::FLAG_production;
    }
    
    /**
     * 判断是否是开发环境
     * @return boolean
     */
    public static function isDevelopment(){
        return self::getEnvironent() == self::FLAG_development ? true : false;
    }
    
    /**
     * 判断是否是生产环境
     * @return boolean
     */
    public static function isProduction(){
        return self::getEnvironent() == self::FLAG_production ? true : false;
    }
    
    /**
     * 判断是否是测试环境
     * @return boolean
     */
    public static function isTesting(){
        return self::getEnvironent() == self::FLAG_testing ? true : false;
    }
    
    /**
     * 判断是否是预发布环境
     * @return boolean
     */
    public static function isProduction2(){
        return self::getEnvironent() == self::FLAG_production2 ? true : false;
    }
    
    
    
}
