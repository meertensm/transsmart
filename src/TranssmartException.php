<?php 
namespace MCS;
 
use Exception;

class TranssmartException extends Exception
{
    
    public function __construct($array, $code = 0, Exception $previous = null) {
        
        if ($this->isJson($array['body'])) {
            $json = json_decode($array['body'], true);
            if (is_array($json)) {
                $message = implode(PHP_EOL, array_unique($json));
            } else {
                $message = $json;    
            }
        } else if (strlen($array['body']) > 0) {
            $message = $array['body'];    
        } else {
            $message = 'An error occured without a description';    
        }
    
        $message = 'Http response code ' . $array['response_code'] . PHP_EOL . $message;
        
        parent::__construct($message, $code, $previous);
        
    }

    public function toHtml()
    {
        return str_replace(PHP_EOL, '<br>', $this->getMessage());   
    }
    
    private function isJson($json)
    {
        $parsed = json_decode($json, true);
        if (is_null($parsed)) {
            return false;    
        } else {
            return true;    
        }
    }
    
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
