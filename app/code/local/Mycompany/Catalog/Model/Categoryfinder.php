<?php

class Mycompany_Catalog_Model_Categoryfinder
{
    /**
     * @var array
     */
    protected $_categories = array();

    /**
     * @var array
     */
    protected $_categoriesWithRoots = array();

    /**
     * Populates the models properties with category information.
     */
    public function __construct()
    {
        $this->_initCategories();
    }


    /**
     * Finds a subcategory id from a path string
     *
     * @param $string
     * @return bool
     */
    public function getIdFromPath($string)
    {
        if (in_array($string, array_keys($this->_categories))) {
            return $this->_categories[$string];
        }

        return false;
    }

    /**
     * Returns all valid path strings
     *
     * @return array
     */
    public function getAllPaths()
    {
        return array_keys($this->_categories);
    }

    /**
     * Initialize categories text-path to ID hash.
     *
     * @return Mage_ImportExport_Model_Import_Entity_Product
     */
    protected function _initCategories()
    {
        $collection = Mage::getResourceModel('catalog/category_collection')->addNameToResult();
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */
        foreach ($collection as $category) {
            $structure = explode('/', $category->getPath());

            $pathSize = count($structure);
            if ($pathSize > 1) {
                $path = array();
                for ($i = 1; $i < $pathSize; $i++) {
                    try{

                        if(is_object($collection->getItemById($structure[$i]))){
                    $path[] = $collection->getItemById($structure[$i])->getName();
                        }
                        else{
                           // echo $structure[$i]. PHP_EOL;
                        }
                    } catch(Exception $e){ echo $e->getMessage. PHP_EOL; echo $structure[$i];  }

                }
                $rootCategoryName = array_shift($path);
                if (!isset($this->_categoriesWithRoots[$rootCategoryName])) {
                    $this->_categoriesWithRoots[$rootCategoryName] = array();
                }
                $index = implode('/', $path);
                $this->_categoriesWithRoots[$rootCategoryName][$index] = $category->getId();
                if ($pathSize > 2) {
                    $this->_categories[$index] = $category->getId();
                }
            }
        }
        return $this;
    }

}