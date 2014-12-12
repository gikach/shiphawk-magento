<?php
class Shiphawk_Shipping_IndexController extends Mage_Core_Controller_Front_Action
{
    public function getapiAction() {

        $url_api = 'https://dev.shiphawk.com:443/v1/items/suggest?api_key=6bca0ac384820013803c7be514cb3b75';
        $curl = curl_init();
// Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url_api,

            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'name' => 'value',

            )
        ));
// Send the request & save response to $resp
        $resp = curl_exec($curl);
        //print_r($resp);
// Close request to clear up some resources
        curl_close($curl);


    }
}
