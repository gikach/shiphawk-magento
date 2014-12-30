<?php
class Shiphawk_Shipping_Adminhtml_ApiController extends Mage_Adminhtml_Controller_Action
{
    public function gettypeAction() {
        Mage::log('gettypeAction');
        //echo $this->getUrl('faqs/index/send/');

        //    $urladmin = Mage::helper("adminhtml")->getUrl("shiphawk_shipping/api/gettype");
    }

    public function shipmentAction() {

        Mage::log('TO SHIP');
        $orderId= $this->getRequest()->getPost('order_id');
       // $orderId = 12;
        $order= Mage::getModel('sales/order')->load($orderId);

        $qty=array();
        foreach($order->getAllItems() as $eachOrderItem){

            $Itemqty=0;
            $Itemqty = $eachOrderItem->getQtyOrdered()
                - $eachOrderItem->getQtyShipped()
                - $eachOrderItem->getQtyRefunded()
                - $eachOrderItem->getQtyCanceled();
            $qty[$eachOrderItem->getId()] = $Itemqty;

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
}