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
    $installer->addAttribute('catalog_product', 'sku_version', array(
            'attribute_set' => 'Default',
            'group' => 'General',
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => 'Version',
            'input' => 'select',
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
            'configurable' => true,
            'is_configuable' => true,
            'option'            => array (
                'value' => array(
                    'optionone'        =>array(0=>'SKUA'),
                    'optiontwo'        =>array(0=>'SKUB'),
                    'optionthree'   =>array(0=>'SKUC'),
                    'optionfour'    =>array(0=>'SKUD'),
                    'optionfive'    =>array(0=>'SKUE'),
                    'optionsix'        =>array(0=>'SKUF'),
                    'optionseven'    =>array(0=>'SKUG'),
                    'optionseven'    =>array(0=>'SKUH')
                )
            )
        )
    );
    $connection->commit();

} catch (Exception $e) {
    $connection->rollBack();
    Mage::logException($e);
}
$installer->endSetup();
