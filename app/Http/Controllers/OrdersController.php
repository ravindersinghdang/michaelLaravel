<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return View
     */
    public function index($id){
        if(!$id){
            http_response_code(403);
            echo json_encode(array("Access Denied"));
            exit();
        }

        $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
        $url = $shopify_url.'/orders/'.$id.'.json';      
        $response = getData($url);  
        $dataobject = json_decode($response);
        $data = json_decode(json_encode($dataobject), true);
        return $data;
        exit();
    }

    public function draft_orders($id){
        if(!$id){
            http_response_code(403);
            echo json_encode(array("Access Denied"));
            exit();
        }

        $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
        $url = $shopify_url.'/draft_orders/'.$id.'.json';      
        $response = getData($url);  
        $dataobject = json_decode($response);
        $data = json_decode(json_encode($dataobject), true);
        return $data;
        exit();
    }

    public function create_draft_order(Request $request){        
        $ALLOWED_IPS = Config::get('app.ALLOWED_IPS');

        $data = $request->json()->all();
        if(!$data){
            http_response_code(403);
            echo json_encode(array("Access Denied"));
            exit();
        }
        
        if(in_array($_SERVER['REMOTE_ADDR'], $ALLOWED_IPS)) {
            $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
            $url = $shopify_url.'/draft_orders.json';
            $response = postData($url, $data);
            return $response;
        }else{
            http_response_code(403);
            echo json_encode(array("Access Denied"));
            exit();
        }
    }
    
    public function order_fulfillment(Request $request){
        $data = $request->json()->all();
        $SS_API_KEY = Config::get('app.SS_API_KEY');
        $SS_SECRET_KSY = Config::get('app.SS_SECRET_KSY');
        $SS_API_URL = Config::get('app.SS_API_URL');
        $order_id = $data["order_number"];        

        $results = DB::table('shopify_order_id')->where('shipstation_order_id', $order_id)->get();
        
        if($results && count($results) > 0){            
            foreach ($results as $result) {
                $shipOrderId = $result->shipstation_order_id;
                $authorization = "BASIC ".base64_encode($SS_API_KEY.":".$SS_SECRET_KSY);
                $postUrl = $SS_API_URL."/orders/markasshipped";

                $headers = array(
                    'Content-Type:application/json',
                    'Authorization:'.$authorization
                );

                $data_arr = array(
                    'orderId' => $shipOrderId,
                    'carrierCode' => "other",
                    'notifyCustomer' => false,
                    'notifySalesChannel' => false
                );
               
                try{
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
                    curl_setopt($ch,CURLOPT_URL,$postUrl);
                    curl_setopt($ch,CURLOPT_POST, true);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data_arr));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    
                    curl_close($ch);
                } catch(Exception $e) {

                    file_put_contents("orderFullFillmentError.log", json_encode($e));

                    return false;
                }
            }   
        }
    }

    public function order_update(Request $request){
        $data = $request->json()->all();    
        $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');    
        if(!$data["shipping_address"]) {
            $shipping_address = array(
                "first_name" => $data["customer"]["first_name"],
                "last_name" => ($data["customer"]["last_name"]) ? $data["customer"]["last_name"] : "Default",
                "address1" => "5234 Glenmont Drive",
                "address2" => "",
                "city" => "Houston",
                "country" => 'United States',
                "zip" => "77081",
                "province" => "Texas",
                "phone" => "18008696894"
            );

            $order = array(
                "order" => array(
                    "id" => $data["id"],
                    "shipping_address" => $shipping_address
                )
            );          

            try {
                $response = putData($shopify_url.'/orders/'.$data["id"].'.json', $order);
                http_response_code(200);
            } catch(Exception $e) {
                http_response_code(200);
            }
        }else{
            echo "string";
        }
    }
}