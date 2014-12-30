<?php
class Shiphawk_Shipping_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {
    public function  __construct() {

        parent::__construct();

        $order_id = $this->getOrderId();

        $manual_shipping =  Mage::getStoreConfig('carriers/shiphawk_shipping/book_shipment');

        if($manual_shipping) {
            $this->_addButton('shiphawk_shipping', array(
                'label'     => Mage::helper('shiphawk_shipping')->__('Shiphawk Shipment'),
                /*'onclick'   => 'alert(\'' . $order_id . '\')',*/
                'onclick'   => 'setLocation(\'' . $this->getShipHawkUrl($order_id) . '\')',
                'class'     => 'go'
            ), 0, 100, 'header', 'header');
        }

    }

    public function getShipHawkUrl($order_id) {
        //
        //return $this->getUrl('*/sales_order_shipment/start');
        return Mage::helper("adminhtml")->getUrl("shiphawk_shipping/adminhtml/api/shipment", $order_id);
    }
}