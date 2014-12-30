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

    public function addShiping($order){
        $qty=array();
        foreach($order->getAllItems() as $eachOrderItem){

            $Itemqty=0;
            $Itemqty = $eachOrderItem->getQtyOrdered()
                - $eachOrderItem->getQtyShipped()
                - $eachOrderItem->getQtyRefunded()
                - $eachOrderItem->getQtyCanceled();
            $qty[$eachOrderItem->getId()]=$Itemqty;

        }

        /*
        echo "<pre>";
        print_r($qty);
        echo "</pre>";
        */
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
    }

    //lock attributes by code
    /*public function lockAttributes($observer) {
        $event = $observer->getEvent();
        $product = $event->getProduct();
        $product->lockAttribute('shiphawk_type_of_product_value');
        $product->lockAttribute('shiphawk_product_id');
    }*/
}