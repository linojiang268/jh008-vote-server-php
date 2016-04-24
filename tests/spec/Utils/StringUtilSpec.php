<?php
namespace spec\Jihe\Utils;

use PhpSpec\ObjectBehavior;
use Jihe\Utils\StringUtil;
use \PHPUnit_Framework_Assert as Assert;

class StringUtilSpec extends ObjectBehavior
{
    function it_should_generate_random_text_in_exactly_length()
    {
        Assert::assertEquals(6, strlen(StringUtil::quickRandom(6)));
        Assert::assertEquals(10, strlen(StringUtil::quickRandom(10)));
    }
    
    function it_should_generate_random_text_even_pool_is_not_long_enough()
    {
        Assert::assertEquals('111111', StringUtil::quickRandom(6, '1'));
        Assert::assertEquals(6, strlen(StringUtil::quickRandom(6, '123')));
    }
}