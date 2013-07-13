<?php

namespace Supra\Social\Twitter;

/**
 * 
 */
class TwitterDataProvider 
{
    
    const ACCESS_TOKEN_LINK = 'https://api.twitter.com/oauth/access_token';
    const REQUEST_TOKEN_LINK = 'https://api.twitter.com/oauth/request_token';
    
    const AUTHENTICATE_URL = 'https://api.twitter.com/oauth/authenticate';
    const AUTHORIZE_URL = 'https://api.twitter.com/oauth/authorize';
    
    const REQUEST_TYPE = 'GET';
    const VERSION = '1.0';
    const SIGNATURE_METHOD = 'HMAC-SHA1';
    
    private $consumerKey;
    private $consumerSecret;
    private $token;
    private $tokenSecret;
    
    protected $storage;    
        
    /*
     * @param string $consumerKey
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;
    }
        
    /*
     * @param string $consumerSecret
     */
    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;
    }
    
    /*
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }
        
    /*
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }
    
    
    public function setTokens($token, $tokenSecret)
    {
        $this->token = $token;
        $this->tokenSecret = $tokenSecret;
    }
    
    
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }
    
    
    public function getStorage()
    {
        return $this->storage;
    }
   

    /*
     * @param string $callBack
     * @return array
     */
    public function getRequestToken($callBack) {
        
        $data = array();
        $url = self::REQUEST_TOKEN_LINK;
        
        $parameters = array(
            'oauth_version' => '1.0',
            'oauth_nonce' => $this->generateNonce(),
            'oauth_timestamp' => time(),
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_callback' => $callBack,
            'oauth_signature_method' => 'HMAC-SHA1',
        );
        
        $parameters['oauth_signature'] = $this->buildSignature($url, $parameters);
        $data = $this->executeRequest($url, $parameters);
        
        return $this->parseReturnData($data);
    }
    
    
    /*
     * @param string $oauthVerifier
     * @return array
     */
    public function getAccessToken($oauthVerifier)
    {
        $data = array();        
        $url = self::ACCESS_TOKEN_LINK;
        
        $parameters = array(
            'oauth_version' => self::VERSION,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_timestamp' => time(),
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_signature_method' => self::SIGNATURE_METHOD,
            'oauth_verifier' => $oauthVerifier,
            'oauth_token' => $this->token,
        );
        
        $parameters['oauth_signature'] = $this->buildSignature($url, $parameters);
        $data = $this->executeRequest($url, $parameters);
        
        return $this->parseReturnData($data);
    }
    
    
    /*
     * @param string $token
     * @return string
     */
    public function generateAuthenticationUrl($token)
    {
        return self::AUTHENTICATE_URL . '?oauth_token=' . $token;
    }
    
    
    /* 
     * Parses return data string to array
     * 
     * @param string $data
     * @return array
     */
    private function parseReturnData($data)
    {
        $result = array();
        $parts = explode('&', $data);
        foreach($parts as $part) {
            $split = explode('=', $part, 2);
            $key = $this->urldecodeRfc3986($split[0]);
            $value = $split[1] ? $this->urldecodeRfc3986($split[1]) : '';
            $result[$key] = $value;
        }
        
        return $result;
    }
    
    
    /* 
     * Get data from given URL
     * 
     * @param string $url
     * @param array $urlParameters
     * @return array
     */
    public function get($url, $urlParameters)
    {
        $data = array();
        
        $parameters = array(
            'oauth_version' => self::VERSION,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_timestamp' => time(),
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_signature_method' => self::SIGNATURE_METHOD,
            'oauth_token' => $this->token,
        );
        
        $parameters = array_merge($parameters, $urlParameters);
        
        $parameters['oauth_signature'] = $this->buildSignature($url, $parameters);
        $data = $this->executeRequest($url, $parameters);
        
        return json_decode($data);
    }
    
    
    /* 
     * Executes CURL request
     * 
     * @param string $link
     */
    private function executeRequest($link, $parameters)
    {
        $urlPart = $this->buildHttpQuery($parameters);
        if ($urlPart) {
            $urlPart = '?' . $urlPart;
        }
            
        $url = $link . $urlPart;
        
        $ci = curl_init();
        
        /* Curl settings */
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_HEADER, FALSE);
        curl_setopt($ci, CURLOPT_URL, $url);
        
        $response = curl_exec($ci);

        curl_close ($ci);
        return $response;
    }
    
    
    /* 
     * Sets oauth_signature to parameter array
     * 
     * @param string $link
     */
    private function buildSignature($link, $parameters)
    {
        $signatureBase = $this->getSignatureBaseString($link, $parameters);
        
        $keyParts = array(
            $this->consumerSecret,
            ($this->token) ? $this->tokenSecret : ""
        );

        foreach($keyParts as $keyPart) {
            $data[] = $this->urlencodeRfc3986($keyPart);
        }
        
        $secretKey = implode('&', $data);
        
        return base64_encode(hash_hmac('sha1', $signatureBase, $secretKey, true));
    }
    
    
    /*
     * @param string $link
     * @param array $parameters
     * @return string
     */
    private function getSignatureBaseString($link, $parameters)
    {
        $data = array(
            self::REQUEST_TYPE,
            $link,
            $this->buildHttpQuery($parameters),
        );
        
        foreach($data as &$item) {
            $item = $this->urlencodeRfc3986($item);
        }
        
        return implode('&', $data);
    }
    
    
    /*
     * @param array $parameters
     * @return string
     */
    private function buildHttpQuery($parameters)
    {
        foreach($parameters as $key => $value) {
            $data[$this->urlencodeRfc3986($key)] = $this->urlencodeRfc3986($value);
        }
        
        uksort($data, 'strcmp');
        
        $pairs = array();
        foreach ($data as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }
    
    
    /*
     * @param string $string
     * @return string
     */
    private function urlencodeRfc3986($string)
    {
        return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($string)));
    }
    
    
    /*
     * @param string $string
     * @return string
     */
    private function urldecodeRfc3986($string)
    {
        return urldecode($string);
    }
    
    
    /*
     * @return string
     */
    private function generateNonce()
    {
        return md5(time().mt_rand());
    }
}