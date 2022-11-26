<?php
/**
 * DataTest.php
 *
 * @author libojian <bojian.li@foxmail.com>
 * @since 2022/11/26 7:48 PM
 * @version 0.1
 */

namespace Bojian\Phpunit\tests;
use Bojian\Phpunit\Base\BaseServiceTest;

class DataTest extends BaseServiceTest
{
    /**
     * @dataProvider dataProvider
     * @param $a
     * @param $b
     * @param $result
     * @return void
     */
    public function testAdd($a, $b, $result) {
        self::assertEquals($result, $a + $b);
    }

    /**
     * 提供testAdd测试数据
     * @return int[][]
     */
    public function dataProvider()
    {
        return [
            [1, 2, 3],
            [4, 5, 9]
        ];
    }

    /**
     * 测试数组push断言并返回数组「给下组测试提供数据源」
     * @return array
     */
    public function testArrayPush()
    {
        $array = [];
        array_push($array, 2);
        // 断言：数组包含 2
        self::assertContains(2, $array);

        return $array;
    }


    /**
     * 执行数组pop先拿到testArrayPush数据
     * @depends testArrayPush
     *
     * @param $array
     * @return void
     */
    public function testArrayPop($array)
    {
        array_pop($array);
        $this->assertCount(0, $array);
    }

}
