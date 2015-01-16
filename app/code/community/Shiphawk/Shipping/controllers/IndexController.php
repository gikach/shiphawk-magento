<?php
class Shiphawk_Shipping_IndexController extends Mage_Core_Controller_Front_Action
{
    public function trackingAction() {

        //  var_dump (file_get_contents('php://input'));
        $api_key_from_url = $this->getRequest()->getParam('api_key');
        $data_from_shiphawk = json_decode(file_get_contents('php://input'));
        $api_key = Mage::helper('shiphawk_shipping')->getApiKey();

        //curl -X POST -H Content-Type:application/json -d '{"event":"shipment.status_update","status":"in_transit","updated_at":"2015-01-14T10:43:16.702-08:00","shipment_id":1010226}' http://shiphawk.devigor.wdgtest.com/index.php/shiphawk/index/tracking?api_key=3331b35952ec7d99338a1cc5c496b55c

        if($api_key_from_url == $api_key) {
            try {
            $track_number = $data_from_shiphawk->shipment_id;
            $shipment_track = Mage::getResourceModel('sales/order_shipment_track_collection')->addAttributeToFilter('track_number', $track_number)->getFirstItem();
            $shipment = Mage::getModel('sales/order_shipment')->load($shipment_track->getParentId());

            $data_from_shiphawk = (array) $data_from_shiphawk;
            $shipment->addComment(implode(',', $data_from_shiphawk));

            //TODO email to customer?
            //$shipment->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
            $shipment->save();
            }catch (Mage_Core_Exception $e) {
                Mage::logException($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e->getMessage());
            }

            Mage::log($data_from_shiphawk, null, 'ShipHawkTrackingData.log');
        }
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
