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
 * Adminhtml_Extension_Custom_Edit_Tab_Modex Block
 * @package Herve_Modex
 */
class Herve_Modex_Block_Adminhtml_Extension_Custom_Edit_Tab_Modex extends Mage_Connect_Block_Adminhtml_Extension_Custom_Edit_Tab_Abstract
{

// Hervé Guétin Tag NEW_CONST

    /**
     * Modex configuration saved in DB
     *
     * @var array
     */
    protected $_modexConfiguration = array();

    /**
     * Password fields that have data
     *
     * @var array
     */
    protected $_passwords = array();

// Hervé Guétin Tag NEW_VAR

    /**
     * Prepare Modex info form before rendering HTML
     *
     * @return Herve_Modex_Block_Adminhtml_Extension_Custom_Edit_Tab_Modex
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('_modex');

        $yesnoOptions = Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray();

        // Prepare Modex configuration
        $this->_prepareModexConfiguration();

        // Main fieldset

        $mainFieldset = $form->addFieldset('modex_main_fieldset', array(
            'legend'    => Mage::helper('herve_modex')->__('Module Export Information')
        ));

        $mainFieldset->addField('modex_enable', 'select', array(
            'name' => 'modex_enable',
            'label' => Mage::helper('herve_modex')->__('Enable Module Export'),
            'values' => $yesnoOptions,
        ));

        $mainFieldset->addField('modex_path', 'text', array(
            'name'      => 'modex_path',
            'label'     => Mage::helper('herve_modex')->__('Full path'),
            'required'  => true,
            'note'      => Mage::helper('herve_modex')->__('Full server path. Server user for Magento instance must have write permissions.'),
        ));

        $mainFieldset->addField('modex_archive', 'select', array(
            'name'      => 'modex_archive',
            'label'     => Mage::helper('herve_modex')->__('Create .tgz archive'),
            'values' => $yesnoOptions,
        ));

        $mainFieldset->addField('modex_create_package', 'select', array(
            'name'      => 'modex_create_package',
            'label'     => Mage::helper('herve_modex')->__('Create package XML file'),
            'values' => $yesnoOptions,
        ));

        $mainFieldset->addField('modex_readme', 'select', array(
            'name'      => 'modex_readme',
            'label'     => Mage::helper('herve_modex')->__('Create README.md file'),
            'note'      => Mage::helper('herve_modex')->__('Content of README.md is take from extension description in Package Info tab.'),
            'values' => $yesnoOptions,
        ));

        $mainFieldset->addField('modex_modman', 'select', array(
            'name'      => 'modex_modman',
            'label'     => Mage::helper('herve_modex')->__('Create Modman file'),
            'values' => $yesnoOptions,
        ));

        // Git fieldset

        $gitFieldset = $form->addFieldset('modex_git_fieldset', array(
            'legend'    => Mage::helper('herve_modex')->__('Version Control System')
        ));

        $gitFieldset->addField('modex_git_enable', 'select', array(
            'name'      => 'modex_git_enable',
            'label'     => Mage::helper('herve_modex')->__('Use Git VCS'),
            'values' => $yesnoOptions,
        ));

        $gitFieldset->addField('modex_git_bin', 'text', array(
            'name'      => 'modex_git_bin',
            'label'     => Mage::helper('herve_modex')->__('Full path to Git bin'),
            'note'      => Mage::helper('herve_modex')->__('/usr/bin/git is used if left empty'),
        ));

        $gitFieldset->addField('modex_git_commit_msg', 'text', array(
            'name'      => 'modex_git_commit_msg',
            'label'     => Mage::helper('herve_modex')->__('Git Commit Message'),
            'note'      => Mage::helper('herve_modex')->__('Release Notes are used if left empty'),
        ));

        $gitFieldset->addField('modex_git_remote_enable', 'select', array(
            'name'      => 'modex_git_remote_enable',
            'label'     => Mage::helper('herve_modex')->__('Use Remote Repository'),
            'values' => $yesnoOptions,
        ));

        $gitFieldset->addField('modex_git_remote_name', 'text', array(
            'name'      => 'modex_git_remote_name',
            'label'     => Mage::helper('herve_modex')->__('Git Remote Name (ie: origin)'),
            'required'  => true,
        ));

        $gitFieldset->addField('modex_git_remote_branch', 'text', array(
            'name'      => 'modex_git_remote_branch',
            'label'     => Mage::helper('herve_modex')->__('Git Remote Branch'),
            'note'      => Mage::helper('herve_modex')->__('master is used if left empty'),
        ));

        $gitFieldset->addField('modex_git_remote_url', 'text', array(
            'name'      => 'modex_git_remote_url',
            'label'     => Mage::helper('herve_modex')->__('Git Remote Url (http or https)'),
            'required'  => true,
            'class'     => 'validate-url',
        ));

        $gitFieldset->addField('modex_git_remote_username', 'text', array(
            'name'      => 'modex_git_remote_username',
            'label'     => Mage::helper('herve_modex')->__('Git Remote Username'),
            'required'  => true,
        ));

        $gitFieldset->addField('modex_git_remote_password', 'password', array(
            'name'      => 'modex_git_remote_password',
            'label'     => Mage::helper('herve_modex')->__('Git Remote Password'),
            'required'  => (isset($this->_passwords['modex_git_remote_password'])) ? false : true,
            'note'      => (isset($this->_passwords['modex_git_remote_password'])) ? Mage::helper('herve_modex')->__('Password is saved. Retype and save extension to update.') : '',
        ));

        if(isset($this->_passwords['modex_git_remote_password'])) {
            $gitFieldset->addField('modex_git_remote_push', 'note', array(
                'text' => $this->getButtonHtml(
                        Mage::helper('herve_modex')->__('Force Push to Remote Repository'),
                        "setLocation('{$this->getUrl('*/modex_repository/push')}')",
                        'save'
                    )
            ));
        }

        // Manage field visibility dependencies
        $this->_createDependenciesBlock($form, $mainFieldset, $gitFieldset);

        // Form values
        $form->setValues($this->_modexConfiguration);
        $this->setForm($form);

        return $this;
    }

    /**
     * Prepare Modex configuration from Modex package data
     *
     * @return Herve_Modex_Block_Adminhtml_Extension_Custom_Edit_Tab_Modex
     */
    protected function _prepareModexConfiguration()
    {
        $passwords = array();
        $modexData = Mage::getModel('herve_modex/modex')->getModexData();

        if($modexData) {
            foreach($modexData->getData() as $field => $data) {
                $modexConfiguration['modex_' . $field] = $data;
            }

            // If there is no value for Git binary
            if(!isset($modexConfiguration['modex_git_bin']) || trim($modexConfiguration['modex_git_bin']) == '') {
                $modexConfiguration['modex_git_bin'] = Mage::getConfig()->getNode('herve_modex/repository/git/bin');
            }

            // Remove passwords
            foreach($modexConfiguration as $field => $data) {
                if(strpos($field, 'password') !== false) {
                    $passwords[$field] = true;
                    unset($modexConfiguration[$field]);
                }
            }

            $this->_modexConfiguration = $modexConfiguration;
            $this->_passwords = $passwords;
        }

        return $this;
    }

    /**
     * Create JS dependencies block
     *
     * @param Varien_Form $form
     * @param Varien_Form_Element_Fieldset $mainFieldset
     * @param Varien_Form_Element_Fieldset $gitFieldset
     * @return Herve_Modex_Block_Adminhtml_Extension_Custom_Edit_Tab_Modex
     */
    protected function _createDependenciesBlock($form, $mainFieldset, $gitFieldset)
    {
        $htmlPrefix = $form->getHtmlIdPrefix();
        $dependenciesBlock = Mage::app()->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        $dependenciesBlock->addFieldMap($htmlPrefix . 'modex_enable', 'enable');

        $mainFielsetFields = $mainFieldset->getSortedElements();
        foreach($mainFielsetFields as $field) {
            $fieldId = $field->getId();
            if($fieldId != 'modex_enable') {
                $dependenciesBlock->addFieldMap($htmlPrefix . $fieldId, $fieldId . '_dep');
                $dependenciesBlock->addFieldDependence($fieldId . '_dep', 'enable', '1');
            }
        }

        $dependenciesBlock->addFieldMap($htmlPrefix . 'modex_git_enable', 'git_enable');
        $dependenciesBlock->addFieldMap($htmlPrefix . 'modex_git_remote_enable', 'git_remote_enable');
        $gitFielsetFields = $gitFieldset->getSortedElements();
        foreach($gitFielsetFields as $field) {
            $fieldId = $field->getId();
            if($fieldId != 'modex_git_enable') {
                $dependenciesBlock->addFieldMap($htmlPrefix . $fieldId, $fieldId . '_dep');
                $dependenciesBlock->addFieldDependence($fieldId . '_dep', 'git_enable', '1');
                if(strpos($fieldId, 'modex_git_remote') !== false && $fieldId != 'modex_git_remote_enable') {
                    $dependenciesBlock->addFieldDependence($fieldId . '_dep', 'git_remote_enable', '1');
                }
            }
        }

        Mage::app()->getLayout()->getBlock('content')->append($dependenciesBlock);

        return $this;
    }

    /**
     * Get Tab Label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('herve_modex')->__('Module Export (Modex)');
    }

    /**
     * Get Tab Title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('herve_modex')->__('Module Export (Modex)');
    }

// Hervé Guétin Tag NEW_METHOD

}