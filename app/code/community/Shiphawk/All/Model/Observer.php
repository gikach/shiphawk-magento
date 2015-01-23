<?php
class Shiphawk_All_Model_Observer extends Mage_Core_Model_Abstract
{
    public function setDefaultGroup($observer){

            if(Mage::helper('core')->isModuleEnabled('Shiphawk_Shipping')) {
                Mage::log('Enabled');
            }else{
                Mage::log('NOT Enabled');
                $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_group_collection')
                    ->addFieldToFilter('attribute_group_name', 'ShipHawk Attributes')
                    ->load();
                //$select = $attributeSetCollection->getSelect()->__toString();
                //Mage::log($select);
                foreach ($attributeSetCollection as $id=>$attributeGroup) {

                    Mage::log('set-id: ' . $attributeGroup->getAttributeSetId(), null, 'AttributeGroup.log');
                    Mage::log('group-name: ' . $attributeGroup->getAttributeGroupName(), null, 'AttributeGroup.log');
                    Mage::log('group-id: ' . $attributeGroup->getAttributeGroupId(), null, 'AttributeGroup.log');

                }
            }

            /*$modules = Mage::getConfig()->getNode('modules')->children();
            $modulesArray = (array)$modules;

            // Mage::log($modulesArray);

            if(isset($modulesArray['Shiphawk_Shipping'])) {
               Mage::log(' Exist');
            } else {
                Mage::log('NOT Exist');
            }*/
            /*
             * //eav_attribute_group table
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

    public function lockGroup($observer) {
        if(Mage::helper('core')->isModuleEnabled('Shiphawk_Shipping')) {
            Mage::log('Enabled');
        }else{
            Mage::log('NOT Enabled');


        }
    }

    public function lockAttributes() {

    }
}