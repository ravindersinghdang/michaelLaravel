<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class QuoteController extends Controller
{
    public function index(Request $request){

    	if($request->input("draft_order_id")) {
			try {

				$shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
        		$url = $shopify_url.'/draft_orders/'.$request->input("draft_order_id").'.json';
        		$response = getData($url); 

				if($response && $response["id"]) {
					$tags = ($response["tags"]) ? explode(",", $response["tags"]) : array();
					$tags[] = "quote";

					$order = array(
				        "draft_order" => array(
				        	"id" => $_POST["draft_order_id"],
				            "tags" => implode(",", $tags)
				        )
				    );	


				    $url = $shopify_url.'/draft_orders/'.$request->input("draft_order_id").'.json';
					putData($url, $order);

					$invoice = array(
						"draft_order_invoice" => array(
							"to" => $response["email"],
							"from" => "info@johnsmustang.com",
							"subject" => "Quote ".$response["name"],
							"custom_message" => ""
						)
					);

					$url = $shopify_url.'/draft_orders/'.$request->input("draft_order_id").'/send_invoice.json';
					postData($url, $invoice);

					unset($tags[count($tags) - 1]);

			 		$order = array(
				        "draft_order" => array(
				        	"id" => $_POST["draft_order_id"],
				            "tags" => ($tags && count($tags) > 0) ? implode(",", $tags) : ""
				        )
				    );

				    $url = $shopify_url.'/draft_orders/'.$request->input("draft_order_id").'.json';
					putData($url, $order);

					http_response_code(200);
					echo json_encode(array(
					 "email" => $response["email"],
					 "message" => "Invoice has been sent"
					));
					exit();
				} else {
					http_response_code(400);
					echo json_encode(array("Draft Order not available"));
					exit();
				}
		 	} catch(Exception $e) {
		 		http_response_code(500);
		 		echo json_encode(array($e->getMessage()));
				exit();
		 	}
		} else {
			http_response_code(403);
			echo json_encode(array("Access Denied"));
			exit();
		} 	

    }
}