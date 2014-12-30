<?php
class Shiphawk_Shipping_IndexController extends Mage_Core_Controller_Front_Action
{
    public function testAction() {

        $orderId= $this->getRequest()->getPost('order_id');
        $orderId = 12;
        $order= Mage::getModel('sales/order')->load($orderId);

        $qty=array();
        foreach($order->getAllItems() as $eachOrderItem){

            $Itemqty=0;
            $Itemqty = $eachOrderItem->getQtyOrdered()
                - $eachOrderItem->getQtyShipped()
                - $eachOrderItem->getQtyRefunded()
                - $eachOrderItem->getQtyCanceled();
            $qty[$eachOrderItem->getId()]=$Itemqty;

        }


        echo "<pre>";
        print_r($qty);
        echo "</pre>";

        /* check order shipment is prossiable or not */

        $email=true;
        $includeComment=true;
        $comment="test Shipment";

        if ($order->canShip()) {
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            /* prepare to create shipment */
            $shipment = $order->prepareShipment($qty);
            if ($shipment) {
                $shipment->register();
                $shipment->addComment($comment, $email && $includeComment);
                $shipment->getOrder()->setIsInProcess(true);
                try {
                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder())
                        ->save();
                    $shipment->sendEmail($email, ($includeComment ? $comment : ''));
                } catch (Mage_Core_Exception $e) {
                    var_dump($e);
                }

            }

        }

        $this->_redirect('*');
    }
    public function getapiAction() {

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

    public function getrateAction() {
        $api_key = Mage::helper('shiphawk_shipping')->getApiKey();
        $url_api = 'https://sandbox.shiphawk.com/api/v1/rates/full?api_key='.$api_key;

        $curl = curl_init();
        $items_array = array(
                'from_zip'=> '45226',
                'to_zip'=> '02072',
                'rate_filter' => 'consumer',
                'items' =>
                    array(
                        array(
                            'width'=> 10,
                            'length'=> 10,
                            'height'=> 10,
                            'weight'=> 11,
                            'value'=>103,
                            /*'quantity' => 1,
                            'is_packed' => true,*/
                            'id'=> 50
                        )
                    )

        );

        $items_array =  json_encode($items_array);
        var_dump($items_array);

        curl_setopt($curl, CURLOPT_URL, $url_api);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $items_array);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($items_array)
                )
        );



        $resp = curl_exec($curl);

        var_dump($resp);
        $arr_res = json_decode($resp);

        Mage::log($arr_res, null, 'Apiresponce.log');

        var_dump($arr_res);

        curl_close($curl);
    }

    public function bookAction() {
        $api_key = Mage::helper('shiphawk_shipping')->getApiKey();
        $url_api = 'https://sandbox.shiphawk.com/api/v1/shipments/book?api_key='.$api_key;
//rate id 60d4d586-2521-4090-88a4-0fbf5678ed04
//rate id cfe58398-9417-4731-aa54-b9cb0221d1fc
        $curl = curl_init();
        $items_array = array(
            'rate_id'=> '9384ffbe-b97d-4c62-b221-fa67453b5535',
            'order_email'=> 'gikach@gmail.com',
            'xid'=>'100000019',
            'origin_address' =>
                array(
                    'first_name' => 'test',
                    'last_name' => 'test',
                    'address_line_1' => 'street 1',
                    'phone_num' => '3333',
                    'city' => 'City',
                    'state' => 'NY',
                    'zipcode' => '45226'
                ),
            'destination_address' =>
                array(
                    'first_name' => 'test dest',
                    'last_name' => 'test dest',
                    'address_line_1' => '645 West 1st Avenue',
                    'phone_num' => '332233',
                    'city' => 'Roselle',
                    'state' => 'NJ',
                    'zipcode' => '07203'
                ),
            'billing_address' =>
                 array(
                     'first_name' => 'test',
                     'last_name' => 'test',
                     'address_line_1' => 'street 1',
                     'phone_num' => '3333',
                     'city' => 'City',
                     'state' => 'NY',
                     'zipcode' => '45226'
                 ),
            'pickup' =>
                array(
                    array(
                        'start_time' => '2015-07-27T04:51:36.645-07:00',
                        'end_time' => '2015-07-27T07:51:36.645-07:00',
                    ),
                    array(
                        'start_time' => '2015-07-28T04:51:36.645-07:00',
                        'end_time' => '2015-07-28T07:51:36.646-07:00',
                    )
                ),

            'accessorials' => array()

        );

        $items_array =  json_encode($items_array);
        var_dump($items_array);

        curl_setopt($curl, CURLOPT_URL, $url_api);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $items_array);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($items_array)
            )
        );



        $resp = curl_exec($curl);

        var_dump($resp);
        $arr_res = json_decode($resp);

        Mage::log($arr_res, null, 'BOOKresponce.log');

        var_dump($arr_res);

        curl_close($curl);
    }

}
//curl -H 'Content-Type: application/json' -XPOST -d '{"from_zip":"90210","to_zip":"02072","rate_filter":"consumer","items":[{"width":"10", "length":"10", "height":"10", "weight": "11", "value":"103", "id": "50"}]}' 'https://sandbox.shiphawk.com/api/v1/rates/full?api_key=3331b35952ec7d99338a1cc5c496b55c'