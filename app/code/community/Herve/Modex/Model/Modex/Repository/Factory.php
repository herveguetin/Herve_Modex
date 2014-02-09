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
 * Herve_Modex_Model_Modex_Repository_Factory Model
 * @package Herve_Modex
 */

include_once 'Herve/Modex/Model/Modex/Repository.php';

class Herve_Modex_Model_Modex_Repository_Factory extends Mage_Core_Model_Abstract
{

// Hervé Guétin Tag NEW_CONST

// Hervé Guétin Tag NEW_VAR

    public function make(Herve_Modex_Model_Modex $modex)
    {
        // If there is no binary defined for Git
        if(!$modex->getGitBin() || trim($modex->getGitBin()) == '') {
            $gitBin = Mage::getConfig()->getNode('herve_modex/repository/git/bin')->asArray();
            $modex->setGitBin($gitBin);
        }

        $repository = new TQ\ModexRepository(null, null, $modex);

        return $repository;
    }
// Hervé Guétin Tag NEW_METHOD

}