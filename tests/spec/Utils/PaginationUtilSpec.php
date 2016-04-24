<?php
namespace spec\Jihe\Utils;

use \PHPUnit_Framework_Assert as Assert;
use PhpSpec\ObjectBehavior;
use Jihe\Utils\PaginationUtil;

class PaginationUtilSpec extends ObjectBehavior
{
    function it_should_generate_valid_page()
    {
        Assert::assertEquals(2, PaginationUtil::genValidPage(2, 20, 4));
        Assert::assertEquals(5, PaginationUtil::genValidPage(100, 20, 4));
    }
}
