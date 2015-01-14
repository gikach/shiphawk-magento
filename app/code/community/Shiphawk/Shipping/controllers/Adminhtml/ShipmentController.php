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
                    /*'shipment_id'=> $shipment_id_track,*/
                    'callback_url'=> $callback_url
                );

                $curl = curl_init();

                //1010221
                //$shipment_id_track = "1010224";

                Mage::log($items_array);
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
                Mage::log($arr_res);
                    /* (
                        [error] => Route not found
                    ) */

                    $items_array =  json_encode($items_array);
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

            //TODO что если несколько трек нмоеров, хардкод кариер код
            if($tracknum->getCarrierCode() == 'shiphawk_shipping_ground') {
                return $tracknum->getNumber();
            }
            //$tracknums[]=$tracknum->getNumber();


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

                foreach($shiphawk_rate_data as $rate_id=>$products_ids) {
                    $shipment = $this->_initShipHawkShipment($order,$products_ids);

                    $shipment->register();

                    $this->_saveShiphawkShipment($shipment);

                    // add book
                    $api = Mage::getModel('shiphawk_shipping/api');

                    $track_data = $api->toBook($order,$rate_id);

                    // add track
                    if($track_number = $track_data->shipment_id) {
                        $this->addTrackNumber($shipment, $track_number);
                    }
                }

                $shipmentCreatedMessage = $this->__('The shipment has been created.');

                $this->_getSession()->addSuccess($shipmentCreatedMessage);

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $shipment->getOrderId()));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Cannot save shipment.'));
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $shipment->getOrderId()));
        }

        $this->_redirect('adminhtml/sales_order/view', array('order_id' => $shipment->getOrderId()));
    }

    /**
     * Initialize shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment|bool
     */
    protected function _initShipHawkShipment($order, $products_ids)
    {
        $shipment = false;
        if(is_object($order)) {

            $savedQtys = $this->_getItems($order, $products_ids);
            $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($savedQtys);
        }

        return $shipment;
    }

    protected function _getItems($order, $products_ids) {
        $qty = array();
        if(is_object($order)) {
            foreach($order->getAllItems() as $eachOrderItem){

                if(in_array($eachOrderItem->getProductId(),$products_ids['product_ids'])) {
                    $Itemqty = 0;
                    $Itemqty = $eachOrderItem->getQtyOrdered()
                        - $eachOrderItem->getQtyShipped()
                        - $eachOrderItem->getQtyRefunded()
                        - $eachOrderItem->getQtyCanceled();
                    $qty[$eachOrderItem->getId()] = $Itemqty;
                }else{
                    $qty[$eachOrderItem->getId()] = 0;
                }

            }
        }

        return $qty;
    }

    /**
     * Save shipment and order in one transaction
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return Mage_Adminhtml_Sales_Order_ShipmentController
     */
    protected function _saveShiphawkShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    /**
     * Add new tracking number action
     */
    public function addTrackNumber($shipment, $number)
    {
        try {
            $carrier = 'shiphawk_shipping_ground';
            //$number  = $this->getRequest()->getPost('number');
            $title  = 'ShipHawk Shipping';
            if (empty($carrier)) {
                Mage::throwException($this->__('The carrier needs to be specified.'));
            }
            if (empty($number)) {
                Mage::throwException($this->__('Tracking number cannot be empty.'));
            }
            //$shipment = $this->_initShipment();
            if ($shipment) {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($number)
                    ->setCarrierCode($carrier)
                    ->setTitle($title);
                $shipment->addTrack($track)
                    ->save();

            } else {

                Mage::log('Cannot initialize shipment for adding tracking number.');
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
        } catch (Exception $e) {

            Mage::log('Cannot add tracking number.');
        }

    }

}