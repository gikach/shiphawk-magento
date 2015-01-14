<?php
class Shiphawk_Shipping_Model_Observer extends Mage_Core_Model_Abstract
{
    public function configSaveAfter($observer){

        $is_requered_attr = Mage::getStoreConfig('carriers/shiphawk_shipping/required_attribute');
        $shiphawk_attributes = Mage::helper('shiphawk_shipping')->getAttributes();

        foreach($shiphawk_attributes as $attributeCode) {
            $this->_setAttributeRequired($attributeCode,$is_requered_attr);
        }

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
        //ордер не виден в админке
      //  $this->addShipping($orderId);

        Mage::log('Place order after');
        Mage::log($order->getId());

        $shiphawk_book_id = Mage::getSingleton('core/session')->getShiphawkBookId();

        Mage::log(unserialize($shiphawk_book_id));

        $order->setShiphawkBookId($shiphawk_book_id);
        $order->save();


        $api = Mage::getModel('shiphawk_shipping/api');

        $api->saveshipment($orderId);



        Mage::log($order->getShiphawkBookId());
        Mage::getSingleton('core/session')->unsShiphawkBookId();

    }

    public function saveShipHawkId() {
//TODO возможно потому что не сохраняются сессии в этот метод не заходит
        $ship_hawk_id = Mage::getSingleton('core/session')->getShiphawkId();
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        $order->setShiphawkApiId($ship_hawk_id);
        $order->save();

        //Mage::log('order complete');

        //$this->addShiping($order);

        Mage::getSingleton('core/session')->unsShiphawkId();
    }

    public function addShipping($orderId){

        //TODO перенос методов из контроллера шипмент
    }

    //lock attributes by code
    /*public function lockAttributes($observer) {
        $event = $observer->getEvent();
        $product = $event->getProduct();
        $product->lockAttribute('shiphawk_type_of_product_value');
        $product->lockAttribute('shiphawk_product_id');
    }*/
}