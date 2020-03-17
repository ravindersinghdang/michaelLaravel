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

        $results = DB::table('orders')->where('shipstation_order_id', $order_id)->get();
        
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
        }
    }

    public function create_order(Request $request){
        $order = $request->json()->all();    

        $SS_API_KEY = Config::get('app.SS_API_KEY');
        $SS_SECRET_KSY = Config::get('app.SS_SECRET_KSY');
        $SS_API_URL = Config::get('app.SS_API_URL');

        $authorization = "BASIC ".base64_encode($SS_API_KEY.":".$SS_SECRET_KSY);
        $postUrl = $SS_API_URL."/orders/createorder";

        $headers = array(
            'Content-Type:application/json',
            'Authorization:'.$authorization
        );

        $line_item_arr = array();
        if($order['line_items'] && count($order['line_items']) > 0){
            foreach ($order['line_items'] as $key => $lvalue) {
                $metafields =  getProductVariantMetaFields($lvalue['product_id'],$lvalue['variant_id']);
                
                if($metafields == ""){
                    $metafields =  getProductMetaFields($lvalue['product_id']); 
                }
                
                $line_item_arr[$key]['sku'] = $lvalue['sku'];
                $line_item_arr[$key]['name'] = $lvalue['title'];
                $line_item_arr[$key]['quantity'] = $lvalue['quantity'];
                $line_item_arr[$key]['unitPrice'] = $lvalue['price'];
                $line_item_arr[$key]['warehouseLocation'] = $metafields;
            }
        }

        $data_arr = array(
            "orderNumber"=>$order['id'], 
            "orderKey"=> null, 
            "orderDate"=>$order['created_at'],
            "paymentDate"=>null,
            "shipByDate"=>null,
            "orderStatus"=>'awaiting_payment',
            "customerUsername"=>$order['customer']['first_name'],
            "customerEmail"=>$order['customer']['email'],
            "billTo"=> array(
                "name"=>$order['billing_address']['name'],
                "company"=>$order['billing_address']['company'],
                "street1"=>$order['billing_address']['address1'],
                "street2"=>$order['billing_address']['address2'],
                "street3"=>null,
                "city"=>$order['billing_address']['city'],
                "state"=>$order['billing_address']['province'],
                "postalCode"=>$order['billing_address']['zip'],
                "country"=>$order['billing_address']['country_code'],
                "phone"=>$order['billing_address']['phone'],
                "residential"=>'',
            ),
            "shipTo"=> array(
                "name"=>$order['shipping_address']['name'],
                "company"=>$order['shipping_address']['company'],
                "street1"=>$order['shipping_address']['address1'],
                "street2"=>$order['shipping_address']['address2'],
                "street3"=>null,
                "city"=>$order['shipping_address']['city'],
                "state"=>$order['shipping_address']['province'],
                "postalCode"=>$order['shipping_address']['zip'],
                "country"=>$order['shipping_address']['country_code'],
                "phone"=>$order['shipping_address']['phone'],
                "residential"=>null,
            ),

            "items"=> $line_item_arr,
            "amountPaid" => $order['subtotal_price'],
            "taxAmount" => $order['total_tax'],
            "customerNotes" => $order['customer']['note'],
            "internalNotes" => '',
            "gift"=>null,
            "giftMessage"=>null,
            "paymentMethod"=>null,
            "requestedShippingService"=>null,
            "carrierCode"=>null,
            "serviceCode"=>null,
            "packageCode"=>null,
            "confirmation"=>null,
            "shipDate"=>null,
            "advancedOptions"=>array(
                'storeId' => SS_STORE_ID,
            ),
            "tagIds"=>array(SS_TAG_NUMBER),
        );

        try {
            $results = DB::table('orders')->where('shipstation_order_id',  $order['id'])->first();
            if($results){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
                curl_setopt($ch,CURLOPT_URL,$postUrl);
                curl_setopt($ch,CURLOPT_POST, true);
                curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data_arr));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $jsonresponse = curl_exec($ch);

                curl_close($ch);
                $response = json_decode($jsonresponse); 
                if($response) {
                    $id = DB::table('orders')->insertGetId(
                        array('shopify_order_id' => $order['id'], 'shipstation_order_id' => $response->orderId, 'shipstation_order_key' => $response->orderKey, 'created_date' => date("Y-m-d H:i:s"));
                    );
                }
    
            }
        } catch(Exception $e) {
            
        }
    }

    public function delete_order(Request $request){
        $order = $request->json()->all();    
        $results = DB::table('orders')->where('shopify_order_id', $order['id'])->get();

        if($results){
            $orderId = $results->shipstation_order_id;
        }

        $SS_API_KEY = Config::get('app.SS_API_KEY');
        $SS_SECRET_KSY = Config::get('app.SS_SECRET_KSY');
        $SS_API_URL = Config::get('app.SS_API_URL');

        $authorization = "BASIC ".base64_encode($SS_API_KEY.":".$SS_SECRET_KSY);
        $postUrl = $SS_API_URL."/orders/".$orderId;

        $headers = array(
            'Content-Type:application/json',
            'Authorization:'.$authorization
        );
       
        try {
          $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_URL,$postUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            curl_close($ch);
            $response = json_decode($response); 

            if($response){
                DB::table('orders')->where('shopify_order_id', '=', $order['id'])->delete();
                file_put_contents('response_delete.log', json_encode($response));
            }
        } catch(Exception $e) {
            print_r($e);
            return false;
        }
    }

    public function update_order(Request $request){
        $order = $request->json()->all();    

        $SS_API_KEY = Config::get('app.SS_API_KEY');
        $SS_SECRET_KSY = Config::get('app.SS_SECRET_KSY');
        $SS_API_URL = Config::get('app.SS_API_URL');

        $results = DB::table('orders')->where('shopify_order_id', $order['id'])->get();

        if($results){
            $orderId = $results->shipstation_order_id;
        }

        $authorization = "BASIC ".base64_encode($SS_API_KEY.":".$SS_SECRET_KSY);
        $postUrl = $SS_API_URL."/orders/createorder";

        $headers = array(
            'Content-Type:application/json',
            'Authorization:'.$authorization
        );

        $line_item_arr = array();
        if($order['line_items'] && count($order['line_items']) > 0){
            foreach ($order['line_items'] as $key => $lvalue) {
                $metafields =  getProductVariantMetaFields($lvalue['product_id'],$lvalue['variant_id']);
                
                if($metafields == ""){
                    $metafields =  getProductMetaFields($lvalue['product_id']); 
                }
                
                $line_item_arr[$key]['sku'] = $lvalue['sku'];
                $line_item_arr[$key]['name'] = $lvalue['title'];
                $line_item_arr[$key]['quantity'] = $lvalue['quantity'];
                $line_item_arr[$key]['unitPrice'] = $lvalue['price'];
                $line_item_arr[$key]['warehouseLocation'] = $metafields;
            }
        }

        $data_arr = array(
            "orderNumber"=>$order['id'], 
            "orderKey"=> null, 
            "orderDate"=>$order['created_at'],
            "paymentDate"=>null,
            "shipByDate"=>null,
            "orderStatus"=>'awaiting_payment',
            "customerUsername"=>$order['customer']['first_name'],
            "customerEmail"=>$order['customer']['email'],
            "billTo"=> array(
                "name"=>$order['billing_address']['name'],
                "company"=>$order['billing_address']['company'],
                "street1"=>$order['billing_address']['address1'],
                "street2"=>$order['billing_address']['address2'],
                "street3"=>null,
                "city"=>$order['billing_address']['city'],
                "state"=>$order['billing_address']['province'],
                "postalCode"=>$order['billing_address']['zip'],
                "country"=>$order['billing_address']['country_code'],
                "phone"=>$order['billing_address']['phone'],
                "residential"=>'',
            ),
            "shipTo"=> array(
                "name"=>$order['shipping_address']['name'],
                "company"=>$order['shipping_address']['company'],
                "street1"=>$order['shipping_address']['address1'],
                "street2"=>$order['shipping_address']['address2'],
                "street3"=>null,
                "city"=>$order['shipping_address']['city'],
                "state"=>$order['shipping_address']['province'],
                "postalCode"=>$order['shipping_address']['zip'],
                "country"=>$order['shipping_address']['country_code'],
                "phone"=>$order['shipping_address']['phone'],
                "residential"=>null,
            ),

            "items"=> $line_item_arr,
            "amountPaid" => $order['subtotal_price'],
            "taxAmount" => $order['total_tax'],
            "customerNotes" => $order['customer']['note'],
            "internalNotes" => '',
            "gift"=>null,
            "giftMessage"=>null,
            "paymentMethod"=>null,
            "requestedShippingService"=>null,
            "carrierCode"=>null,
            "serviceCode"=>null,
            "packageCode"=>null,
            "confirmation"=>null,
            "shipDate"=>null,
            "advancedOptions"=>array(
                'storeId' => SS_STORE_ID,
            ),
            "tagIds"=>array(SS_TAG_NUMBER),
        );

        try {
            $results = DB::table('orders')->where('shipstation_order_id',  $order['id'])->first();
            if($results){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
                curl_setopt($ch,CURLOPT_URL,$postUrl);
                curl_setopt($ch,CURLOPT_POST, true);
                curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data_arr));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $jsonresponse = curl_exec($ch);

                curl_close($ch);
                $response = json_decode($jsonresponse); 
                if($response) {
                    $id = DB::table('orders')->insertGetId(
                        array('shopify_order_id' => $order['id'], 'shipstation_order_id' => $response->orderId, 'shipstation_order_key' => $response->orderKey, 'created_date' => date("Y-m-d H:i:s"));
                    );
                }
    
            }
        } catch(Exception $e) {
            
        }
    }
}