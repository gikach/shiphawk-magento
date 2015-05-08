<?php
class Shiphawk_Shipping_IndexController extends Mage_Core_Controller_Front_Action
{
    public function trackingAction() {

        $api_key_from_url = $this->getRequest()->getParam('api_key');
        $data_from_shiphawk = json_decode(file_get_contents('php://input'));
        $api_key = Mage::helper('shiphawk_shipping')->getApiKey();

        //curl -X POST -H Content-Type:application/json -d '{"event":"shipment.status_update","status":"in_transit","updated_at":"2015-01-14T10:43:16.702-08:00","shipment_id":1010226}' http://shiphawk.devigor.wdgtest.com/index.php/shiphawk/index/tracking?api_key=3331b35952ec7d99338a1cc5c496b55c
        //curl -X POST -H Content-Type:application/json -d '{"event":"shipment.status_update","status":"in_transit","updated_at":"2015-01-14T10:43:16.702-08:00","shipment_id":1015967}' http://shiphawk.devigor.wdgtest.com/index.php/shiphawk/index/tracking?api_key=e1919f54fb93f63866f06049d6d45751

        if($api_key_from_url == $api_key) {
            try {
            $track_number = $data_from_shiphawk->shipment_id;
            $shipment_track = Mage::getResourceModel('sales/order_shipment_track_collection')->addAttributeToFilter('track_number', $track_number)->getFirstItem();
            $shipment = Mage::getModel('sales/order_shipment')->load($shipment_track->getParentId());

            $data_from_shiphawk = (array) $data_from_shiphawk;

            Mage::log($data_from_shiphawk, null, 'TrackingData.log');

            $shipment_status_updates = Mage::getStoreConfig('carriers/shiphawk_shipping/shipment_status_updates');
            $updates_tracking_url =    Mage::getStoreConfig('carriers/shiphawk_shipping/updates_tracking_url');
            $comment = '';

                $crated_time = $this->convertDateTome($data_from_shiphawk['updated_at']);

                if($data_from_shiphawk['event'] == 'shipment.status_update') {
                    switch ($data_from_shiphawk['status']) {
                        case 'in_transit':
                            $comment = "Shipment status changed to In Transit (" . $crated_time['date'] . " at " . $crated_time['time'] . "). Your shipment is with the carrier and is in transit.";
                            break;
                        case 'confirmed':
                            $comment = "Shipment status changed to Confirmed (" . $crated_time['date'] . " at " . $crated_time['time'] . "). Your shipment has been successfully confirmed.";
                            break;
                        case 'scheduled':
                            $comment = "Shipment status changed to Scheduled (" . $crated_time['date'] . " at " . $crated_time['time'] . "). Your shipment has been scheduled for pickup.";
                            break;
                        case 'agent_prep':
                            $comment = "Shipment status changed to Agent Prep (" . $crated_time['date'] . " at " . $crated_time['time'] . "). Your shipment is now being professionally prepared for carrier pickup.";
                            break;
                        case 'delivered':
                            $comment = "Shipment status changed to Delivered (" . $crated_time['date'] . " at " . $crated_time['time'] . "). Your shipment has been delivered!";
                            break;
                        case 'cancelled':
                            $comment = "Shipment status changed to Cancelled (" . $crated_time['date'] . " at " . $crated_time['time'] . "). Your shipment has been cancelled successfully.";
                            break;
                        default:
                            $comment = "Status was updated to " . $data_from_shiphawk['status'] . " at " . $crated_time['time'];

                    }

                    $shipment->addComment($comment);
                    if($shipment_status_updates) {
                        $shipment->sendUpdateEmail(true, $comment);
                    }
                }

                if($data_from_shiphawk['event'] == 'shipment.tracking_update') {
                    $comment = $data_from_shiphawk['updated_at'] . 'There is a tracking number available for your shipment - ' . $data_from_shiphawk['tracking_number'];
                    if ($data_from_shiphawk['tracking_url']) {
                        $comment .= ' <a href="' . $data_from_shiphawk['tracking_url'] . '" target="_blank">Click here to track.</a>';
                    }

                    $shipment->addComment($comment);
                    if($updates_tracking_url) {
                        $shipment->sendUpdateEmail(true, $comment);
                    }
                }


            $shipment->save();
            }catch (Mage_Core_Exception $e) {
                Mage::logException($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e->getMessage());
            }

        }
    }

    public function convertDateTome ($date_time) {
        ///2015-04-01T15:57:42Z
        $result = array();
        $t = explode('T', $date_time);
        $result['date'] = date("m/d/y", strtotime($t[0]));

        $result['time'] = date("g:i a", strtotime(substr($t[1], 0, -1)));

        return $result;
    }

    /* suggest items type in product page */
    public function searchAction() {

        $search_tag = trim(strip_tags($this->getRequest()->getPost('search_tag')));

        $api_key = Mage::helper('shiphawk_shipping')->getApiKey();
        $api_url = Mage::helper('shiphawk_shipping')->getApiUrl();

        $url_api = $api_url . 'items/search/'.$search_tag.'?api_key='.$api_key;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url_api,
            CURLOPT_POST => false
        ));

        $resp = curl_exec($curl);
        $arr_res = json_decode($resp);
        $responce_array = array();
        $responce = array();

        if(($arr_res->error) || ($arr_res['error'])) {

            Mage::log($arr_res->error, null, 'ShipHawk.log');
            $responce_html = '';
            $responce['shiphawk_error'] = $arr_res->error;
        }else{
            foreach ((array) $arr_res as $el) {
                $responce_array[$el->id] = $el->name.' ('.$el->category.')';
            }

            $responce_html="<ul>";

            foreach($responce_array as $key=>$value) {
                $responce_html .='<li class="type_link" id='.$key.' onclick="setItemid(this)" >'.$value.'</li>';
            }

            $responce_html .="</ul>";
        }
        $responce['responce_html'] = $responce_html;
        curl_close($curl);

        $this->getResponse()->setBody( json_encode($responce) );
    }

    public function originsAction() {
        $origin_id = trim(strip_tags($this->getRequest()->getPost('origin_id')));

        $is_mass_action = $this->getRequest()->getPost('is_mass_action');

        $origins_collection = $collection = Mage::getModel('shiphawk_shipping/origins')->getCollection();

        $responce = '<select name="product[shiphawk_shipping_origins]" id="shiphawk_shipping_origins">';

        if($is_mass_action == 1) {
            $responce = '<select name="attributes[shiphawk_shipping_origins]" id="shiphawk_shipping_origins" disabled>';
        }

        $responce .= '<option value="">default</option>';

        foreach($origins_collection as $origin) {
            if ($origin_id != $origin->getId()) {
                $responce .= '<option value="'.$origin->getId().'">'.$origin->getShiphawkOriginTitle(). '</option>';
            }else{
                $responce .= '<option selected value="'.$origin->getId().'">'.$origin->getShiphawkOriginTitle().  '</option>';
            }
        }

        $responce .='</select>';

        $this->getResponse()->setBody( json_encode($responce) );
    }
}