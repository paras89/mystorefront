<?php
require_once dirname(__FILE__) . '/abstract.php';

class Mycompany_Import_Categories extends Mage_Shell_Abstract
{

    const STORE = 1;
    /*
     * @var string
     */
    protected $_logFileName = 'category_import.log';
    protected $category = array();
    protected $subCategories = array();
    protected $parentCategories = array();
    protected $categoryTree = array();

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

        // Loop through each Row of the data to form category tree and create whichever categories aren't already created.
        while (($data = fgetcsv($fp)) != null) {

            if (!in_array(trim($data[3]), $this->parentCategories)) {

                // This means the "Store" category hasn't been set up. Set it up.
                $this->parentCategories[] = trim($data[3]);
                $this->categoryTree[] = trim($data[3]);
                $this->_createCategory(trim($data[3])); // This call creates the Parent Category.
            }

            // Get the category node for parent category and subcategory.
            $dir = trim($data[3]) . trim($data[4]);
            if (!in_array($dir, $this->categoryTree)) {
                // This is a new node, create sub category.
                $this->category[] = $dir;
                $this->_createCategory(trim($data[3]), trim($data[4]));
                $this->categoryTree[] = trim($data[3]) . trim($data[4]);

            }
            // Get ternary category Node for parent category, category, subcategory
            $subdir = trim($data[3]) . trim($data[4]) . trim($data[5]);
            if (!in_array($subdir, $this->categoryTree)) {

                $this->categoryTree[] = $subdir;
                $this->_createCategory(trim($data[3]), trim($data[4]), trim($data[5]));
                $this->subCategories[] = trim($data[5]);

            }

        }

        $message = 'Import script completed successfully';
        echo $message;
        Mage::log($message, null, $this->_logFileName);
    }

    /**
     *
     * Create Categories with particular name.
     * @param $parentCategory
     * @param null $category
     * @param null $subCategory
     */
    protected function _createCategory($parentCategory, $category = null, $subCategory = null)
    {
        $categoryModel = Mage::getModel('catalog/category');
        if (isset($subCategory)) {
            // Create a subcategory.
            $categoryName = $subCategory;
            $parentCategory = $category;
            $parentID = $this->category[$category];

        } elseif (!isset($category)) {
            // Create Root Parent Category - Movies.
            $categoryName = $parentCategory;
            $parentID = Mage::app()->getStore(self::STORE)->getRootCategoryId();
        } else {
            // Create parent Category.
            $categoryName = $category;
            $parentID = $this->parentCategories[$parentCategory];
        }
        $parentCategoryModel = Mage::getModel('catalog/category')->load($parentID);
        $categoryModel->setName($categoryName)
            ->setStoreId(self::STORE)
            ->setUrlKey(str_replace(' ', '-', $categoryName))
            ->setIsActive(1)
            ->setPath($parentCategoryModel->getPath())
            ->setIncludeInMenu(1)
            ->save();


        if (isset($subCategory)) {
            $this->subCategories[$subCategory] = $categoryModel->getId();
        } elseif (isset($category)) {
            $this->category[$category] = $categoryModel->getId();
        }
        if (!isset($category)) {
            $this->parentCategories[$parentCategory] = $categoryModel->getId();
        }
        return;
    }


    public function usageHelp()
    {
        return <<<USAGE
Usage:  php import_categories.php --file movies-catalog.csv
USAGE;
    }
}

$shell = new Mycompany_Import_Categories();
$shell->run();