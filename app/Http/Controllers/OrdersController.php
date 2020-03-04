<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
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
    
}