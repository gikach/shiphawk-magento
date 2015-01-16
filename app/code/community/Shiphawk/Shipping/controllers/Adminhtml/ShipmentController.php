<?php
class Shiphawk_Shipping_Adminhtml_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    public function subscribeAction() {
        $shipment_id = $this->getRequest()->getParam('shipment_id');

        $helper = Mage::helper('shiphawk_shipping');
        $api_key = $helper->getApiKey();


        if($shipment_id) {
            try{
                $shipment = Mage::getModel('sales/order_shipment')->load($shipment_id);

                $shipment_id_track = $this->_getTrackNumber($shipment);

                $subscribe_url = $helper->getApiUrl() . 'shipments/' . $shipment_id_track . '/subscribe?api_key=' . $api_key;
                $callback_url = $helper->getCallbackUrl($api_key);

                $items_array = array(
                    'callback_url'=> $callback_url
                );

                $curl = curl_init();
                $items_array =  json_encode($items_array);

                curl_setopt($curl, CURLOPT_URL, $subscribe_url);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $items_array);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($items_array)
                    )
                );

                $resp = curl_exec($curl);
                $arr_res = json_decode($resp);

                if (!empty($arr_res)) {
                    $shipment->addComment($resp);
                }

                //TODO email to customer?
                //$shipment->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
                $shipment->save();

                curl_close($curl);

            }catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());

            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($this->__('Cannot load shipment.'));
            }

        }else{
            $this->_getSession()->addWarning($this->__('No ShipHawk tracking number'));
        }

        //$this->_getSession()->addSuccess($this->__('Subscribe'));
        $this->_redirect('adminhtml/sales_shipment/view', array('shipment_id' => $shipment_id));
    }

    protected function _getTrackNumber($shipment) {

        foreach($shipment->getAllTracks() as $tracknum)
        {
            //ShipHawk track number only one
            if($tracknum->getCarrierCode() == 'shiphawk_shipping') {
                return $tracknum->getNumber();
            }
        }
        return null;
    }

    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     *
     * @return null
     */
    public function saveshipmentAction()
    {
        $orderId= $this->getRequest()->getParam('order_id');

        try {
            $order = Mage::getModel('sales/order')->load($orderId);

            $shiphawk_rate_data = unserialize($order->getData('shiphawk_book_id')); //rate id

            $api = Mage::getModel('shiphawk_shipping/api');
            foreach($shiphawk_rate_data as $rate_id=>$products_ids) {

                    $responceObject = $api->getShiphawkRate($products_ids['from_zip'], $products_ids['to_zip'], $products_ids['items']);

                    //TODO get only one method for each group of product
                    $rate_id = $responceObject[0]->id;

                    // add book
                    $track_data = $api->toBook($order,$rate_id);

                    $shipment = $api->_initShipHawkShipment($order,$products_ids);
                    $shipment->register();
                    $api->_saveShiphawkShipment($shipment);

                    // add track
                    if($track_number = $track_data->shipment_id) {
                        $api->addTrackNumber($shipment, $track_number);
                    }

                    $shipmentCreatedMessage = $this->__('The shipment has been created.');
                    $this->_getSession()->addSuccess($shipmentCreatedMessage);
            }


        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save shipment.'));
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
        }

        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
    }

}