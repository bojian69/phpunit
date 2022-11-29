<?php
/**
 * DataTest.php
 *
 * @author libojian <bojian.li@foxmail.com>
 * @since 2022/11/26 7:48 PM
 * @version 0.1
 */

namespace Bojian\Phpunit\tests;

use Bojian\Phpunit\BaseService;
use PHPUnit\Framework\InvalidArgumentException;

class DemoTest extends BaseService
{

    /********************************************************************************
     * depends 标注来表示测试方法之间的依赖关系。
     ********************************************************************************/

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

    /**
     * 多个依赖项-回归代码多种情况
     */
    public function testProducerFirst(): string
    {
        $this->assertTrue(true);

        return 'first';
    }

    public function testProducerSecond(): string
    {
        $this->assertTrue(true);

        return 'second';
    }

    /**
     * @depends testProducerFirst
     * @depends testProducerSecond
     */
    public function testConsumer(string $a, string $b): void
    {
        $this->assertSame('first', $a);
        $this->assertSame('second', $b);
    }


    /********************************************************************************
     * dataProvider 提供测试数据
     ********************************************************************************/

    /**
     * 数组：提供testAdd测试数据
     * @return int[][]
     */
    public function dataProvider(): array
    {
        return [
            [1, 2, 3],
            [4, 5, 9]
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAdd($a, $b, $result): void
    {
        self::assertEquals($result, $a + $b);
    }

    /**
     * Iterator：提供testAddition测试数据 PS:基本不会用不做深入研究
     * @dataProvider additionProvider
     */
    public function testAddition(int $a, int $b, int $expected): void
    {
        $this->assertSame($expected, $a + $b);
    }
    public function additionProvider(): CsvFileIterator
    {
        return new CsvFileIterator('data.csv');
    }

    /********************************************************************************
     * depends & dataProvider 混合使用
     ********************************************************************************/
    public function provider(): array
    {
        return [['provider1'], ['provider2']];
    }

    /**
     * @depends testProducerFirst
     * @depends testProducerSecond
     * @dataProvider provider
     */
    public function testConsumerSecond(): void
    {
        $this->assertSame(
            ['provider1', 'first', 'second'],
            func_get_args()
        );
    }

    /********************************************************************************
     * expectException 异常进测试
     ********************************************************************************/
    public function testException(): void
    {
        $this->expectException(InvalidArgumentException::class);
    }

}
