<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class getorders extends Controller
{

    public function index($id){    	
    	
    	function getData($url) {
    		try {
    		$curl = curl_init();
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL,$url);
			$result = curl_exec($curl);			
			curl_close($curl);
			return $result;
    		} catch (Exception $e) {
    			$e->$message;    			
    		}			
		}

    	function getOrder($order_id, $json = false) {
              
    		$suri= Config::get('app.Shopifyuri');

		    $url = $suri.'/orders/'.$order_id.'.json';			          
			$response = getData($url);			
			if($json) {
				return $response;
			}
			$dataobject = json_decode($response);
		 	$data = json_decode(json_encode($dataobject), true);
			return $data['order'];
		}      
        $orderdata = getOrder($id);        
        echo json_encode($orderdata);
          // its order id for test perpose 1915811135582
        exit();
    }
}
