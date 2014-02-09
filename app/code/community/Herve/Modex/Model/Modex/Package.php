<?php
/**
 * This file is part of Herve_Modex for Magento.
 *
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author Hervé Guétin <herve.guetin@gmail.com>
 * @category Herve
 * @package Herve_Modex
 * @copyright Copyright (c) 2014 Hervé Guétin (http://www.herveguetin.com)
 */

/**
 * Modex_Package Model
 * @package Herve_Modex
 */
class Herve_Modex_Model_Modex_Package extends Mage_Core_Model_Abstract
{

// Hervé Guétin Tag NEW_CONST

// Hervé Guétin Tag NEW_VAR

    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'modex_package';

    /**
     * Parameter name in event
     * In observe method you can use $observer->getEvent()->getObject() in this case
     * @var string
     */
    protected $_eventObject = 'modex_package';

    /**
     * Modex_Package Constructor
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('herve_modex/modex_package');
    }

    /**
     * Load package by its name
     *
     * @param string $packageName
     * @return Herve_Modex_Model_Modex_Package
     */
    public function loadByName($packageName)
    {
        $this->load($packageName, 'package_name');
        return $this;
    }

    /**
     * Save package
     *
     * @param array $post
     * @param array $modexFormData
     * @return Herve_Modex_Model_Modex_Package
     */
    public function savePackage($post, $modexFormData)
    {
        $this->loadByName($post['name']);

        // If there is no existing Modex package and Modex export is disabled, do nothing
        if(!$this->getPackageId() && $post['modex_enable'] == '0') {
            return $this;
        }

        $packageConfiguration = unserialize($this->getConfiguration());

        // Encrypt passwords
        $encryptor = Mage::getModel('core/encryption');
        foreach($modexFormData as $field => $data) {
            if(strpos($field, 'password') !== false) {
                if(trim($data) == '' && isset($packageConfiguration[$field])) {
                    $modexFormData[$field] = $packageConfiguration[$field];
                }
                else {
                    $modexFormData[$field] = $encryptor->encrypt($data);
                }
            }
        }

        $data = array(
            'package_name'  => $post['name'],
            'configuration' => serialize($modexFormData)
        );

        $this->addData($data)->save();

        return $this;
    }

    /**
     * Clean Modex packages based on XML files in var/connect
     *
     * @return Herve_Modex_Model_Modex_Package
     */
    public function clean()
    {
        // Gather XML files in var/connect
        $connectPath = Mage::helper('connect')->getLocalPackagesPath();

        if(file_exists($connectPath)) {
            $collector = new Varien_Data_Collection_Filesystem();
            $collector->setCollectDirs(false);
            $collector->addTargetDir($connectPath);
            $collector->setFilesFilter('/\.xml$/');

            $connectFiles = $collector->loadData();
            $connectPackagesFiles = array();
            foreach($connectFiles as $file) {
                $connectPackagesFiles[$file->getBasename()] = $file->getBasename();
            }

            // If there are XML files...
            if(count($connectPackagesFiles) > 0) {
                $modexPackagesFiles = $this->getCollection();
                foreach($modexPackagesFiles as $modexPackagesFile) {
                    if(!in_array($modexPackagesFile->getPackageName() . '.xml', $connectPackagesFiles)) {
                        // ... remove the ones that are in Modex DB and not in var/connect
                        $modexPackagesFile->delete();
                    }
                }
            }
        }

        return $this;
    }

// Hervé Guétin Tag NEW_METHOD

}