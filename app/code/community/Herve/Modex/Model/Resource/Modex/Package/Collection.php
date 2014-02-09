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
 * Collection of Modex_Package
 * @package Herve_Modex
 */
class Herve_Modex_Model_Resource_Modex_Package_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

// Hervé Guétin Tag NEW_CONST

// Hervé Guétin Tag NEW_VAR

    /**
     * Modex_Package Collection Resource Constructor
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('herve_modex/modex_package');
    }

// Hervé Guétin Tag NEW_METHOD

}