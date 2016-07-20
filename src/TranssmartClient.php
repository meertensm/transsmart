<?php 
namespace MCS;

use DateTime;
use Exception;
use MCS\TranssmartException;

class TranssmartClient{
        
    const PRODUCTION_URL = 'https://connect.api.transwise.eu/Api/';
    const TEST_URL = 'https://connect.test.api.transwise.eu/Api/';
    
    private $production = true;
    private $username = null;
    private $password = null;
    
    protected $http_methods = [
        'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'
    ];
    
    public function __construct($username, $password, $production = true)
    {
        if (!isset($username) || !isset($password)) {
            throw new Exception('Missing __construct parameter!');    
        } else {
            $this->username = $username;    
            $this->password = $password;      
            $this->production = (bool) $production;      
        }
    }
    
    public function getUsername()
    {
        return $this->username;    
    }
    
    private function parseJson($json, $array = false)
    {
        $result = json_decode($json, $array);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        return $result;
    }
    
    public function request($http_method, $path, $query_parameters = [], $body = null)
    {
        
        $curl_options = [
            CURLOPT_URL => rtrim(($this->production === true ? self::PRODUCTION_URL : self::TEST_URL) . $path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $http_method,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json', 
                'Authorization: Basic '. base64_encode($this->username . ':' . $this->password)
            ]
        ];
        
        if (!is_null($body)) {
            if (is_object($body) || is_array($body)) {
				if (is_array($body)) {
					if (isset($body['CarrierProfileId'])) {
						unset($body['CarrierId']);		
						unset($body['ServiceLevelTimeId']);								
					}
				} else {
					if (isset($body->CarrierProfileId)) {
						unset($body->CarrierId);		
						unset($body->ServiceLevelTimeId);								
					}
				}
                $body = json_encode($body);    
            }
            $curl_options[CURLOPT_POSTFIELDS] = $body;
        }
        
        if (count($query_parameters) > 0) {
            if (isset($query_parameters['id'])){
                $curl_options[CURLOPT_URL] .= '/' . $query_parameters['id'];
                unset($query_parameters['id']);
            }
        }
        
        if (count($query_parameters) > 0) {
            $curl_options[CURLOPT_URL] .= '?' . http_build_query($query_parameters);    
        }
        
        
        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $json = curl_exec($ch);
        
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            switch ($http_code) {
                case 400:  // Bad request
                case 500:  // Technical error
                    throw new TranssmartException([
                        'response_code' => $http_code,
                        'body' => $json
                    ]);
                    return false;
                    break;
                case 401; // Unauthorized
                    throw new Exception('Unauthorized');
                    return false;
                case 404; // Not found
                    throw new Exception('Not Found');
                    return false;
                    break;
            }
        }
        
        return json_decode($json, true);
  
    }
    
    public function __call($path, $arguments = [])
    {
        
        foreach ($this->http_methods as $method) {
            if (strpos(strtoupper($path), $method) !== false) {
                $http_method = $method;
                $path = str_replace(strtolower($method), '', $path);
                break;
            }
        }
        
        if (!isset($http_method)) {
            throw new Exception('Unknown http method');    
        }
        
        return $this->request(
            $http_method,
            $path,
            isset($arguments[0]) ? $arguments[0] : [],
            isset($arguments[1]) ? $arguments[1] : null
        );
        
    }
}
