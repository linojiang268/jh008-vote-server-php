<?php
namespace spec\Jihe\Hashing;

use PhpSpec\ObjectBehavior;

class PasswordHasherSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(\Jihe\Hashing\PasswordHasher::class);
    }
    
    //=================================
    //      make hashing
    //=================================
    function it_does_hashing_without_salt()
    {
        // although this is not recommended, we can hash something without salt
        $this->make('secret')
             ->shouldBe('44C7BE48226EBAD5DCA8216674CAD62B');
    }
    
    function it_should_produce_different_hashing_results_for_different_salts()
    {
        $this->make('secret', ['salt' => 'GptAw7'])
             ->shouldBe('A59B935682DC7AAE08904DD3EB6DCF6E');
        
        $this->make('secret', ['salt' => 'gjoTM3'])
             ->shouldBe('D14A8FE93CD332A7B79CA4EBD1F12B8C');
    }
    
    //=================================
    //      check hashed value
    //=================================
    function it_checks_hashed_value_without_salt()
    {
        // although this is not recommended, we can check hashed value without salt
        $this->check('secret', '44C7BE48226EBAD5DCA8216674CAD62B')
             ->shouldBe(true);
    }
    
    function it_checks_hashed_value_with_salt()
    {
        $this->check('secret', 'A59B935682DC7AAE08904DD3EB6DCF6E', ['salt' => 'GptAw7'])
             ->shouldBe(true);
        
        $this->check('secret', 'D14A8FE93CD332A7B79CA4EBD1F12B8C', ['salt' => 'gjoTM3'])
             ->shouldBe(true);
    }
}