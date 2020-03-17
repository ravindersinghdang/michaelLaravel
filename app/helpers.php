<?php 
	if (! function_exists('getData')) {
    	function getData($url) {
        	$curl = curl_init();
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL,$url);
			$result = curl_exec($curl);
			curl_close($curl);
			return $result;
    	}
	}

	if (! function_exists('postData')) {
    	function postData($url,$params) {
        	$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_VERBOSE, 0);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($curl);
			curl_close($curl);
			return $result;
    	}
	}

	if (! function_exists('putData')) {
    	function putData($url,$params) {
        	$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_VERBOSE, 0);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($curl);
			curl_close($curl);
			return $result;
    	}
	}

	if (! function_exists('getDraftOrder')) {
		function getDraftOrder($url, $draft_order_id, $json = false) {
			$response =  getData($url);
			if($json) {
				return $response;
			}

			$dataobject = json_decode($response);
		 	$data = json_decode(json_encode($dataobject), true);

			return $data['draft_order'];
		}
	}

	if(! function_exists('createDraftOrder')){
		function createDraftOrder($params) {
			$shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
			$url = $shopify_url.'/draft_orders.json';
			$response = postData($url, $params);
			return $response;
		}
	}

	if(! function_exists('getProductVariantMetaFields')){
		function getProductVariantMetaFields($product_id,$variant_id) {
			$shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');			
			$url = $shopify_url.'/products/'.$product_id.'/variants/'.$variant_id.'/metafields.json';
			$response =  getData($url);
			if($response['metafields'] && count($response['metafields']) > 0){
				foreach ($response['metafields'] as $key => $data) {
					if($data['namespace'] == "specs" && $data['key'] == "location"){
						$metafields = $data['value'];
					}
				}
				return $metafields;
			}
		}
	}

	if(! function_exists('getProductMetaFields')){
		function getProductMetaFields($product_id) {
			$shopify_url = Config::get('app.SHOPIFY_PRIVATE_API_URL');
			$url = $shopify_url.'/products/'.$product_id.'/metafields.json';
			$response =  getData($url);
			if($response['metafields'] && count($response['metafields']) > 0){
				foreach ($response['metafields'] as $key => $data) {
					if($data['namespace'] == "specs" && $data['key'] == "location"){
						$metafields = $data['value'];
					}
				}
				return $metafields;
			}
		}
	}
?>