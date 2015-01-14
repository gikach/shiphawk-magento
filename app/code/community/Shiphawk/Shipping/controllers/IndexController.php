<?php
class Shiphawk_Shipping_IndexController extends Mage_Core_Controller_Front_Action
{
    public function trackingAction() {

        $data_from_shiphawk = $this->getRequest()->getPost();

        $api_key_from_url = $this->getRequest()->getParam('api_key');

        $data_from_shiphawk = $this->getRequest()->getPost();

        /*
         *  {
 "shipment_id":"1007505",
 "status": "ordered",
 "status_updates": [
 {
 "timestamp": "2014-07-25T09:07:59.514-07:00",
 "message": "booked"
 },
 {
 "timestamp": "2014-07-25T09:12:51.278-07:00",
 "message": "ordered"
        */
        $api_key = Mage::helper('shiphawk_shipping')->getApiKey();

        //TODO проверка на апи ключ
        /*if($api_key_from_url == $api_key) {
                //TODO save post data to shipment comment
        }*/

        echo $api_key;
    }

    public function testAction() {

//North America
        date_default_timezone_set('MST');

//        echo date('Y-m-d', strtotime($myDate . ' +1 Weekday'));

        echo date('Y-m-d', strtotime('now +1 Weekday'));

        echo '<br/>';

        echo $date = date('Y-m-d h:i:s a', time());

        //'start_time' => '2015-07-27T04:51:36.645-07:00',
        //'end_time' => '2015-07-27T07:51:36.645-07:00',

    }


    /* suggest items type in product page */
    public function searchAction() {

        $search_tag = trim(strip_tags($this->getRequest()->getPost('search_tag')));
        $api_key = Mage::helper('shiphawk_shipping')->getApiKey();

        $url_api = 'https://sandbox.shiphawk.com/api/v1/items/search/'.$search_tag.'?api_key='.$api_key;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url_api,
            CURLOPT_POST => false
        ));

        $resp = curl_exec($curl);
        $arr_res = json_decode($resp);
        $responce_array = array();

        if (!empty($arr_res) && (empty($arr_res['error']))) {
            foreach ($arr_res as $el) {
                $responce_array[$el->id] = $el->name.' ('.$el->category.')';
            }

            $responce_html="<ul>";

            foreach($responce_array as $key=>$value) {
                $responce_html .='<li class="type_link" id='.$key.' onclick="setIdValue(this)" >'.$value.'</li>';
            }

            $responce_html .="</ul>";

        }else{
            $responce_html = '';
        }

        curl_close($curl);

        $this->getResponse()->setBody($responce_html);

    }


}
//curl -H 'Content-Type: application/json' -XPOST -d '{"from_zip":"90210","to_zip":"02072","rate_filter":"consumer","items":[{"width":"10", "length":"10", "height":"10", "weight": "11", "value":"103", "id": "50"}]}' 'https://sandbox.shiphawk.com/api/v1/rates/full?api_key=3331b35952ec7d99338a1cc5c496b55c'