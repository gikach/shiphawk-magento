<?php

class Shiphawk_Shipping_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    const DEFAULT_WEIGHT = 1;
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
        /** @var Mage_Shipping_Model_Rate_Result $result */
        $result = Mage::getModel('shipping/rate_result');

        $helper = Mage::helper('shiphawk_shipping');
        $default_origin_zip = Mage::getStoreConfig('carriers/shiphawk_shipping/default_origin');

        $hide_on_frontend = Mage::getStoreConfig('carriers/shiphawk_shipping/hide_on_frontend');
        $is_admin = $helper->checkIsAdmin();
        // hide ShipHawk method on frontend , allow only in admin area
        if (($hide_on_frontend == 1) && (!$is_admin)) {
            return $result;
        }

        $to_zip = $this->getShippingZip();
        $api = Mage::getModel('shiphawk_shipping/api');
        $items = $this->getShiphawkItems($request);

        $grouped_items_by_zip = $this->getGroupedItemsByZip($items);

        $ship_responces = array();
        $toOrder= array();
        $api_error = false;
        $is_multi_zip = false;

        if(count($grouped_items_by_zip) > 1)  {
            $is_multi_zip = true;
        }


        $rate_filter =  Mage::helper('shiphawk_shipping')->getRateFilter($is_admin);

        $carrier_type = Mage::getStoreConfig('carriers/shiphawk_shipping/carrier_type');

        try {
            //default origin zip code
            $from_zip = Mage::getStoreConfig('carriers/shiphawk_shipping/default_origin');
            //foreach($grouped_items_by_zip as $from_zip=>$items_) {
            foreach($grouped_items_by_zip as $origin_id=>$items_) {

                if ($origin_id != 'origin_per_product') {

                    if($is_multi_zip) {
                        $rate_filter = 'best';
                    }

                    if($origin_id) {
                        $shipHawkOrigin = Mage::getModel('shiphawk_shipping/origins')->load($origin_id);
                        $from_zip = $shipHawkOrigin->getShiphawkOriginZipcode();
                    }


                    $checkattributes = $helper->checkShipHawkAttributes($from_zip, $to_zip, $items_, $rate_filter);

                    if(empty($checkattributes)) {
                        /* get zipcode and location type from first item in grouped by origin (zipcode) products */
                        $from_zip = $items_[0]['zip'];
                        $location_type = $items_[0]['location_type'];
                        $responceObject = $api->getShiphawkRate($from_zip, $to_zip, $items_, $rate_filter, $carrier_type, $location_type );
Mage::log($from_zip, null, 'from-zip.log');
Mage::log($items_, null, 'from-zip.log');
                        $ship_responces[] = $responceObject;

                        if(is_object($responceObject)) {
                            $api_error = true;
                            Mage::log('ShipHawk response: '.$responceObject->error, null, 'ShipHawk.log');
                        }else{
                            // if $rate_filter = 'best' then it is only one rate
                            if(($is_multi_zip)||($rate_filter == 'best')) {
                                Mage::getSingleton('core/session')->setMultiZipCode(true);
                                $toOrder[$responceObject[0]->id]['product_ids'] = $this->getProductIds($items_);
                                $toOrder[$responceObject[0]->id]['price'] = $responceObject[0]->summary->price;
                                $toOrder[$responceObject[0]->id]['name'] = $responceObject[0]->summary->service;
                                $toOrder[$responceObject[0]->id]['items'] = $items_;
                                $toOrder[$responceObject[0]->id]['from_zip'] = $from_zip;
                                $toOrder[$responceObject[0]->id]['to_zip'] = $to_zip;
                                $toOrder[$responceObject[0]->id]['carrier'] = $responceObject[0]->summary->carrier;
                            }else{
                                Mage::getSingleton('core/session')->setMultiZipCode(false);
                                foreach ($responceObject as $responce) {
                                    $toOrder[$responce->id]['product_ids'] = $this->getProductIds($items_);
                                    $toOrder[$responce->id]['price'] = $responce->summary->price;
                                    $toOrder[$responce->id]['name'] = $responce->summary->service;
                                    $toOrder[$responce->id]['items'] = $items_;
                                    $toOrder[$responce->id]['from_zip'] = $from_zip;
                                    $toOrder[$responce->id]['to_zip'] = $to_zip;
                                    $toOrder[$responce->id]['carrier'] = $responce->summary->carrier;
                                }
                            }
                        }
                    }else{
                        $api_error = true;
                        foreach($checkattributes as $rate_error) {
                            Mage::log('ShipHawk error: '.$rate_error, null, 'ShipHawk.log');
                        }
                    }
                }else{

                    $grouped_items_per_product_by_zip = $this->getGroupedItemsByZipPerProduct($items_);

                    if(count($grouped_items_per_product_by_zip) > 1 ) {
                        $is_multi_zip = true;
                    }

                    if($is_multi_zip) {
                        $rate_filter = 'best';
                    }

                    foreach ($grouped_items_per_product_by_zip as $from_zip=>$items_per_product) {

                        $checkattributes = $helper->checkShipHawkAttributes($from_zip, $to_zip, $items_per_product, $rate_filter);

                        if(empty($checkattributes)) {
                            /* get zipcode and location type from first item in grouped by origin (zipcode) products */
                            $from_zip = $items_[0]['zip'];
                            $location_type = $items_[0]['location_type'];

                            $responceObject = $api->getShiphawkRate($from_zip, $to_zip, $items_per_product, $rate_filter, $carrier_type, $location_type);
                            Mage::log($from_zip, null, 'from-zip.log');
                            Mage::log($items_, null, 'from-zip.log');
                            $ship_responces[] = $responceObject;

                            if(is_object($responceObject)) {
                                $api_error = true;
                                Mage::log('ShipHawk response: '.$responceObject->error, null, 'ShipHawk.log');
                            }else{
                                // if $rate_filter = 'best' then it is only one rate
                                if(($is_multi_zip)||($rate_filter == 'best')) {
                                    Mage::getSingleton('core/session')->setMultiZipCode(true);
                                    $toOrder[$responceObject[0]->id]['product_ids'] = $this->getProductIds($items_per_product);
                                    $toOrder[$responceObject[0]->id]['price'] = $responceObject[0]->summary->price;
                                    $toOrder[$responceObject[0]->id]['name'] = $responceObject[0]->summary->service;
                                    $toOrder[$responceObject[0]->id]['items'] = $items_per_product;
                                    $toOrder[$responceObject[0]->id]['from_zip'] = $from_zip;
                                    $toOrder[$responceObject[0]->id]['to_zip'] = $to_zip;
                                    $toOrder[$responceObject[0]->id]['carrier'] = $responceObject[0]->summary->carrier;
                                }else{
                                    Mage::getSingleton('core/session')->setMultiZipCode(false);
                                    foreach ($responceObject as $responce) {
                                        $toOrder[$responce->id]['product_ids'] = $this->getProductIds($items_per_product);
                                        $toOrder[$responce->id]['price'] = $responce->summary->price;
                                        $toOrder[$responce->id]['name'] = $responce->summary->service;
                                        $toOrder[$responce->id]['items'] = $items_per_product;
                                        $toOrder[$responce->id]['from_zip'] = $from_zip;
                                        $toOrder[$responce->id]['to_zip'] = $to_zip;
                                        $toOrder[$responce->id]['carrier'] = $responce->summary->carrier;
                                    }
                                }
                            }
                        }else{
                            $api_error = true;
                            foreach($checkattributes as $rate_error) {
                                Mage::log('ShipHawk error: '.$rate_error, null, 'ShipHawk.log');
                            }
                        }

                    }

                }
            }

            if(!$api_error) {
                $services = $this->getServices($ship_responces);
                $name_service = '';
                $summ_price = 0;

                foreach ($services as $id_service=>$service) {
                    if (!$is_multi_zip) {
                        //add ShipHawk shipping
                        $shipping_price = $helper->getDiscountShippingPrice($service['price']);
                        if($is_admin == false) {
                            $result->append($this->_getShiphawkRateObject($service['name'], $shipping_price, $service['price']));
                        }else{

                            $result->append($this->_getShiphawkRateObject($service['carrier'] . ' - ' . $service['name'] . ' - ' . $service['delivery'] . ' - ' . $service['carrier_type'], $shipping_price, $service['price']));
                        }

                    }else{
                        if($is_admin == false) {
                            $name_service .= $service['name'] . ', ';
                        }else{

                            $name_service .= $service['carrier'] . ' - ' . $service['name'] . ' - ' . $service['delivery'] . ' - ' . $service['carrier_type'] .  ', ';
                        }
                        //$name_service .= $service['name'] . ', ';
                        $summ_price += $service['price'];
                    }
                }



                //save rate_id info for Book
                Mage::getSingleton('core/session')->setShiphawkBookId($toOrder);

                //remove last comma
                if(strlen($name_service) >2) {
                    if ($name_service{strlen($name_service)-2} == ',') {
                        $name_service = substr($name_service,0,-2);
                    }
                }

                if($is_multi_zip) {
                    //add ShipHawk shipping
                    $name_service = 'Shipping from multiple locations';
                    $shipping_price = $helper->getDiscountShippingPrice($summ_price);
                    Mage::getSingleton('core/session')->setSummPrice($summ_price);
                    $result->append($this->_getShiphawkRateObject($name_service, $shipping_price, $summ_price));
                }
            }

        }catch (Mage_Core_Exception $e) {
            Mage::logException($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e->getMessage());
        }
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

    public function  getProductIds($_items) {
     $products_ids = array();
        foreach($_items as $_item) {
            $products_ids[] = $_item['product_id'];
        }
        return $products_ids;
    }

    /**
     * Get Standard rate object
     *
     * @return Mage_Shipping_Model_Rate_Result_Method
     */
    protected function _getShiphawkRateObject($method_title, $price, $true_price)
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');

        $ship_rate_id = str_replace('-', '_', str_replace(',', '', str_replace(' ', '_', $method_title.$true_price)));

        $rate->setCarrier($this->_code);
        //$rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setCarrierTitle('ShipHawk');
        $rate->setMethodTitle($method_title);
        $rate->setMethod($ship_rate_id);
        $rate->setPrice($price);
        $rate->setCost($price);

        return $rate;
    }

    public function getShiphawkItems($request) {
        $items = array();
        foreach ($request->getAllItems() as $item) {
            $product_id = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($product_id);

            $type_id = $product->getTypeId();
            if($type_id == 'simple') {
                $product_qty = (($product->getShiphawkQuantity() > 0)) ? $product->getShiphawkQuantity() : 1;

                //hack for admin shipment in popup
                $qty_ordered = ($item->getQty() > 0 ) ? $item->getQty() : $item->getData('qty_ordered');

                if($product->getWeight() > 0) {
                    $items[] = array(
                        'width' => $product->getShiphawkWidth(),
                        'length' => $product->getShiphawkLength(),
                        'height' => $product->getShiphawkHeight(),
                        'weight' => $product->getWeight(),
                        'value' => $this->getShipHawkItemValue($product),
                        //'quantity' => $product_qty*$item->getQty(),
                        'quantity' => $product_qty*$qty_ordered,
                        'packed' => $this->getIsPacked($product),
                        'id' => $product->getShiphawkTypeOfProductValue(),
                        'zip'=> $this->getOriginZip($product),
                        'product_id'=> $product_id,
                        'xid'=> $product_id,
                        'origin'=> $this->getShiphawkShippingOrigin($product),
                        'location_type'=> $this->getOriginLocation($product),
                    );
                }else{
                    $items[] = array(
                        'width' => $product->getShiphawkWidth(),
                        'length' => $product->getShiphawkLength(),
                        'height' => $product->getShiphawkHeight(),
                        'weight' => self::DEFAULT_WEIGHT, //todo move to config?
                        'value' => $this->getShipHawkItemValue($product),
                        //'quantity' => $product_qty*$item->getQty(),
                        'quantity' => $product_qty*$qty_ordered,
                        'packed' => $this->getIsPacked($product),
                        'id' => $product->getShiphawkTypeOfProductValue(),
                        'zip'=> $this->getOriginZip($product),
                        'product_id'=> $product_id,
                        'xid'=> $product_id,
                        'origin'=> $this->getShiphawkShippingOrigin($product),
                        'location_type'=> $this->getOriginLocation($product),
                    );
                }
            }
        }

        return $items;
    }

    public function getShiphawkShippingOrigin($product) {
        $product_origin_id = $product->getShiphawkShippingOrigins();
        /** @var $helper Shiphawk_Shipping_Helper_Data */
        $helper = Mage::helper('shiphawk_shipping');
        if ($product_origin_id) {
            return $product_origin_id;
        }

        if ((empty($product_origin_id)) && ($helper->checkShipHawkOriginAttributes($product))) {
            return 'origin_per_product';
        }

        return null;

    }

    public function getShippingZip() {
        if (Mage::app()->getStore()->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        }else{
            /** @var $cart Mage_Checkout_Model_Cart */
            $cart = Mage::getSingleton('checkout/cart');
            $quote = $cart->getQuote();
        }

        $shippingAddress = $quote->getShippingAddress();
        $zip_code = $shippingAddress->getPostcode();
        return $zip_code;
    }

    public function getShipHawkItemValue($product) {
        if($product->getShiphawkQuantity() > 0) {
            $product_price = $product->getPrice()/$product->getShiphawkQuantity();
        }else{
            $product_price = $product->getPrice();
        }
        $item_value = ($product->getShiphawkItemValue() > 0) ? $product->getShiphawkItemValue() : $product_price;
        return $item_value;
    }

    public function getOriginZip($product) {
        $default_origin_zip = Mage::getStoreConfig('carriers/shiphawk_shipping/default_origin');

        $shipping_origin_id = $product->getData('shiphawk_shipping_origins');

        $helper = Mage::helper('shiphawk_shipping');
        /* check if all origin attributes are set */
        $per_product = $helper->checkShipHawkOriginAttributes($product);

        if($per_product == true) {
            return $product->getData('shiphawk_origin_zipcode');;
        }

        if($shipping_origin_id) {
            // get zip code from Shiping Origin
            $shipping_origin = Mage::getModel('shiphawk_shipping/origins')->load($shipping_origin_id);
            $product_origin_zip_code = $shipping_origin->getData('shiphawk_origin_zipcode');
            return $product_origin_zip_code;
        }

        return $default_origin_zip;
    }

    public function getOriginLocation($product) {
        $default_origin_location = Mage::getStoreConfig('carriers/shiphawk_shipping/origin_location_type');

        $shipping_origin_id = $product->getData('shiphawk_shipping_origins');

        $helper = Mage::helper('shiphawk_shipping');
        /* check if all origin attributes are set */
        $per_product = $helper->checkShipHawkOriginAttributes($product);

        if($per_product == true) {
            //$product->getAttributeText('brand');
            return $product->getAttributeText('shiphawk_origin_location');
        }

        if($shipping_origin_id) {
            // get zip code from Shiping Origin
            $shipping_origin = Mage::getModel('shiphawk_shipping/origins')->load($shipping_origin_id);
            $product_origin_zip_code = $shipping_origin->getData('shiphawk_origin_location');
            return $product_origin_zip_code;
        }

        return $default_origin_location;
    }

    public function getIsPacked($product) {
        $default_is_packed = Mage::getStoreConfig('carriers/shiphawk_shipping/item_is_packed');
        $product_is_packed = $product->getShiphawkItemIsPacked();
        $product_is_packed = ($product_is_packed == 2) ? $default_is_packed : $product_is_packed;

        return ($product_is_packed ? 'true' : 'false');
    }

    /* sort items by origin id */
    public function getGroupedItemsByZip($items) {
        $tmp = array();
        foreach($items as $item) {
            $tmp[$item['origin']][] = $item;
        }
        return $tmp;
    }

    /* sort items by origin zip code */
    public function getGroupedItemsByZipPerProduct($items) {
        $tmp = array();
        foreach($items as $item) {
            $tmp[$item['zip']][] = $item;
        }
        return $tmp;
    }

    public function getServices($ship_responces) {

        $services = array();
        foreach($ship_responces as $ship_responce) {
            if(is_array($ship_responce)) {
                foreach($ship_responce as $object) {
                    $services[$object->id]['name'] = $this->_getServiceName($object);
                    $services[$object->id]['price'] = $object->summary->price;
                    $services[$object->id]['carrier'] = $object->summary->carrier;
                    $services[$object->id]['delivery'] = $object->summary->delivery;
                    $services[$object->id]['carrier_type'] = $object->summary->carrier_type;
                }
            }
        }

        return $services;
    }

    protected function _getServiceName($object) {
        if ( $object->summary->carrier_type == "Small Parcel" ) {
            return $object->summary->service;
        }

        if ( $object->summary->carrier_type == "Blanket Wrap" ) {
            return "Standard White Glove Delivery (3-6 weeks)";
        }

        if ( ( ( $object->summary->carrier_type == "LTL" ) || ( $object->summary->carrier_type == "3PL" ) || ( $object->summary->carrier_type == "Intermodal" ) ) && ($object->details->price->delivery == 0) ) {
            return "Curbside Delivery (1-2 weeks)";
        }

        if ( ( ( $object->summary->carrier_type == "LTL" ) || ( $object->summary->carrier_type == "3PL" ) || ( $object->summary->carrier_type == "Intermodal" ) ) && ($object->details->price->delivery > 0) ) {
            return "Expedited White Glove Delivery (2-3 weeks)";
        }

        if ( $object->summary->carrier_type == "Home Delivery" ) {
            return "Home Delivery - " . $object->summary->service . " (1-2 weeks)";
        }

        return $object->summary->service;

    }

    /*
    1. If carrier_type = "Small Parcel" display name should be what's included in field [Service] (example: Ground)

    2. If carrier_type = "Blanket Wrap" display name should be:
    "Standard White Glove Delivery (3-6 weeks)"

    3. If carrier_type = "LTL","3PL","Intermodal" AND delivery field inside [details][price]=$0.00 display name should be:
    "Curbside delivery (1-2 weeks)"

    4. If carrier_type = "LTL","3PL" "Intermodal" AND delivery field inside [details][price] > $0.00 display name should be:
    "Expedited White Glove Delivery (2-3 weeks)"

    Additional rule for naming (both frontend and backend):

    If carrier_type = "Home Delivery" display name should be:
    "Home Delivery - {{
    {Service name from received rate}
    }} (1-2 weeks)"
    ==> example: Home Delivery - One Man (1-2 weeks)

    */

    public function isTrackingAvailable()
    {
        return true;
    }


}