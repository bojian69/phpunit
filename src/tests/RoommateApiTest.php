<?php
/**
 * RoommateApiTest.php
 *
 * @author libojian <bojian.li@foxmail.com>
 * @since 2022/11/26 7:53 PM
 * @version 0.1
 */

namespace Bojian\Phpunit\tests;
use Bojian\Phpunit\Base\BaseApiTest;

class RoommateApiTest extends BaseApiTest
{

    //配置登录信息
    protected $appId = 5;
    protected $authorization = 'bf4261df274495ea03bddfd853467d3b29be0af3';
    protected $isLogin = true;
    protected $host = 'http://ucms-api.bojian.xyz:8888';
//    protected $host = 'https://testapi.uhomes.com/ucms2.0';

    /**
     * 拼室友创建
     * @return false|void
     */
    public function testCreate()
    {
        $params = [
            'unit_id' =>[
                'value' => 2269,
                'description' => '户型Id',
            ],
            'unit_name' =>[
                'value' => 'Classic En-Suite',
                'description' => '户型名称',
            ],
            'expectations' =>  [
                'value' => '发个广告2',
                'description' => '对室友期望',
            ],

        ];

        $result = $this->post('roommate/create', $this->setApiParam($params));
        $this->assertSame(0, $result['error_code'] ?? 1);
        $reqState = $this->sendApiDocsFile('Roommate/getCreateNum.req.json', $this->setApiParam($params, 'docs'));
        $respState = $this->sendApiDocsFile('Roommate/create.resp.json', $result);
        trigger_error('sendApiDocsFileState：' . json_encode([$reqState, $respState]), E_USER_NOTICE);
    }
}
