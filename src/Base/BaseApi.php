<?php

/**
 * BaseApiTest.php
 *
 * @author libojian <bojian.li@foxmail.com>
 * @since 2022/11/26 9:46 AM
 * @version 0.1
 */
namespace Bojian\Phpunit;

use PHPUnit\Framework\TestCase;

class BaseApi extends TestCase
{
    protected $host;             //域名
    protected $user;             //账号
    protected $password;         //密码
    protected $appId = 5;        //登录appId
    protected $authorization;    //登录token
    protected $locale = 'zh-cn'; //语言配置

    /**************************************************
     *  下边内容系统配置-先了解机制再改
     *************************************************/
    //项目名称
    protected $sys = 'api';
    //是否写路由文件
    protected $writeRout = false;
    //控制器
    protected $controller;
    //方法名
    protected $function;
    //接口名称
    protected $apiName;

    /**
     * 配置登录域名信息
     * @return void
     */
    protected function setUp(): void
    {
        $this->host = $_ENV['HOST'] ?? '';
        $this->appId = $_ENV['APP_ID'] ?? '';
        $this->authorization = $_ENV['APP_TOKEN'] ?? '';
    }

    /**
     * POST请求
     * @param string $path
     * @param array $params
     * @param array $headers
     * @return bool|string|void
     */
    public function post(string $path, array $params = [], array $header = [])
    {
        return $this->request('POST', $path, $params, $header);
    }

    /**
     * GET请求
     * @param string $path
     * @param array $params
     * @param array $header
     * @return bool|string|void
     */
    public function get(string $path, array $params = [], array $header = [])
    {
        return $this->request('GET', $path, $params, $header);
    }

    /**
     * api请求基类
     * @param string $method
     * @param string $path
     * @param array $data
     * @param array $headers
     * @return bool|string|void
     */
    private function request(string $method, string $path, array $params = [], array $headers = [])
    {
        // 分割path获取项目，控制器和方法名
        $sysArray = explode('/', $path);
        if (3 === count($sysArray)) {
            [$this->sys, $this->controller, $this->function] = explode('/', $path);
        } else {
            [$this->controller, $this->function] = explode('/', $path);
        }

        // 生成接口文档文件
        $this->writeRout && $this->setRoute($method);

        /**
         * 处理传参信息
         * 支持key-value直传和key-array解析
         */
        $dataStr = '';
        switch ($method) {
            case 'POST':
                if (!empty($params)) {
                    foreach ($params as $k => $v) {
                        $dataStr = $dataStr . sprintf('%s=%s&', $k, rawurlencode(is_array($v) ? ($v['value'] ?? '') : $v));
                    }
                }
                $dataStr = trim(rtrim($dataStr, '&'), '');
                break;
            case 'GET':
                $query = [];
                if (!empty($params)) {
                    foreach ($params as $k => $v) {
                        $query[$k] = is_array($v) ? ($v['value'] ?? '') : $v;
                    }
                }

                !empty($query) && $path = sprintf('%s?%s', $path, http_build_query($query));
                unset($params, $query);
                break;
        }

        /**
         * 处理url
         */
        $curl = curl_init();

        $header = array(
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
            "locale: " . $this->locale,
            "x-client-appid: " . $this->appId,
        );
        !empty($this->authorization) && $header[] = "x-client-authorization: " . $this->authorization;
        if (!empty($headers)) {
            foreach ($headers as $k => $v) {
                $header[] = sprintf('%s: %s', $k, $v);
            }
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $optArr = [
            CURLOPT_URL => sprintf('%s/%s', trim($this->host), trim($path)),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "$method",
        ];
        !empty($dataStr) && $optArr[CURLOPT_POSTFIELDS] = "$dataStr";
        curl_setopt_array($curl, $optArr);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            trigger_error('cURLError：' . $err, E_USER_NOTICE);
            exit('exit.');
        }

        $responseArray = json_decode($response, true);
        $code = $responseArray['error_code'] ?? -1;
        if (0 !== $code) echo $response;
        return $responseArray;
    }

    /**
     * 设置请求路由
     * @param string $method
     * @return bool
     */
    private function setRoute(string $method)
    {
        $apiPath = sprintf('%s/%s/%s', $this->sys, ucfirst($this->controller), $this->function);

        // 获取路由文件
        $filePath = str_replace("vendor/bojian/phpunit/src/Base", 'configs/route.php', __DIR__);
        if (false === strrpos($filePath, 'route.php')) {
            $filePath = str_replace('Base', 'route/route.php', $filePath);
        }

        // 验证路由文件是否存在-存在直接返回
        $fileContent = !file_exists($filePath) ? '' : file_get_contents($filePath);
        if (false !== strrpos($fileContent, $apiPath)) {
            return true;
        }

        // 创建项目层路由
        $this->createSysRoute($filePath);

        // 创建路由组
        $this->createGroupRoute($filePath);

        // 创建api路由
        $this->createApiRoute($filePath, $apiPath, $method);

        return true;
    }

    /**
     * 创建api路由
     * @param $filePath
     * @param $apiPath
     * @return bool
     */
    private function createApiRoute($filePath, $apiPath, $method)
    {
        $fileContent = file_get_contents($filePath);
        $method = strtolower($method);
        $groupRoute = "Route::group('$this->controller', function () {";
        $apiRoute = "Route::$method('$this->function', '$apiPath');";

        if ('api' === $this->sys) {
            $writeContent =  <<<EOF
$groupRoute
    // phpunit::created
    $apiRoute
EOF;
        } else {
            $writeContent =  <<<EOF
    $groupRoute
       // phpunit::created
       $apiRoute
EOF;
        }

        $newContent = str_replace($groupRoute, $writeContent, $fileContent);
        file_put_contents($filePath, $newContent);

        return true;
    }

    /**
     * 创建路由组
     * @param string $filePath
     * @return bool
     */
    private function createGroupRoute(string $filePath)
    {
        $fileContent = file_get_contents($filePath);
        $groupRoute = "Route::group('$this->controller', function () {";

        // 路由组是否存在-存在直接返回
        if (false !== strrpos($fileContent, $groupRoute)) {
            return true;
        }

        // 验证是否为api路由组-api路由组只有一层Route::group
        $sysRoute = "Route::group('$this->sys', function () {";
        $controller = ucfirst($this->controller);
//        if ('api' === $this->sys) {
//            $sysRoute = 'use think\Route;';
//            $writeContent =  <<<EOF
//$sysRoute
//
//// +----------------------------------------------------------------------+
//// | =====================$controller 路由组======================
//// +----------------------------------------------------------------------+
//$groupRoute
//
//});
//EOF;
//        } else {
            $writeContent =  <<<EOF
$sysRoute

   // +----------------------------------------------------------------------+
   // | =====================$controller 路由组======================
   // +----------------------------------------------------------------------+
   $groupRoute
  
   });
EOF;
//        }

        // 字符串替换
        $newContent = str_replace($sysRoute, $writeContent, $fileContent);
        file_put_contents($filePath, $newContent);

        return true;
    }


    /**
     * 创建项目层路由
     * @param string $filePath
     * @return bool
     */
    private function createSysRoute(string $filePath)
    {
        // 判断路由文件是否存在
        if (!file_exists($filePath)) {
            $fileContent = <<<EOF
<?php

use think\Route;



return [
    // 多项目组时首页的跳转
    '/' => 'api/index/index',
    // miss路由
    '__miss__' => 'api/Error/_empty',
];
EOF;
            $dirPath = rtrim(str_replace('route.php', '', $filePath), '/');

            if (!file_exists($dirPath)) {
                mkdir($dirPath,0777,true);
            }

            file_put_contents($filePath, $fileContent);
        }

        // api组不创建路由组文件
        if ('api' === $this->sys) {
            return true;
        }

        $fileContent = file_get_contents($filePath);
        $searchStr = "Route::group('$this->sys', function () {";
        $writeStart = <<<EOF
use think\Route;

Route::group('$this->sys', function () {
EOF;
        $writeEnd = <<<EOF


});
EOF;
        $writeContent = $writeStart . $writeEnd;

        // 判断项目组是否存在
        if (false === strrpos($fileContent, $searchStr)) {
            // 字符串替换
            $newContent = str_replace('use think\Route;', $writeContent, $fileContent);
            file_put_contents($filePath, $newContent);
        }

        return true;
    }

    /**
     * 文件写入方法
     * @param $writeContent
     * @param $filePath
     * @param $k
     * @return void
     */
    private function writeFile($writeContent, $filePath, $key = 21)
    {
        // 读写模式打开
        $fp = fopen($filePath, "r+");
        // 设定写入指针（从哪里开始写）
        fseek($fp, $key);
        // 写入内容
        fwrite($fp, '\n' . $writeContent);
        // 关闭文件
        fclose($fp);
    }

    /**
     * 创建接口文档文件
     * @param $fileContent  文件内容
     * @param $type         格式化类型 docs-初始化接口传参文件
     * @return false|int
     */
    public function sendApiDocsFile($fileContent, $type = '')
    {
        // 验证路径是否合格
        if (empty($this->controller) || empty($this->function)) {
            return false;
        }

        // 验证是否为返回结果 docs：传参内容
        if ('docs' === $type) {
            $fileName = sprintf('%s.req.json', $this->function);
            $fileContent = $this->setApiParam($fileContent, 'docs');
        } else {
            $fileName = sprintf('%s.resp.json', $this->function);
        }

        // 获取文件夹和文件名称
        $filePath = sprintf('%s/%s/%s', __DIR__, ucfirst($this->controller), $fileName);

        if (false !== strpos($filePath, '/Base/')) {
            $filePath = str_replace("/Base/", "/$this->sys/schema/", $filePath);
        }

        if (false !== strpos($filePath, 'vendor/bojian/phpunit/src')) {
            $filePath = str_replace("vendor/bojian/phpunit/src", 'apps', $filePath);
        }

        // 验证文件夹是否存在；否-创建文件夹
        $dirPath = rtrim(str_replace($fileName, '', $filePath), '/');
        if (!file_exists($dirPath)) {
            mkdir($dirPath,0777,true);
        }

        // 验证文件是否存在；是-删除现在文件
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return file_put_contents($filePath, json_encode($fileContent, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 格式化参数
     * @param array $param
     * @param string $type value-将paramKey和value组装 docs-接口文档格式
     * @return array
     */
    public function setApiParam(array $param, string $type = 'value')
    {
        $result = [];
        switch ($type) {
            case 'value': //api请求Key-Value输出
                foreach ($param as $k => $v) {
                    $result[$k] = $v['value'] ?? '';
                }
                break;

            case 'docs': //接口文档内容输出
                $result = [
                    'type' => 'object',
                    'properties' => array_map(function ($p) {
                        $value = $p['value'] ?? '';
                        $description = $p['description'] ?? '';
                        return [
                            'type' => is_int($value) ? 'integer' : 'string',
                            'required' => (bool) (isset($p['required']) ? $p['required'] : true),
                            'description' => sprintf('%s；默认：%s', $description, $value)
                        ];
                    }, $param)
                ];

                break;
        }

        return $result;
    }
}
