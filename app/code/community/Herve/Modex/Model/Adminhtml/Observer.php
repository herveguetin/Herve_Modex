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
 * Adminhtml_Observer Model
 * @package Herve_Modex
 */
class Herve_Modex_Model_Adminhtml_Observer extends Mage_Core_Model_Abstract
{

// Hervé Guétin Tag NEW_CONST

// Hervé Guétin Tag NEW_VAR

// Hervé Guétin Tag NEW_METHOD

    /**
     * Retrieve Connect session
     *
     * @return Mage_Connect_Model_Session
     */
    protected function _getConnectSession()
    {
        return Mage::getSingleton('connect/session');
    }

    /**
     * Clean Modex packages
     */
    public function cleanModexPackages()
    {
        Mage::getModel('herve_modex/modex_package')->clean();
    }

    /**
     * Save modex
     *
     * @param Varien_Event_Observer $observer
     */
    public function saveModex(Varien_Event_Observer $observer)
    {
        $post = $observer->getControllerAction()->getRequest()->getPost();

        // Process Modex when necessary
        $modexFormData = array();
        $modexPostKeys = array_filter(array_keys($post), function($key) {
            return strpos($key, 'modex_') !== false;
        });
        foreach($modexPostKeys as $key) {
            $modexFormData[$key] = $post[$key];
        }
        if(count($modexFormData) > 0) {
            $session = $this->_getConnectSession();

            // Save package config
            Mage::getModel('herve_modex/modex_package')->savePackage($post, $modexFormData);

            // If modex export is enabled, save files
            if($post['modex_enable'] == 1) {
                $result = Mage::getModel('herve_modex/modex')->save();
                if(!$result) {
                    $session->addError(Mage::helper('herve_modex')->__('There was a problem saving files with Modex'));
                }
                else {
                    $session->addSuccess(Mage::helper('herve_modex')->__('Extension has been exported with Modex'));
                }
            }
        }
    }

    /**
     * Update GIT repository
     *
     * @param Varien_Event_Observer $observer
     */
    public function updateGit(Varien_Event_Observer $observer)
    {
        $modex = $observer->getModex();
        if($modex->getGitEnable()) {
            $repository = Mage::getModel('herve_modex/modex_repository_factory')->make($observer->getModex());
            try {
                $repository->update();
            }
            catch(Exception $e) {
                $this->_getConnectSession()->addError(Mage::helper('herve_modex')->__('There was a problem with Git: %s', $e->getMessage()));
            }
        }
    }
}