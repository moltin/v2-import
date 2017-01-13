<?php

$clientId = '';
$secret = '';

// Authenticate
$accessToken = getAccessToken($clientId, $secret);

// ---
// TV data
// ---

$cat1Products = json_decode(file_get_contents('./products-tv.json'), true);
$cat1Data = '{"data":{"name":"TV","slug":"tv"}}';
$cat1 = json_decode(postJSON('https://api.moltin.com/v2/categories', $accessToken, json_decode($cat1Data)));

// Insert TVs
foreach($cat1Products as $product) {
	// Create product
	$returnedProduct = postJSON('https://api.moltin.com/v2/products', $accessToken, $product);
	$returnedProduct = json_decode($returnedProduct);

	// Attach to category
	$categoryReq['data'][0] = new stdClass();
	$categoryReq['data'][0]->type = "category";
	$categoryReq['data'][0]->id = $cat1->data->id;
	$returnedCategory = postJSON('https://api.moltin.com/v2/products/'.$returnedProduct->data->id."/relationships/categories", $accessToken, $categoryReq);
}

// ---
// Sound System Data
// ---

$cat2Products = json_decode(file_get_contents('./products-sound.json'), true);
$cat2Data = '{"data":{"name":"Sound Systems","slug":"sound-systems"}}';
$cat2 = json_decode(postJSON('https://api.moltin.com/v2/categories', $accessToken, json_decode($cat2Data)));

// Insert Sound Systems
foreach($cat2Products as $product) {
	$returnedProduct = postJSON('https://api.moltin.com/v2/products', $accessToken, $product);
	$returnedProduct = json_decode($returnedProduct);

	$categoryReq['data'][0] = new stdClass();
	$categoryReq['data'][0]->type = "category";
	$categoryReq['data'][0]->id = $cat2->data->id;
	$returnedCategory = postJSON('https://api.moltin.com/v2/products/'.$returnedProduct->data->id."/relationships/categories", $accessToken, $categoryReq);
}


function getAccessToken($clientId, $secret) {
	$ch = curl_init('https://api.moltin.com/oauth/access_token');
	curl_setopt($ch, CURLOPT_POST, count(3));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&client_id={$clientId}&client_secret={$secret}");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
	$result = curl_exec($ch);
	$info = curl_getinfo($ch);

	if ($info['http_code'] !== 200) {
		die("Could not authenticate. Please check your credentials.");
	}

	curl_close($ch);
	$result = json_decode($result);
	return $result->access_token;
}

function postJSON($url, $accessToken, $data){
	$data = json_encode($data);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer ' . $accessToken
	));
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}