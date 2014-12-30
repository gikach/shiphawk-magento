<?php
$installer = Mage::getResourceModel('sales/setup','sales_setup'); //!!

$installer->startSetup();

$installer->addAttribute("order", "shiphawk_api_id", array("type"=>"varchar"));
$installer->addAttribute("quote", "shiphawk_api_id", array("type"=>"varchar"));


$installer->endSetup();