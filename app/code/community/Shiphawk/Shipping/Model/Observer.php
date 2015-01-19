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

    public function setDefaultGroup() {

        if(Mage::helper('core')->isModuleEnabled('Shiphawk_Shipping')) {
            Mage::log('Enabled');
        }

        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$modules;

       // Mage::log($modulesArray);

        if(!isset($modulesArray['Shiphawk_Shipping'])) {
            //Mage::log('NOT Exist');
        } else {
            //Mage::log('NOT Exist');
        }
        /*
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_group_collection')
                              ->load();

foreach ($attributeSetCollection as $id=>$attributeGroup) {
    echo 'group-name: '; echo $attributeGroup->getAttributeGroupName();
    echo '<br>';
    echo 'group-id: '; echo $attributeGroup->getAttributeGroupId();
    echo '<br>';
    echo 'set-id: '; echo $attributeGroup->getAttributeSetId();
    echo '<br>';
}
        */

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

        /* set ShipHawk rate */
        $shiphawk_book_id = Mage::getSingleton('core/session')->getShiphawkBookId();
        $order->setShiphawkBookId($shiphawk_book_id);
        $order->save();

        $manual_shipping =  Mage::getStoreConfig('carriers/shiphawk_shipping/book_shipment');

        if(!$manual_shipping) {
            $api = Mage::getModel('shiphawk_shipping/api');
            $api->saveshipment($orderId);
        }

        Mage::getSingleton('core/session')->unsShiphawkBookId();

    }

    public function saveShipHawkId() {
        //do not enter in this event

        /*$ship_hawk_id = Mage::getSingleton('core/session')->getShiphawkId();
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        $order->setShiphawkApiId($ship_hawk_id);
        $order->save();

        Mage::log('order complete');

        Mage::getSingleton('core/session')->unsShiphawkId();*/
    }

    //lock attributes by code
    /*public function lockAttributes($observer) {
        $event = $observer->getEvent();
        $product = $event->getProduct();
        $product->lockAttribute('shiphawk_type_of_product_value');
        $product->lockAttribute('shiphawk_product_id');
    }*/
}