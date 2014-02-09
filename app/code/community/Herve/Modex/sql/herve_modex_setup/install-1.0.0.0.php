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

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/**
 * Create table 'herve_modex/modex_package'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('herve_modex/modex_package'))
    ->addColumn('package_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Package Id')
    ->addColumn('package_name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    ), 'Package Name')
    ->addColumn('configuration', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    ), 'Modex Configuration')
    ->setComment('Modex Items');
$installer->getConnection()->createTable($table);

$installer->endSetup();
