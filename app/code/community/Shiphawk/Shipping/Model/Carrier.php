 <?php

class Shiphawk_Shipping_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Carrier's code, as defined in parent class
     *
     * @var string
     */
    protected $_code = 'shiphawk_shipping';

    /**
     * Returns available shipping rates for Shiphawk Shipping carrier
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        //TODO срабатывает 2 раза? записывать переменную в сессию
        /** @var Mage_Shipping_Model_Rate_Result $result */
        $result = Mage::getModel('shipping/rate_result');

        $helper = Mage::helper('shiphawk_shipping');
        $default_origin_zip = Mage::getStoreConfig('carriers/shiphawk_shipping/default_origin');

        $to_zip = $this->getShippingZip();
        $api = Mage::getModel('shiphawk_shipping/api');
        $items = $this->getShiphawkItems($request);

        Mage::log($items, null, 'Items.log');

        $grouped_items_by_zip = $this->_getGroupedItemsByZip($items);

        $ship_responces = array();
        foreach($grouped_items_by_zip as $from_zip=>$items_) {
            $ship_responces[] =  $api->getShiphawkRate($from_zip, $to_zip, $items_);
        }

        $services = $this->_getServices($ship_responces);

        $name_service = '';
        $summ_price = 0;
        $shiphawk_id = array();
        foreach ($services as $id_service=>$service) {
            $name_service .= $service['name'] . ', ';
            $summ_price += $service['price'];
            $shiphawk_id[] = $id_service;
            //$result->append($this->_getShiphawkRate($service['name'], $service['price']));
        }
//TODO проверка на error (zip code ит.д)
        Mage::getSingleton('core/session')->setShiphawkId(serialize($shiphawk_id));
        $result->append($this->_getShiphawkRate($name_service, $summ_price));

        Mage::log($services, null, 'ServiceARR.log');
        //$ship_responce = $api->getShiphawkRate($default_origin_zip, $to_zip, $items);

        Mage::log($ship_responces, null, 'Responce.log');

       /*if(is_array($ship_responce)) {
           foreach($ship_responce as $object) {
               $result->append($this->_getShiphawkRate($object->service, $object->price));
           }
       }*/

        return $result;
    }

    /**
     * Returns Allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            'ground'    =>  'Ground delivery',
        );
    }

    /**
     * Get Standard rate object
     *
     * @return Mage_Shipping_Model_Rate_Result_Method
     */
    protected function _getShiphawkRate($method_title, $price)
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('ground');
        $rate->setMethodTitle($method_title);
        $rate->setPrice($price);
        $rate->setCost(0);

        return $rate;
    }

    public  function getShiphawkItems($request) {
        $items = array();

        foreach ($request->getAllItems() as $item) {
            $product_id = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($product_id);
            $items[] = array(
                'width' => $product->getShiphawkWidth(),
                'length' => $product->getShiphawkLength(),
                'height' => $product->getShiphawkHeight(),
                'weight' => $product->getWeight(),
                'value' => $product->getShiphawkItemValue(),
                'quantity' => $product->getShiphawkQuantity()*$item->getQty(),
                'is_packed' => $this->getIsPacked($product),
                'id' => $product->getShiphawkTypeOfProductValue(),
                'zip'=> $this->getOriginZip($product)
            );
        }

        return $items;
    }

    public function getShippingZip() {
        /** @var $cart Mage_Checkout_Model_Cart */
        $cart = Mage::getSingleton('checkout/cart');
        $quote = $cart->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $zip_code = $shippingAddress->getPostcode();
        return $zip_code;
    }

    public function getOriginZip($product) {
        $default_origin_zip = Mage::getStoreConfig('carriers/shiphawk_shipping/default_origin');
        $product_origin_zip_code = $product->getShiphawkOriginZipcode();
        $product_origin_zip_code = (empty($product_origin_zip_code)) ? $default_origin_zip : $product_origin_zip_code;

        return $product_origin_zip_code;
    }

    public function getIsPacked($product) {
        $default_is_packed = Mage::getStoreConfig('carriers/shiphawk_shipping/item_is_packed');
        $product_is_packed = $product->getShiphawkItemIsPacked();
        $product_is_packed = ($product_is_packed == 2) ? $default_is_packed : $product_is_packed;

        return $product_is_packed;
    }

    protected function _getGroupedItemsByZip($items) {
        $tmp = array();
        foreach($items as $item) {
            $tmp[$item['zip']][] = $item;
        }
        return $tmp;
    }

    protected function _getServices($ship_responces) {
        $services = array();
        foreach($ship_responces as $ship_responce) {
            if(is_array($ship_responce)) {
                foreach($ship_responce as $object) {
                    $services[$object->id]['name'] = $object->service;
                    $services[$object->id]['price'] = $object->price;

                    Mage::log($object->service, null, 'Service.log');
                    Mage::log($object->price, null, 'Service.log');
                    Mage::log($object->id, null, 'Service.log');

                }
            }
        }

        return $services;
    }


}