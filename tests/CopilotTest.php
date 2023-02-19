<?php
/**
 * CopilotTest.php
 *
 * @author libojian <bojian.li@foxmail.com>
 * @since 2023/2/19 6:03 PM
 * @version 0.1
 */

namespace Bojian\Phpunit\tests;

use Bojian\Phpunit\BaseService;
class CopilotTest extends BaseService
{
    private array $array;
    public function setUp(): void
    {
        parent::setUp();
        $this->array = [1, 2, 6, 4, 5];
    }

     // 测试quickSort
    public function testQuickSort() {
        $result = $this->quickSort($this->array);
        $this->assertEquals([1, 2, 4, 5, 6], $result);
    }

    // 使用快速排序对数组进行排序
    protected function quickSort(array $arr): array
    {
        $length = count($arr);
        if ($length <= 1) {
            return $arr;
        }
        $baseNum = $arr[0];
        $leftArray = [];
        $rightArray = [];
        for ($i = 1; $i < $length; $i++) {
            if ($baseNum > $arr[$i]) {
                $leftArray[] = $arr[$i];
            } else {
                $rightArray[] = $arr[$i];
            }
        }
        $leftArray = $this->quickSort($leftArray);
        $rightArray = $this->quickSort($rightArray);
        return array_merge($leftArray, [$baseNum], $rightArray);
    }


    // 测试冒泡排序
    public function testBubbleSort() {
        $arr = [1, 3, 2, 4, 22, 6, 7, 8, 9, 10];
        $result = $this->bubbleSort($arr);
        $this->assertEquals([1, 2, 3, 4, 6, 7, 8, 9, 10,22], $result);
    }

    // 冒泡排序
    protected function bubbleSort(array $arr): array
    {
        $length = count($arr);
        for ($i = 0; $i < $length; $i++) {
            for ($j = $i + 1; $j < $length; $j++) {
                if ($arr[$i] > $arr[$j]) {
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }

    // 测试选择排序
    public function testSelectSort() {
        $arr = [1, 3, 2, 4, 22, 6, 7, 8, 9, 10];
        $result = $this->selectSort($arr);
        $this->assertEquals([1, 2, 3, 4, 6, 7, 8, 9, 10,22], $result);
    }

    // 选择排序
    protected function selectSort(array $arr): array
    {
        $length = count($arr);
        for ($i = 0; $i < $length; $i++) {
            $p = $i;
            for ($j = $i + 1; $j < $length; $j++) {
                if ($arr[$p] > $arr[$j]) {
                    $p = $j;
                }
            }
            if ($p != $i) {
                $tmp = $arr[$i];
                $arr[$i] = $arr[$p];
                $arr[$p] = $tmp;
            }
        }
        return $arr;
    }

    // 测试插入排序
    public function testInsertSort() {
        $arr = [1, 3, 2, 4, 22, 6, 7, 8, 9, 10];
        $result = $this->insertSort($arr);
        $this->assertEquals([1, 2, 3, 4, 6, 7, 8, 9, 10,22], $result);
    }

    // 插入排序
    protected function insertSort(array $arr): array
    {
        $length = count($arr);
        for ($i = 1; $i < $length; $i++) {
            $tmp = $arr[$i];
            for ($j = $i - 1; $j >= 0; $j--) {
                if ($tmp < $arr[$j]) {
                    $arr[$j + 1] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }

    public function languageProvider()
    {
        $cn = '中文：你好，我是协同助手，我可以帮助你写代码。';
        $en = 'English: Hello, I am Copilot, I can help you write code.';
        $fr = 'French: Bonjour, je suis Copilot, je peux vous aider à écrire du code.';
        $de = 'German: Hallo, ich bin Copilot, ich kann dir beim Schreiben von Code helfen.';
        $es = 'Spanish: Hola, soy Copilot, puedo ayudarte a escribir código.';
        $ja = 'Japanese: こんにちは、私はコピロットです、コードを書くのを手伝ってくれます。';
        $ko = 'Korean: 안녕하세요, 저는 Copilot입니다. 코드를 작성하는 데 도움을 줄 수 있습니다.';
        $ru = 'Russian: Привет, я Copilot, я могу помочь вам написать код.';
        $pt = 'Portuguese: Olá, sou o Copilot, posso ajudá-lo a escrever código.';
        return [
            ['en'],
            ['fr'],
        ];
    }
}
