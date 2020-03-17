<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
class CheckoutController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return View
     */
    public function index($token){
        if(!$token){
            http_response_code(403);
            echo json_encode(array("Access Denied"));
            exit();
        }

        $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
        $url = $shopify_url.'/checkouts/'.$token.'.json';      
        $response = getData($url);  
        $dataobject = json_decode($response);
        $data = json_decode(json_encode($dataobject), true);
        return $data;
        exit();
    }

    public function checkouts(){
        $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
        $url = $shopify_url.'/checkouts.json?limit=250';
        return getData($url);
    }
}