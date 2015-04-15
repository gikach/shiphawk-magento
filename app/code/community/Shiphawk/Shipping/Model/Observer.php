<?php
class Shiphawk_Shipping_Model_Observer extends Mage_Core_Model_Abstract
{
    public function configSaveAfter($observer){

        /*$is_requered_attr = Mage::getStoreConfig('carriers/shiphawk_shipping/required_attribute');
        $shiphawk_attributes = Mage::helper('shiphawk_shipping')->getAttributes();

        foreach($shiphawk_attributes as $attributeCode) {
            $this->_setAttributeRequired($attributeCode,$is_requered_attr);
        }*/

    }

    protected function _setAttributeRequired($attributeCode, $is_active) {
        $attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode( 'catalog_product', $attributeCode);
        $attributeModel->setIsRequired($is_active);
        $attributeModel->save();
    }

    public function salesOrderPlaceAfter($observer) {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $orderId = $order->getId();

        $helper = Mage::helper('shiphawk_shipping');

        $manual_shipping =  Mage::getStoreConfig('carriers/shiphawk_shipping/book_shipment');
        $shipping_code = $order->getShippingMethod();
        $shipping_description = $order->getShippingDescription();
        $check_shiphawk = Mage::helper('shiphawk_shipping')->isShipHawkShipping($shipping_code);
        if($check_shiphawk !== false) {
            /* set ShipHawk rate */
            $shiphawk_book_id = Mage::getSingleton('core/session')->getShiphawkBookId();

            $multi_zip_code = Mage::getSingleton('core/session')->getMultiZipCode();

            //shiphawk_shipping_amount

            if($multi_zip_code == false) {
                //
                $shipping_amaount = $order->getShippingAmount();
                //$shiphawk_book_id  = $helper->getSipHawkCode($shiphawk_book_id, $shipping_description);
                //$shiphawk_book_id  = $helper->getSipHawkCode($shiphawk_book_id, $shipping_amaount);
                $shiphawk_book_id  = $helper->getSipHawkCode($shiphawk_book_id, $shipping_code);
                foreach ($shiphawk_book_id as $rate_id=>$method_data) {


                        $order->setShiphawkShippingAmount($method_data['price']);

                }
            }

            $order->setShiphawkBookId(serialize($shiphawk_book_id));
            $order->save();
            if(!$manual_shipping) {
                if ($order->canShip()) {
                    $api = Mage::getModel('shiphawk_shipping/api');
                    $api->saveshipment($orderId);
                }
            }
        }

        Mage::getSingleton('core/session')->unsShiphawkBookId();
        Mage::getSingleton('core/session')->unsMultiZipCode();

    }

}