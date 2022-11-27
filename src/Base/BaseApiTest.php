<?php

/**
 * BaseApiTest.php
 *
 * @author libojian <bojian.li@foxmail.com>
 * @since 2022/11/26 9:46 AM
 * @version 0.1
 */
namespace Bojian\Phpunit\Base;

use PHPUnit\Framework\TestCase;

class BaseApiTest extends TestCase
{
    protected $host;             //域名
    protected $user;             //账号
    protected $password;         //密码
    protected $appId = 5;        //登录appId
    protected $authorization;    //登录token
    protected $isLogin = false;  //是否需要登录 true-是 false-否
    protected $locale = 'zh-cn'; //语言配置

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
        $url = sprintf('%s/%s', $this->host, $path);
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
     * 创建接口文档文件
     * @param string $path
     * @param $fileContent
     * @return false|int
     */
    public function sendApiDocsFile(string $path, $fileContent)
    {
        // 验证路径是否合格
        if (empty($path) || false === strpos($path, '/')) {
            return false;
        }
        // 获取文件夹和文件名称
        [$dirName, $fileName] = explode('/', $path);
        $filePath = __DIR__ . '/' . $path;
        if (false !== strpos($filePath, 'tests/app/api/controller')) {
            $filePath = str_replace("tests/app/api/controller", 'apps/schema', $filePath);
        }

        if (false !== strpos($filePath, '/Base/')) {
            $filePath = str_replace("/Base/", '/schema/', $filePath);
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
            case 'value': //废弃：api请求Key-Value输出
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

    /**
     * 弃用：post请求api
     * @param string $path
     * @param array $params
     * @return false|mixed
     */
    public function OldPost(string $path, array $params = [])
    {
        $url = sprintf('%s%s', $this->host, $path);
        $header = $this->parseSignHeaders();
//        trigger_error('parseSignHeaders：' . json_encode($header), E_USER_NOTICE);

        if (empty($header) && $this->isLogin) {
            return false;
        }

//        trigger_error('postUrl：' . $url, E_USER_NOTICE);
        $request = \Requests::post($url, $header, $params);
        $requestBody = json_decode($request->body, true);
        $errorCode = (int) (isset($requestBody['error_code']) ? $requestBody['error_code'] : 1);
        if (0 !== $errorCode) {
//            trigger_error('postRequestCurlError', E_USER_NOTICE);
            return false;
        }

        return $requestBody;
    }

    /**
     * 弃用：get请求api
     * @param string $path
     * @param array $params
     * @return false|mixed
     */
    public function OldGet(string $path, array $params = [])
    {
        $url = sprintf('%s%s', $this->host, $path);
        !empty($params) && $url = sprintf('%s?%s', $url, http_build_query($params));
        $header = $this->parseSignHeaders();
        trigger_error('parseSignHeaders：' . json_encode($header, JSON_UNESCAPED_UNICODE), E_USER_NOTICE);
        if (empty($header) && $this->isLogin) {
            return false;
        }
        trigger_error('postUrl：' . $url, E_USER_NOTICE);
        $request = \Requests::get($url, $header);
        $requestBody = json_decode($request->body, true);
        $errorCode = (int) (isset($requestBody['error_code']) ? $requestBody['error_code'] : 1);
        trigger_error('getRequestBody：' . json_encode($requestBody, JSON_UNESCAPED_UNICODE), E_USER_NOTICE);
        if (0 !== $errorCode) {
            trigger_error('postRequestCurlError', E_USER_NOTICE);
            return false;
        }

        return $requestBody;
    }

    /**
     * 弃用：获取api请求header
     * @return array
     */
    private function parseSignHeaders(): array
    {
        if ((false === $this->isLogin) || ($this->isLogin && empty($this->authorization))) {
            return [];
        }

        return  [
            'x-client-appid' => $this->appId,
            'x-client-authorization' => $this->authorization,
        ];
    }

}
