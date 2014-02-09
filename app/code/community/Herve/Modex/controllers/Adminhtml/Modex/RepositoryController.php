<?php
/**
 * This file is part of Herve_Modex for Magento.
 *
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author Hervé Guétin <herve.guetin@gmail.com> <@herveguetin>
 * @category Herve
 * @package Herve_Modex
 * @copyright Copyright (c) 2014 Hervé Guétin (http://www.herveguetin.com)
 */

/**
 * Adminhtml_Modex_Repository Controller
 * @package Herve_Modex
 */
class Herve_Modex_Adminhtml_Modex_RepositoryController extends Mage_Adminhtml_Controller_Action
{

// Hervé Guétin Tag NEW_CONST

// Hervé Guétin Tag NEW_VAR

    /**
     * Push to repo
     */
    public function pushAction()
    {
        $session = Mage::getSingleton('connect/session');

        try {
            $modex = Mage::getModel('herve_modex/modex');
            $repository = Mage::getModel('herve_modex/modex_repository_factory')->make($modex);
            $repository->push();
            $session->addSuccess(Mage::helper('herve_modex')->__('Extension has been pushed'));
        }
        catch(Exception $e) {
            $session->addError(Mage::helper('herve_modex')->__('Extension has not been pushed: %s', $e->getMessage()));
        }

        $this->_redirect('*/extension_custom/edit');
    }

// Hervé Guétin Tag NEW_METHOD

    /**
     * Is allowed?
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }

}