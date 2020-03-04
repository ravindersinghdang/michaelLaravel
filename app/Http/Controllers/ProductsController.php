<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
class ProductsController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return View
     */
    public function index($product_id){
        if(!$product_id){
            http_response_code(403);
            echo json_encode(array("Access Denied"));
            exit();
        }

        $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
        $url = $shopify_url.'/products/'.$product_id.'/metafields.json';
        $response = getData($url);          
        return $response;
        exit();
    }


    public function variant_metafields($product_id,$variant_id){
        if(!$product_id){
            http_response_code(403);
            echo json_encode(array("Access Denied"));
            exit();
        }

        $shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
        $url = $shopify_url.'/products/'.$product_id.'/variants/'.$variant_id.'/metafields.json';
        $response = getData($url);          
        return $response;
        exit();
    }
    
}