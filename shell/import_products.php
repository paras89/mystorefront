<?php

require_once dirname(__FILE__) . '/abstract.php';

class Mycompany_Import_Products extends Mage_Shell_Abstract
{

    const STORE = 1;
    /*
     * @var string
     */
    protected $_logFileName = 'product_import.log';
    protected $versionOptions = array();


    public function run()
    {

        error_reporting(E_ALL);

        set_time_limit(0); // No time limit
        ini_set('display_errors', 1);
        ini_set('memory_limit', '2G');

        if (!$this->getArg('file')) {
            echo $this->usageHelp();
            die();
        }

        $file = $this->getArg('file');


        if (!file_exists($file)) {
            die('File does not exist.');
        }

        if (!($fp = fopen($file, 'r'))) {
            die('Cannot open file.');
        }
        //Skip the column headers.
        fgetcsv($fp);
        $index = 0;
        $product = Mage::getModel('catalog/product');
        $this->_setVersionOptions();

        // For each row of data add product to store.
        while (($data = fgetcsv($fp)) != null) {
            $index++;
            $product->setData(array());
            $configurableProduct = null;
            if (trim($data[0]) != '') {
                $configurableProduct = $this->_createConfigurableProduct($data);
            }
            $this->_createProduct(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, $data, $product, $configurableProduct);
        }

        $message = 'Import script completed successfully';
        echo $message;
        Mage::log($message, null, $this->_logFileName);
    }

    /**
     * Return category Tree string for a particular row of product data.
     * @param $data
     * @return string
     */
    protected function _getCategoryTree($data)
    {
        return trim(strip_tags($data[3])) . '/' . trim(strip_tags($data[4])) . '/' . trim(strip_tags($data[5]));
    }

    /**
     * Initialize Version Options for Version attributee.
     */
    protected function _setVersionOptions()
    {
        $attribute_model = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
        $attribute_code = $attribute_model->getIdByCode('catalog_product', 'sku_version');
        $attribute = $attribute_model->load($attribute_code);
        $attribute_options_model->setAttribute($attribute);
        $options = $attribute_options_model->getAllOptions(false);
        foreach ($options as $option) {
            $this->versionOptions[] = $option['value'];
        }
    }

    /**
     * Create configurable product if not alraeady present.
     * @param $data
     * @return mixed
     */
    protected function _createConfigurableProduct($data)
    {
        $Configproduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $data[0]);

        if ($Configproduct !== false) {
            // Configurable product for this simple product already exists.
            return $Configproduct;
        }
        $product = Mage::getModel('catalog/product');
        // We are creating configurable product, so put configurable sku in $data[1]
        $data[1] = $data[0];
        return $this->_createProduct(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, $data, $product);
    }

    /**
     * Create Product type based on type: simple/configurable.
     * @param $type
     * @param $data
     * @param $product
     * @param null $configurableProduct
     * @return mixed
     */
    protected function _createProduct($type, $data, $product, $configurableProduct = null)
    {
        $categoryFinder = Mage::getModel('mycompany_catalog/categoryfinder');
        $def_attribute_set = Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId();
        $product->setAttributeSetId($def_attribute_set);
        $product->setName(trim(strip_tags($data[2])));
        $product->setSku(trim(strip_tags($data[1])));
        $product->setPrice(trim(strip_tags($data[6])));
        $product->setShippingDuration(trim(strip_tags($data[7])));
        $product->setTypeId($type);
        $product->setTaxClassId(1);
        $product->setWeight(1.2);
        $product->setDescription(trim(strip_tags($data[2])));
        $product->setShortDescription(trim(strip_tags($data[2])));
        $product->setWebsiteIds(array(1));
        $product->setImage('no_selection');
        $version = explode('-', trim($data[1]));
        $version = $version[1];
        $product->setSkuVersion($this->versionOptions[$version]);

        $stock_data = array('use_config_manage_stock' => 1,
            'qty' => 100,
            'min_qty' => 0,
            'manage_stock' => 1,
            'use_config_min_qty' => 1,
            'min_sale_qty' => 0,
            'use_config_min_sale_qty' => 1,
            'max_sale_qty' => 9999,
            'use_config_max_sale_qty' => 1,
            'is_qty_decimal' => 0,
            'backorders' => 0,
            'notify_stock_qty' => 1,
            'is_in_stock' => 1);
        $product->setData('stock_data', $stock_data);
        $categoryTree = $this->_getCategoryTree($data);
        if($cat = $categoryFinder->getIdFromPath($categoryTree)){
           $product->setCategoryIds($cat);
        }
        $product->setStatus(1);
        if ($type == 'simple' && trim($data[0]) != '') {
            $visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
        } else {
            $product->setHasOptions(1);
            $product->setRequiredOptions(1);
            $visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
            $product->setMsrpEnabled(2);
            $product->setMsrpDisplayActualPriceType(4);

        }
        $product->setVisibility($visibility);
        if ($type == 'configurable') {
            $configurableAttributesData = array(
                '0' => array('id' => NULL, 'label' => 'Version', 'position' => NULL,
                    'values' => $this->versionOptions,
                    'attribute_id' => 962, 'attribute_code' => 'sku_version', 'frontend_label' => 'Version',
                    'html_id' => 'config_super_product__attribute_0'
                ),

            );
            $product->setConfigurableAttributesData($configurableAttributesData);
        }

        $product->save();
        if (isset($configurableProduct)) {
            $this->_attachProductToConfigurable($product, $configurableProduct);
        }
        return $product;

    }

    /**
     * Associate Configurable product with Child simple product.
     * @param $childProduct
     * @param $configurableProduct
     */
    private function _attachProductToConfigurable($childProduct, $configurableProduct)
    {

        $instance = $configurableProduct->getTypeInstance();
        $configProduct = $configurableProduct;
        $data = $configurableProduct->getData();
        $loader = Mage::getResourceModel('catalog/product_type_configurable')->load($configurableProduct, $configurableProduct->getId());

        $ids = $configurableProduct->getTypeInstance()->getUsedProductIds();
        $newids = array();
        foreach ($ids as $id) {
            $newids[$id] = 1;
        }
        $configProduct->setData($data);
        $newids[$childProduct->getId()] = 1;
        $loader->saveProducts($configProduct, array_keys($newids));
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php import_products.php --file movies-catalog.csv
USAGE;
    }
}

$shell = new Mycompany_Import_Products();
$shell->run();