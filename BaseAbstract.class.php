<?php
namespace Brige;
use Brige\Utils\Http;
/**
 * Description of Request
 *
 * @author caozlThird
 */
abstract class BaseAbstract {
    /**
     * 当次请求的域名在配置中的键名
     * @var string
     */
    public static $domainKey = 'defaultApi';
    /**
     * 当次请求的接口资源拼装后缀，如：/api/user/info
     * @var string
     */
    public static $urlAppend = '';
    /**
     * 当次请求的域名所在配置文件的文件名
     * @var string
     */
    public static $domainConfigFile = 'domain';
    /**
     * 是否书写错误日志
     * @var boolean
     */
    public static $errorLog = true;
    /**
     * 是否书写慢请求日志
     * @var boolean
     */
    public static $slowLog = true;
    /**
     * 是否书写调试日志
     * @var boolean
     */
    public static $debugLog = false;
    
    /**
     * 向接口发起请求，返回解析对象
     * 
     * @param array $headers 请求头信息
     * @param array $params 请求内容键值对
     * @param string $method 请求类型，GET/POST
     * 
     * @return \Brige\Parse
     */
    public static function get($headers , $params=array() , $method='GET'){
        try{
            $requestInstance = null;
            $exception = null;
            list($requestUrl , $headers , $params , $method) = self::_prepare($headers , $params , $method);
            Request::send($requestUrl , $headers , $params , $method , static::$debugLog , static::$slowLog , $requestInstance);
        }catch (ExceptionBrigeBase $exception){

        }catch (\Exception $exception){

        }
        return new Parse($requestInstance , $exception , static::$errorLog);
    }
    
    /**
     * 参数初始化准备
     * 
     * @param array $headers 请求头信息
     * @param array $params 请求内容键值对
     * @param string $method 请求类型，GET/POST
     * 
     * @return array
     * @throws ExceptionBrigeBase
     */
    private function _prepare($headers , $params , $method){
        if(!static::$domainKey){
            throw new ExceptionBrigeBase('request_prepare_domainKey_not_fond' , 801);
        }
        $requestUrl = \Brige\Utils\Config::getFile(static::$domainConfigFile)->get(static::$domainKey).static::$urlAppend;
        if(!$requestUrl){
            throw new ExceptionBrigeBase('request_prepare_url_not_found' , 901);
        }
        $method = (strtoupper($method) == Http::REQUEST_METHOD_get) ? Http::REQUEST_METHOD_get : Http::REQUEST_METHOD_post ;
        static::$debugLog = is_bool(static::$debugLog) ? static::$debugLog : false;
        static::$slowLog = is_bool(static::$slowLog) ? static::$slowLog : true;
        static::$errorLog = is_bool(static::$errorLog) ? static::$errorLog : true;
        return array($requestUrl , $headers , $params , $method);
    }
    
    /**
     * 解析桥接日志工具
     * @param string $filePathOrLine 日志文件的路径 或 单行文件的内容
     * @param string $type 传入的类型，line / file
     * @return mixed
     */
    public static function decodeLog($filePathOrLine , $type='line'){
        if($type == 'line'){
            return Log::decodeLine($filePathOrLine);
        }elseif(!file_exists($filePathOrLine)){
            die('file:'.$filePathOrLine.' not found');
        }else{
            $handle = fopen($filePathOrLine , "r");
            $decodeFilePath = $filePathOrLine.'.decode';
            while (!feof($handle)) {
                $line = fgets($handle);
                $len = strlen($line);
                $decodeLine = Log::decodeLine($line);
                if(substr($decodeLine , $len - 1) === "\n"){
                    File::writeFile($decodeFilePath, $decodeLine, 'aw');
                }else{
                    File::writeFile($decodeFilePath, $decodeLine . "\n", 'aw');
                }
            }
            fclose($handle);
        }
    }
    
}


class Request{
    /**
     * 定义大于指定时长的请求为慢请求，设置为0时不写慢查询日志
     */
    const SLOW_TIME_LINE = 1;
    /**
     * 请求发起阶段的几个时间戳参数
     * @var array 
     */
    private $_timestampe = array();
    /**
     * 实际请求地址
     * @var string 
     */
    private $_requestUrl = '';
    /**
     * 请求头参数
     * @var array 
     */
    private $_headers = array();
    /**
     * 请求的内容体
     * @var array
     */
    private $_params = array();
    /**
     * 请求类型，GET/POST
     * @var type 
     */
    private $_method = '';
    /**
     * 是否写调试日志
     * @var boolean
     */
    private $_debugLog = false;
    /**
     * 是否写慢请求日志
     * @var boolean
     */
    private $_slowLog = false;
    /**
     * 请求的响应信息{0:状态 , 1:响应内容主体 ，2:错误信息 ，3:响应头信息}
     * @var array
     */
    private $_responseInfo = array();

    public static function send($requestUrl , $headers , &$params , $method , $debuLog , $slowLog , &$requestInstance){
        $requestInstance = new self($requestUrl , $headers , $params , $method , $debuLog , $slowLog);
        $requestInstance->_before();
        $requestInstance->_send();
        $requestInstance->_after();
    }
    
    /**
     * 参数初始化
     * @param string $requestUrl
     * @param array $headers
     * @param array $params
     * @param string $method
     * @param boolean $debuLog
     * @param boolean $slowLog
     */
    private function __construct($requestUrl , $headers , &$params , $method , $debuLog , $slowLog){
        $this->_timestampe = array();
        $this->_requestUrl = $requestUrl;
        $this->_headers = $headers;
        $this->_params = $params;
        $this->_method = $method;
        $this->_debugLog = $debuLog;
        $this->_slowLog = $slowLog;
        $this->_responseInfo = array();
    }

    /**
     * 请求发起前相关操作
     */
    private function _before(){
        $this->_timestampe['transNo'] = rand(100 , 999).time().rand(100 , 999);
        $this->_timestampe['before'] = microtime(true);
        if($this->_debugLog){//书写调试日志 - 前半部分
            $debugLog  = 'transNo:'.$this->_timestampe['transNo']."\n";
            $debugLog .= 'requestTime:'. round($this->_timestampe['before'], 4)."\n";
            $debugLog .= 'method:'. json_encode($this->_method) ."\n";
            $debugLog .= 'url:'. $this->_requestUrl ."\n";
            $debugLog .= 'header:'. json_encode($this->_headers) ."\n";
            $debugLog .= 'params:'. json_encode($this->_params) ."\n";
            Log::write($debugLog, 'debug');
        }
    }
    
    /**
     * 发起请求
     * @throws ExceptionBrigeBase
     */
    private function _send(){
        //尽量减少写日志的I/O时间影响，重新刷新发送时间戳
        $this->_timestampe['send'] = microtime(true);
        $timeout = 5;
        $this->_responseInfo = ($this->_method == Http::REQUEST_METHOD_get) ? 
                Http::getRequest($this->_requestUrl, $timeout, false, false, $this->_headers, false) :
                Http::postRequest($this->_requestUrl, $this->_params, $timeout, false, false, $this->_headers, false);
        if($this->_responseInfo[0] != 200){
            throw new ExceptionBrigeBase($this->_responseInfo[2] , $this->_responseInfo[0]);
        }
    }
    
    /**
     * 请求发送完毕
     */
    private function _after(){
        $this->_timestampe['after'] = microtime(true);
        $this->_timestampe['use'] = $this->_timestampe['after'] - $this->_timestampe['send'];
        //书写调试日志 - 后半部分
        if($this->_debugLog){
            $debugLog  = 'transNo:'.$this->_timestampe['transNo']."\n";
            $debugLog .= 'responseTime:'. round($this->_timestampe['after'], 4)."\n";
            $debugLog .= 'useTime:'. round($this->_timestampe['use'], 4)." s\n";
            $debugLog .= 'response:'. $this->_responseInfo[1] ." \n";
            Log::write($debugLog, 'debug');
        }
        //写慢请求日志
        if(Request::SLOW_TIME_LINE && ($this->_timestampe['use'] > Request::SLOW_TIME_LINE)){
            $slowLog  = 'requestTime:'. round($this->_timestampe['send'], 4)."\n";
            $slowLog .= 'responseTime:'. round($this->_timestampe['after'], 4)."\n";
            $slowLog .= 'useTime:'. round($this->_timestampe['use'], 4)." s\n";
            $slowLog .= 'method:'. json_encode($this->_method) ."\n";
            $slowLog .= 'url:'. $this->_requestUrl ."\n";
            $slowLog .= 'header:'. json_encode($this->_headers) ."\n";
            $slowLog .= 'params:'. json_encode($this->_params) ."\n";
            $slowLog .= 'response:'. $this->_responseInfo[1] ." \n";
            Log::write($slowLog, 'slow');
        }
    }
    
    /**
     * 外部获取请求参数接口
     * @param string $name
     * @return type
     */
    public function __get($name) {
        $key = '_'.$name;
        return isset($this->$key) ? $this->$key : '';
    }
    
}

/**
 * 解析请求结果类
 */
class Parse{
    /**
     * 
     * @var Request
     */
    private $_request = null;
    /**
     * 
     * @var \Exception
     */
    private $_exception = null;
    /**
     * 
     * @param Request $responseHtml 请求返回主体
     * @param \Exception $exception 异常信息
     * @param boolean $errorLog 是否书写错误日志
     */
    public function __construct(&$requestInstance , \Exception $exception , $errorLog) {
        $this->_request = $requestInstance;
        $this->_exception = $exception;
        if($errorLog && $this->_exception){
            $errorData  = 'errorMessage:'. $this->_exception->getMessage()."\n";
            $errorData .= 'errorNo:'. $this->_exception->getCode()."\n";
            $errorData .= 'requestTime:'. round($this->_request->timestampe['send'], 4)."\n";
            $errorData .= 'responseTime:'. round($this->_request->timestampe['after'], 4)."\n";
            $errorData .= 'useTime:'. round($this->_request->timestampe['use'], 4)." s\n";
            $errorData .= 'method:'. json_encode($this->_request->method) ."\n";
            $errorData .= 'url:'. json_encode($this->_request->requestUrl) ."\n";
            $errorData .= 'header:'. json_encode($this->_request->headers) ."\n";
            $errorData .= 'params:'. json_encode($this->_request->params) ."\n";
            $errorData .= 'response:'. $this->_request->responseInfo[1] ." \n";
            Log::write($errorData, 'error');
        }
    }
    
    /**
     * 获取请求的响应内容
     * @return string
     */
    public function getResponse(){
        return $this->_request->response[1];
    }
    
    /**
     * 获取请求的异常对象
     * @return Exception
     */
    public function getException(){
        return $this->_exception;
    }
    
    /**
     * 获取响应内容格式化后的结果
     * @return string
     */
    public function getFormat(){
        return json_decode($this->_request->response[1]);
    }
    
}


class Log{
    /**
     * 日志加密公钥
     */
    const LOG_PUBLIC_KEY = 'LPK_*&^%$#@:';
    
    public static function write($log , $type){
        $data = '===================='.date('Y-m-d|H:i:s')."====================\n";
        $lines = explode("\n" , $log);
        foreach($lines as $line){
            $data .= self::encodeLine($line)."\n";
        }
        $data .= "\n";
        File::writeFile('F:\tmp\logs\\'.date('Y-m\\').$type.'.txt', $data , 'aw');
    }
    
    
    public static function encodeLine($line){
        $pos = strpos($line , ':');
        if($pos === false){
            return $line;
        }
        $privateKey = substr($line, 0 , $pos);
        $str = substr($line, $pos + 1);
        srand((double)microtime() * 1000000);
        $encrypt_key = md5(rand(0, 32000));
        $ctr = 0;
        $tmp = '';
        $len = strlen($str);
        for($i=0;$i<$len;$i++){
            $ctr=$ctr==strlen($encrypt_key)?0:$ctr;
            $tmp.=$encrypt_key[$ctr].($str[$i] ^ $encrypt_key[$ctr++]);
        }
        return $privateKey.':'.base64_encode(self::passport_key($tmp , self::LOG_PUBLIC_KEY.$privateKey));
    }
    
    
    public static function decodeLine($line){
        $pos = strpos($line , ':');
        if($pos === false || $pos > 20){
            return $line;
        }
        $privateKey = substr($line, 0 , $pos);
        $str = self::passport_key(base64_decode(substr($line, $pos + 1)), self::LOG_PUBLIC_KEY.$privateKey);
        $tmp = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $md5 = $str[$i];
            $tmp.=$str[++$i] ^ $md5;
        }
        return $privateKey.':'.$tmp;
    }
    
    
    private static function passport_key($str, $encrypt_key) {
        $encrypt_key = md5($encrypt_key);
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp.=$str[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }

}


class File{
    
    /**
     * 准备给定的路径(路径不存在则创建)
     * @param string $path
     * @return 实际有效路径
     */
    public static function preparePath($path){
        $path = strtr($path, array('\\' => '/'));
        $pathCut = explode('/', $path);
        $targetPath = '';
        if ($pathCut && is_array($pathCut)) {
            foreach ($pathCut as $part) {
                if ($part) {
                    if ($targetPath) {
                        $targetPath .= DIRECTORY_SEPARATOR . $part;
                    } else {
                        $targetPath = (strpos($part, ':') === false) ? DIRECTORY_SEPARATOR.$part : $part;
                    }
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath);
                        chmod($targetPath, 0755);
                    }
                }
            }
        }
        return $targetPath;
    }
    
    
    /**
     * 将内容写入文件，直接调用fopen\fwrite
     * 
     * @param string $filePath 文件名路径
     * @param string $string 写入的内容
     * @param string $mode 写入模式，与fwrite的参数相同，a/w/r等
     * 
     * @return boolean
     */
    public static function writeFile($filePath , $string , $mode='w'){
        static $fileHandleList = array();
        $fileKey = md5($filePath);
        if(!isset($fileHandleList[$fileKey])){
            self::preparePath(dirname($filePath));
            $fileHandleList[$fileKey] = fopen($filePath , $mode);
        }
        if (fwrite($fileHandleList[$fileKey] , $string) !== false) {
            return true;
        }
        return false;
    }
    
    
}


class ExceptionBrigeBase extends \Exception{
    
    
    
}




