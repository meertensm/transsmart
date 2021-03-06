<?php 
namespace MCS;

use DateTime;
use Exception;

class TranssmartShipment{
        
    private $client;
    
    public $Reference;
    public $AddressName;
    public $AddressStreet;
    public $AddressStreetNo;
    public $AddressZipcode;
    public $AddressCity;
    public $AddressCountry;
    public $AddressContact;
    public $AddressPhone;
    public $AddressEmail;
    
    public $ColliInformation = [];
    
    public function __construct($username, $password, $production = true)
    {
        if (!isset($username) || !isset($password)) {
            throw new Exception('Missing __construct parameter!');    
        } else {
            $this->client = new TranssmartClient(
                $username, $password, (bool) $production
            );      
        }
    }
    
    public function setAddress($array)
    {
        foreach ($array as $property => $value) {
            if (substr($property, 0, 7) != 'Address') {
                $property = 'Address' . $property;       
            }
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }   
    }
    
    public function ship()
    {
        $document = $this->client->postDocument([
            'autobook' => 1,
            'autolabel' => 0
        ], $this);
        
        if (is_array($document)) {
            
            if (isset($document['Status']) && in_array($document['Status'], ['NEW', 'BOOK'])) {
                
                $label_response = $this->client->getDoLabel([
                    'id' => $document['Id'],
                    'username' => $this->client->getUsername(),
                    'pdf' => 1,
                    'downloadonly' => '1'
                ]);  
                
                $response = [
                    'id' => $document['Id'],
                    'tracking_number' => $document['TrackingNumber'],
                    'tracking_url' => $document['TrackingUrl'],
                    'parcels' => [],
                    'labels' => []
                ];
                
                foreach ($document['ColliInformation'] as $parcel) {
                    $response['parcels'][] = [
                        'id' => $parcel['Id'],
                        'awb' => $parcel['TrackingNumber']
                    ];
                }
                
                foreach ($label_response as $label) {
                    $response['labels'][] = [
                        'type' => $label['docType'],
                        'content' => base64_decode($label['tboxPrinterDataExtended'])
                    ];
                }
                
                return $response;
            }
        } 
    }
    
    public function addParcel($array)
    {
        $fields = [
            'PackagingType', 'Quantity', 'Length', 'Width', 'Height', 'Weight'
        ];  
        
        $new_parcel = [];
        
        foreach($array as $key => $value) {
            if (in_array($key, $fields)) {
                if ($key == 'PackagingType') {
                    $value = strtoupper($value);    
                } else if ($key == 'Quantity') {
                    $value = (int) $value;
                } else {
                    $value = (float) $value;    
                }
                $new_parcel[$key] = $value;
            }
        }
        
        $this->ColliInformation[] = $new_parcel;   
    }
    
    public function __set($property, $value)
    {
        switch ($property) {
            case 'Reference':
                $this->Reference = $value;
                break;
            case 'RefOrder':
                $this->RefOrder = $value;
                break;    
            case 'CarrierId':            
            case 'CostCenterId':
            case 'CarrierProfileId':
            case 'ServiceLevelTimeId':
                $this->{$property} = (int) $value;
                break;
            default:
                if (substr($property, -6) == 'Pickup') {
                    $this->{$property} = $value;
                }
                else if (substr($property, 0, 7) != 'Address') {
                    $property = 'Address' . $property;       
                }
                if (property_exists($this, $property)) {
                    $this->{$property} = $value;
                }
        }
    }    
}
