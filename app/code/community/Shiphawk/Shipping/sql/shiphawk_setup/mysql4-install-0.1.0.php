<?php
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$type_of_product_data = array (
    'attribute_set' =>  'Default',
    'group' => 'ShipHawk Attributes',
    'label'    => 'Type of Item',
    'visible'     => true,
    'type'     => 'varchar',
    'apply_to'          => 'simple',
    /*'input_renderer'    => 'shiphawk_shipping/catalog_product_helper_form_type',//definition of renderer*/
    'input'    => 'text',
    'system'   => false,
    'required' => false,
    'user_defined' => 1,
);

$installer->addAttribute('catalog_product','shiphawk_type_of_product',$type_of_product_data);

$shiphawk_quantity_data = array (
    'attribute_set' =>  'Default',
    'group' => 'ShipHawk Attributes',
    'label'    => 'Number of items per Product',
    'visible'     => true,
    'type'     => 'int',
    'apply_to'          => 'simple',
    'input'    => 'text',
    'default'   => 1,
    'frontend_class' => 'validate-not-negative-number',
    'system'   => false,
    'required' => false,
    'user_defined' => 1,
);

$installer->addAttribute('catalog_product','shiphawk_quantity', $shiphawk_quantity_data);

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'shiphawk_item_is_packed', array(
    'group'         => 'ShipHawk Attributes',
    'backend'       => 'catalog/product_attribute_backend_msrp',
    'label'         => 'Packaged?',
    'input'         => 'select',
    /*'source'        => 'eav/entity_attribute_source_boolean',*/
    'source'        => 'catalog/product_attribute_source_msrp_type_enabled',
    'type'     => 'varchar',
    'apply_to'          => 'simple',
    'visible'       => true,
    'required'      => false,
    'user_defined'  => 1,
    'default'       => '2',
    'input_renderer'   => 'adminhtml/catalog_product_helper_form_msrp_enabled',
    'visible_on_front' => false
));

$length_data = array (
    'attribute_set' =>  'Default',
    'group' => 'ShipHawk Attributes',
    'label'    => 'Length',
    'visible'     => true,
    'type'     => 'varchar',
    'apply_to'          => 'simple',
    'input'    => 'text',
    'system'   => false,
    'required' => false,
    'user_defined' => 1,
);

$installer->addAttribute('catalog_product','shiphawk_length',$length_data);

$width_data = array (
    'attribute_set' =>  'Default',
    'group' => 'ShipHawk Attributes',
    'label'    => 'Width',
    'visible'     => true,
    'type'     => 'varchar',
    'apply_to'          => 'simple',
    'input'    => 'text',
    'system'   => false,
    'required' => false,
    'user_defined' => 1,
);

$installer->addAttribute('catalog_product','shiphawk_width',$width_data);

$height_data = array (
    'attribute_set' =>  'Default',
    'group' => 'ShipHawk Attributes',
    'label'    => 'Height',
    'visible'     => true,
    'type'     => 'varchar',
    'apply_to'          => 'simple',
    'input'    => 'text',
    'system'   => false,
    'required' => false,
    'user_defined' => 1,
);

$installer->addAttribute('catalog_product','shiphawk_height',$height_data);

$item_value_data = array (
    'attribute_set' =>  'Default',
    'group' => 'ShipHawk Attributes',
    'label'    => 'Item Value',
    'visible'     => true,
    'type'     => 'varchar',
    'apply_to'          => 'simple',
    'frontend_class' => 'validate-number',
    'input'    => 'text',
    'system'   => false,
    'required' => false,
    'user_defined' => 1,
);

$installer->addAttribute('catalog_product','shiphawk_item_value', $item_value_data);

/* use as separator too */
$type_of_product_value = array (
    'attribute_set' =>  'Default',
    'group' => 'ShipHawk Attributes',
    'label'    => 'Origin Contact:',
    'visible'     => true,
    'type'     => 'varchar',
    'apply_to'          => 'simple',
    /*'input_renderer'    => 'shiphawk_shipping/catalog_product_helper_form_disabled',//definition of renderer*/
    'input'    => 'text',
    'system'   => false,
    'required' => false,
    'user_defined' => 1,
);

$installer->addAttribute('catalog_product','shiphawk_type_of_product_value',$type_of_product_value);

$installer->endSetup();