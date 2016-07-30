<?php
namespace Brige\Utils;
/**
 * http请求相关的工具函数
 *
 * @author cao_zl
 */
class Http {
    /**
     * 请求类型 post
     */
    const REQUEST_METHOD_post = 'POST';
    /**
     * 请求类型 get
     */
    const REQUEST_METHOD_get = 'GET';
    /**
     * 发送post类型的请求
     * 
     * @param string $url 请求的地址
     * @param array $content 表单内容
     * @param int $timeout 本次请求的超时时间
     * @param boolean $sendCookie 是否发送cookie
     * @param boolean $writeErrorLog 是否写错误日志
     * @param array $header 发送的头信息数组
     * @param boolean $responseHeader 是否返回接收的头信息
     * @return array
     * @throws \Exception
     */
    public static function postRequest($url , $content , $timeout=5 , $sendCookie=false , $writeErrorLog=true , $header=array() , $responseHeader=false){
        try{
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT,$timeout); //
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.14) Gecko/20110301 Fedora/3.6.14-1.fc14 Firefox/3.6.14');
            curl_setopt($ch, CURLOPT_HEADER, $responseHeader ? true : 0);
            if($header){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            if($sendCookie){
                $cookieFile = self::_getCookieFile();
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            $responseAll = curl_exec($ch);
            if (false === $responseAll) {
                throw new \Exception('curl_errno('.curl_errno($ch).'):curl_error('.curl_error($ch).')');
            }
            if($responseHeader){
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                // 根据头大小去获取头信息内容
                $headerInfo = self::parseHeader(substr($responseAll, 0, $headerSize));
                $html = substr($responseAll, $headerSize);
            }else{
                $html = $responseAll;
            }
            curl_close($ch);
        }  catch (\Exception $e){
            $statusCode = $e->getCode() ? $e->getCode() : 990;
            $errorInfo = $e->getMessage();
            if($writeErrorLog){
                self::_writeRequestErrorLog('POST', $errorInfo, func_get_args());
            }
        }
        return array(isset($statusCode) ? $statusCode : 200 , isset($html) ? $html : '' , isset($errorInfo) ? $errorInfo : '' , isset($headerInfo) ? $headerInfo : '');
            
    }
    
    /**
     * 发送get类型的请求
     * 
     * @param string $url 请求的地址
     * @param int $timeout 本次请求的超时时间
     * @param boolean $sendCookie 是否发送cookie
     * @param boolean $writeErrorLog 是否写错误日志
     * @param array $header 发送的头信息数组
     * @param boolean $responseHeader 是否返回接收的头信息
     * @return array
     * @throws \Exception
     */
    public static function getRequest($url , $timeout=8 , $sendCookie=false , $writeErrorLog=true,$header=array() , $responseHeader=false){
        try{
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
            curl_setopt($ch, CURLOPT_HEADER, $responseHeader ? true : 0);
            curl_setopt($ch, CURLOPT_TIMEOUT,$timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.14) Gecko/20110301 Fedora/3.6.14-1.fc14 Firefox/3.6.14');
            if($header){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            if($sendCookie){
                $cookieFile = self::_getCookieFile();
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            $responseAll = curl_exec($ch);
            if (false === $responseAll) {
                throw new \Exception('curl_errno('.curl_errno($ch).'):curl_error('.curl_error($ch).')');
            }
            if($responseHeader){
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                // 根据头大小去获取头信息内容
                $headerInfo = self::parseHeader(substr($responseAll, 0, $headerSize));
                $html = substr($responseAll, $headerSize);
            }else{
                $html = $responseAll;
            }
            curl_close($ch);
        }  catch (\Exception $e){
            $statusCode = $e->getCode() ? $e->getCode() : 990;
            $errorInfo = $e->getMessage();
            if($writeErrorLog){
                self::_writeRequestErrorLog('GET', $errorInfo, func_get_args());
            }
        }
        return array(isset($statusCode) ? $statusCode : 200 , isset($html) ? $html : '' , isset($errorInfo) ? $errorInfo : '', isset($headerInfo) ? $headerInfo : '');
    }
    
    /**
     * 定义cookie文件位置
     * @return string
     */
    private static function _getCookieFile(){
        return '/tmp/__cookieFile.cookie';
    }
    
    private static function _writeRequestErrorLog($method , $errorInfo , $inputParams){
        $writeString  = 'error_info:'.$errorInfo."\r\nmethod:".$method."\r\nparams:".json_encode($inputParams)."\r\n";
        $writeString .= 'From:'.(isset($_SERVER['REQUEST_URI']) ? '(REQUEST_URI)'.$_SERVER['REQUEST_URI'] : '(SCRIPT_NAME)'.$_SERVER['SCRIPT_NAME'])."\r\n";
        $writeString .= 'IP:'.self::ip();
        file_put_contents('/tmp/request_error_log_tmp.log' , $writeString , FILE_APPEND);
    }
    
    /**
     * 解析返回的头信息字符串
     * @param string $headerString
     * @return array
     */
    public static function parseHeader($headerString){
        $lineArray = explode("\r\n" , $headerString);
        $paseArr = array();
        foreach($lineArray as $line){
            if($line && ( ($pos = strpos($line , ':')) !== false)){
                $paseArr[substr($line, 0 , $pos)] = trim(substr($line, $pos + 1));
            }
        }
        return $paseArr;
    }
    
    public static function ip(){
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else if(isset($_SERVER["REMOTE_ADDR"])) {
                $realip = $_SERVER["REMOTE_ADDR"];
            } else {
                $realip = '0.0.0.0';
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }
    
    
    
}
