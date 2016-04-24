<?php
namespace spec\Jihe\Services;

use Prophecy\Argument;
use \PHPUnit_Framework_Assert as Assert;
use PhpSpec\ObjectBehavior;

class SignatureServiceSpec extends ObjectBehavior
{
    function let()
    {
        $this->beAnInstanceOf(\Jihe\Services\SignatureService::class, 
                              [ ['param'  => 'sign',
                                 'key'    => '6DSCGCRNHUESHFO6FQ3KF9FSK5DOWQ09',
                                 'name'   => '__KEY__',
                                 'method' => 'sha256'
                              ] ]);
    }

    //===================================
    //            Sign
    //===================================
    function it_signs_regardless_of_its_order()
    {
        $expected = '5147bfff95d31d24fd512411ce9c029c7939321554459555c1fcb94c8ef5bbc3';
        $this->sign([
            'name'  => 'jihe',
            'rank'  => 'high',
        ])->shouldBe($expected);
        
        // change the order and test again
        $this->sign([
            'rank'  => 'high',
            'name'  => 'jihe',
        ])->shouldBe($expected);
    }
    
    function it_rejects_sign_key_passing()
    {
        $this->shouldThrow(\Jihe\Exceptions\SignatureException::class)
             ->duringSign(['__KEY__' => 'whatever']);
    }
    
    //===================================
    //            Verify
    //===================================
    function it_verifies_regardless_of_its_order()
    {
        $this->verify([
            'name'  => 'jihe',
            'rank'  => 'high',
        ], '5147bfff95d31d24fd512411ce9c029c7939321554459555c1fcb94c8ef5bbc3')->shouldBe(true);
        
        $this->verify([
            'rank'  => 'high',
            'name'  => 'jihe',
        ], '5147bfff95d31d24fd512411ce9c029c7939321554459555c1fcb94c8ef5bbc3')->shouldBe(true);
    }
    
    function it_verifies_with_signature_nested()
    {
        $this->verify([
            'name' => 'jihe',
            'rank' => 'high',
            'sign' => '5147bfff95d31d24fd512411ce9c029c7939321554459555c1fcb94c8ef5bbc3',
        ])->shouldBe(true);
    }


    function it_verifies_with_array_params()
    {
        $this->verify([
            'name' => 'jihe',
            'rank' => 'high',
            'tags' => [1, 2, 3],
            'sign' => '5147bfff95d31d24fd512411ce9c029c7939321554459555c1fcb94c8ef5bbc3',
        ])->shouldBe(true);
    }
}
