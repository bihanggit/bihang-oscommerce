<?php

require 'oklink/Oklink.php';
require 'includes/application_top.php';

$client = OklinkSDK::withApiKey(MODULE_PAYMENT_OKLINK_APIKEY, MODULE_PAYMENT_OKLINK_APISECRET);

if ( $client->checkCallback() ){
    $order =  json_decode(file_get_contents("php://input"));
    if( $order->status === 'completed'){
        if(function_exists('tep_db_query')){
            tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . MODULE_PAYMENT_OKLINK_PAID_STATUS_ID . " where orders_id = " . intval($order->custom));
        }        
    }
    header('HTTP/1.0 200 OK');
}
