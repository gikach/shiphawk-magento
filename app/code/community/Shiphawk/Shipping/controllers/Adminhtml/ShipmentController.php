<?php
class Shiphawk_Shipping_Adminhtml_ShipmentController extends Mage_Adminhtml_Controller_Action
{
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

            $items = Mage::getModel('shiphawk_shipping/carrier')->getShiphawkItems($order);

            $grouped_items_by_zip = Mage::getModel('shiphawk_shipping/carrier')->getGroupedItemsByZip($items);

            $shipping_description = $order->getShippingDescription();

            $is_multi_zip = (count($grouped_items_by_zip) > 1) ? true : false;
            $rate_filter =  Mage::helper('shiphawk_shipping')->getRateFilter();
            if($is_multi_zip) {
                $rate_filter = 'best';
            }

            foreach($shiphawk_rate_data as $rate_id=>$products_ids) {
                    $is_rate = false;
                    //если $is_multi_zip то используем  $rate_filter = best значит в респонсе будет всего один метод
                    if(($is_multi_zip)||($rate_filter == 'best')) {
                        $responceObject = $api->getShiphawkRate($products_ids['from_zip'], $products_ids['to_zip'], $products_ids['items'], $rate_filter);
                    // get only one method for each group of product
                        $rate_id = $responceObject[0]->id;
                        $is_rate = true;
                    }else{
                        $responceObject = $api->getShiphawkRate($products_ids['from_zip'], $products_ids['to_zip'], $products_ids['items'], $rate_filter);

                        foreach ($responceObject as $responce) {
                            //if( strpos($shipping_description, $responce->summary->service) !== false ) {
                            $shipping_amaount = $order->getShippingAmount();
                            if( $shipping_amaount == $responce->summary->price ) {
                                $rate_id = $responce->id;
                                $is_rate = true;
                                break;
                          }
                        }
                    }

                    if($is_rate == true) {
                        // add book
                        $track_data = $api->toBook($order,$rate_id);

                        $shipment = $api->_initShipHawkShipment($order,$products_ids);
                        $shipment->register();
                        $api->_saveShiphawkShipment($shipment);

                        // add track
                        if($track_number = $track_data->shipment_id) {
                            $api->addTrackNumber($shipment, $track_number);

                            $api->subscribeToTrackingInfo($shipment->getId());
                        }

                        $shipmentCreatedMessage = $this->__('The shipment has been created.');
                        $this->_getSession()->addSuccess($shipmentCreatedMessage);
                    }else{
                        Mage::getSingleton('core/session')->addError("Unfortunately the method that was chosen by a customer during checkout is currently unavailable. Please contact ShipHawk's customer service to manually book this shipment.");
                    }

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