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
    protected  $host;             //域名
    protected  $user;             //账号
    protected  $password;         //密码
    protected  $appId = 5;        //登录appId
    protected  $authorization;    //登录token
    protected  $isLogin = false;  //是否需要登录 true-是 false-否

    /**
     * post请求api
     * @param string $path
     * @param array $params
     * @return false|mixed
     */
    public function post(string $path, array $params = [])
    {
        $url = sprintf('%s%s', $this->host, $path);
        $header = $this->parseSignHeaders();
        trigger_error('parseSignHeaders：' . json_encode($header), E_USER_NOTICE);

        if (empty($header) && $this->isLogin) {
            return false;
        }

        trigger_error('postUrl：' . $url, E_USER_NOTICE);
        $request = \Requests::post($url, $header, $params);
        $requestBody = json_decode($request->body, true);
        $errorCode = (int) (isset($requestBody['error_code']) ? $requestBody['error_code'] : 1);
        if (0 !== $errorCode) {
            trigger_error('postRequestCurlError', E_USER_NOTICE);
            return false;
        }

        return $requestBody;
    }

    /**
     * get请求api
     * @param string $path
     * @param array $params
     * @return false|mixed
     */
    public function get(string $path, array $params = [])
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
                    $result[$k] = isset($v['value']) ? $v['value'] : '';
                }
                break;

            case 'docs': //接口文档内容输出
                $result = [
                    'type' => 'object',
                    'properties' => array_map(function ($p) {
                        $value = isset($p['value']) ? $p['value'] : '';
                        $description = isset($p['description']) ? $p['description'] : '';
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
     *  获取api请求header
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

    /**
     * 创建日志文件
     * @param string $path
     * @param array $file
     * @return false|int
     */
    public function sendApiDocsFile(string $path, array $file = [])
    {
        $filePath = str_replace("tests/app/api/controller", 'apps/schema/', __DIR__) . $path;

        if (file_exists($filePath)) {
            unlink($filePath);
            trigger_error('unlinkFile:' . $path, E_USER_NOTICE);
        }

        return file_put_contents($filePath, json_encode($file, JSON_UNESCAPED_UNICODE));
    }
}
