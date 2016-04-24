<?php
namespace Jihe\Services;

use Jihe\Exceptions\SignatureException;

/**
 * This class provides services for request signature verification
 */
class SignatureService
{
    /**
     * name for signature in the request
     * @var string
     */
    private $param;
    
    /**
     * the key use to verify the request or sign the response
     * @var string
     */
    private $key;
    
    /**
     * method used to verify the request or sign the response
     * @var string
     */
    private $method;

    /**
     * name for the key to be added in order to verify the signature
     * @var string
     */
    private $name;
    
    public function __construct(array $config)
    {
        $this->param  = array_get($config, 'param', 'sign');
        $this->key    = array_get($config, 'key');
        $this->name   = array_get($config, 'name', '__KEY__');
        $this->method = array_get($config, 'method', 'sha512');
    }
    
    /**
     * check whether given 
     * 
     * @param array $params      data to be signed and compared with given signature
     * @param string $signature  signature to be compared with. If not provided, signature will be extract from $params array
     * @param array $options     options to sign
     *                           - check    true to check that key should no be passed to be signed
     * 
     * @return boolean           true if verification is passed. false otherwise
     */
    public function verify(array $params, $signature = null, array $options = [])
    {
        // filter array key, which not used for signature
        $params = array_where($params, function($key, $value) {
            return ! is_array($value);
        });

        // try finding signature from $params
        if (array_has($params, $this->param)) {
            $signature = array_get($params, $this->param);
            unset($params[$this->param]); // remove the signature
        }
        
        if (empty($signature) || $signature != $this->sign($params, $options)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sign the raw data
     * 
     * @param array $plain    plain data to be signed
     * @param array $options  options to sign
     *                        - check    true to check that key should no be passed to be signed
     * 
     * @return string         signature
     */
    public function sign(array $plain, array $options = [])
    {
        $check = array_get($options, 'check', true);
        
        return openssl_digest($this->morphArrayToString($plain, $check), $this->method);
    }
    
    /**
     * morph given value to string for signing
     *
     * @param array $plain
     * @param boolean $check        true to check that key should be passed to be signed
     *
     * @throws SignatureException
     *
     * @return string               string prepared for signing
     */
    private function morphArrayToString(array $plain, $check = true)
    {
        if ($check && array_key_exists($this->name, $plain)) {
            throw new SignatureException('key is not allowed to be passed for signature.');
        }
        
        ksort($plain);
        
        return $this->name . '=' . $this->key . '&' . http_build_query($plain);
    }
    
}
