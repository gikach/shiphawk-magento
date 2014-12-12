<?php
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();



$length_data = array (
    'attribute_set' =>  'Default',
    'group' => 'General',
    'label'    => 'Length',
    'visible'     => true,
    'type'     => 'varchar', // multiselect uses comma-sep storage
    'input'    => 'text',
    'system'   => false,
    'required' => true,
    'user_defined' => 1, //defaults to false; if true, define a group
);

$installer->addAttribute('catalog_product','length',$length_data);

$width_data = array (
    'attribute_set' =>  'Default',
    'group' => 'General',
    'label'    => 'Width',
    'visible'     => true,
    'type'     => 'varchar', // multiselect uses comma-sep storage
    'input'    => 'text',
    'system'   => false,
    'required' => true,
    'user_defined' => 1, //defaults to false; if true, define a group
);

$installer->addAttribute('catalog_product','width',$width_data);

$height_data = array (
    'attribute_set' =>  'Default',
    'group' => 'General',
    'label'    => 'Height',
    'visible'     => true,
    'type'     => 'varchar', // multiselect uses comma-sep storage
    'input'    => 'text',
    'system'   => false,
    'required' => true,
    'user_defined' => 1, //defaults to false; if true, define a group
);

$installer->addAttribute('catalog_product','height',$height_data);

$zip_code_origin_data = array (
    'attribute_set' =>  'Default',
    'group' => 'General',
    'label'    => 'Zip code origin',
    'visible'     => true,
    'type'     => 'varchar', // multiselect uses comma-sep storage
    'input'    => 'text',
    'system'   => false,
    'required' => true,
    'user_defined' => 1, //defaults to false; if true, define a group
);

$installer->addAttribute('catalog_product','zip_code_origin',$zip_code_origin_data);

$type_of_product_data = array (
    'attribute_set' =>  'Default',
    'group' => 'General',
    'label'    => 'Type of Product',
    'visible'     => true,
    'type'     => 'varchar', // multiselect uses comma-sep storage
    'input'    => 'text',
    'system'   => false,
    'required' => true,
    'user_defined' => 1, //defaults to false; if true, define a group
);

$installer->addAttribute('catalog_product','type_of_product',$type_of_product_data);

$shiphawk_id_data = array (
    'attribute_set' =>  'Default',
    'label'    => 'Shiphawk Id',
    'visible'     => false,
    'type'     => 'varchar', // multiselect uses comma-sep storage
    'input'    => 'text',
    'system'   => false,
    'required' => false,
    'user_defined' => false, //defaults to false; if true, define a group
);

$installer->addAttribute('catalog_product','shiphawk_id',$shiphawk_id_data);

$installer->endSetup();