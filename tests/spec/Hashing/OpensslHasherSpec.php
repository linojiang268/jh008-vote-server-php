<?php
namespace spec\Jihe\Hashing;

use PhpSpec\ObjectBehavior;

/**
 * Q: How to generate private/public RSA key in .pem format?
 * A: # private key
 *    openssl genrsa -out privkey.pem 2048
 *     
 *    # public key
 *    openssl rsa -in privkey.pem -pubout -out pubkey.pem
 *
 */
class OpensslHasherSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(\Jihe\Hashing\OpensslHasher::class);
    }
    
    function it_makes_signature()
    {
        $this->make('secret', ['private_key' => __DIR__ . '/test-data/openssl_privatekey' ])
             ->shouldBe('FfjhRFbL+oplucjLcLkC5EWNF6hAS9+HlGZBu2rZ891CUDNVUhf8jeRdPbnrRyoBafJzt5hD8cRKdOBrS3pB+Mtail/9/7zLOqw7G2gaw8wkSINKlHua6xwqfJq5Il+mKxGbZ3qNAKfT2OGm8H03K8MVONRCgYrEDoreojY70rvUgJ9vFohF5VN3s1ypX6LunJP9B2GsY/We/zPt5ixtZcPA7RM7W+rHxigb3WxU/rjSWzXHqUOhv3srZNXPRUXDdzofbX8bnX76mEiHesCrUvhpJjeJ/3AeSvssVX0aPX1zYfXkLzOlD+rOn7bwJuXlKPcB9VxFf4robkC6eG1Aug==');
    }
    
    function it_checks_signature()
    {
        $this->make('secret', ['private_key' => __DIR__ . '/test-data/openssl_privatekey' ])
             ->shouldBe('FfjhRFbL+oplucjLcLkC5EWNF6hAS9+HlGZBu2rZ891CUDNVUhf8jeRdPbnrRyoBafJzt5hD8cRKdOBrS3pB+Mtail/9/7zLOqw7G2gaw8wkSINKlHua6xwqfJq5Il+mKxGbZ3qNAKfT2OGm8H03K8MVONRCgYrEDoreojY70rvUgJ9vFohF5VN3s1ypX6LunJP9B2GsY/We/zPt5ixtZcPA7RM7W+rHxigb3WxU/rjSWzXHqUOhv3srZNXPRUXDdzofbX8bnX76mEiHesCrUvhpJjeJ/3AeSvssVX0aPX1zYfXkLzOlD+rOn7bwJuXlKPcB9VxFf4robkC6eG1Aug==');
    }
    
}