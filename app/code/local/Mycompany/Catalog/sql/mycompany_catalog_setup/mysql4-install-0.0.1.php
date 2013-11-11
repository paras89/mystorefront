<?php
/**
 * Created by JetBrains PhpStorm.
 * User: psood
 * Date: 11/9/13
 * Time: 5:10 PM
 * To change this template use File | Settings | File Templates.
 */
$installer = $this;

/** @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$installer->startSetup();
try {
    $connection->beginTransaction();
    $installer->addAttribute('catalog_product', 'shipping_duration', array(
            'attribute_set' => 'Default',
            'group' => 'General',
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'Shipping Duration',
            'input' => 'text',
            'class' => '',
            'source' => '',
            'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => true,
            'visible_in_advanced_search' => false,
            'unique' => false,
            'apply_to' => 'simple,configurable,virtual',
            'configurable' => false,
        )
    );
    $connection->commit();
    Mage::log('shipping duration attribute created successfully',null,'attrubute_creation.log');
} catch (Exception $e) {
    $connection->rollBack();
    Mage::logException($e);
}
$installer->endSetup();
