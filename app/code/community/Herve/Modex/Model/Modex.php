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
 * Modex Model
 * @package Herve_Modex
 */
class Herve_Modex_Model_Modex extends Mage_Connect_Model_Extension
{
    /**
     * Dir name for temporary archive unpacking
     */
    const TMP_DIR = 'tmp';

// Hervé Guétin Tag NEW_CONST
    /**
     * Modex data
     *
     * @var Varien_Object
     */
    protected $_modexData;
    /**
     * Files currently in export dir
     *
     * @var array
     */
    protected $_currentFiles = array();
    /**
     * Files currently in tmp dir
     *
     * @var array
     */
    protected $_tmpFiles = array();
    /**
     * IO object
     *
     * @var Varien_Io_File
     */
    protected $_io;



// Hervé Guétin Tag NEW_VAR

    public function __construct()
    {
        parent::__construct();
        $this->_populateData();
    }

    /**
     * Populate data
     *
     * @return Herve_Modex_Model_Modex
     */
    protected function _populateData()
    {
        $formData = Mage::getSingleton('connect/session')->getCustomExtensionPackageFormData();

        $this->addData($formData);

        if(isset($formData['name'])) {
            $modexPackage = Mage::getModel('herve_modex/modex_package')->loadByName($formData['name']);
            $modexConfiguration = unserialize($modexPackage->getConfiguration());

            $modexData = new Varien_Object();

            if($modexConfiguration && is_array($modexConfiguration)) {
                foreach($modexConfiguration as $field => $value) {
                    $field = str_replace('modex_', '', $field);
                    $modexData->setData($field, $value);
                }
            }

            $this->addData($modexData->getData());
            $this->setPath($this->getPath() . DS . $this->getName());
            $this->setTmpDir($this->getPath() . DS . self::TMP_DIR);

            $this->_modexData = $modexData;
        }

        return $this;
    }

    /**
     * Save extension to Modex path
     *
     * @return Herve_Modex_Model_Modex
     */
    public function save()
    {
        Mage::dispatchEvent('herve_modex_createmodex_before', array('modex' => $this));
        $result = $this->createModex();
        Mage::dispatchEvent('herve_modex_createmodex_after', array('modex' => $this, 'result' => $result));

        return $result;
    }

    /**
     * Retrieve Modex data
     *
     * @return Varien_Object
     */
    public function getModexData()
    {
        return $this->_modexData;
    }

    /**
     * Create modex files
     *
     * @return boolean
     */
    public function createModex()
    {
        try {

            // Get path where to export: path as posted + extension name
            $path = $this->getPath();
            if(!file_exists($path)) {
                $this->getIo()->mkdir($path);
            }

            // Generate connect-like extension archive
            if (!$this->getPackageXml()) {
                $this->generatePackageXml();
            }
            $this->getPackage()->save($this->getPath());

            // Now that we have a tgz, update some data
            $this->setArchivePath($this->getPath() . DS . $this->getName() . '-' . $this->getPackage()->getVersion() . '.tgz');

            // Store files that are already present in dir to $_currentFiles
            $this->_collectFiles($path, '_currentFiles');

            // Unpack extension tgz
            $archivator = new Mage_Archive();
            $this->getIo()->mkdir($this->getTmpDir());
            $archivator->unpack($this->getArchivePath(), $this->getTmpDir());

            // Once archive is unpacked, move extracted files to $path and store files that are in tmp dir to $_tmpFiles
            $this->_processTmpFiles();

            // Remove files that may have been deleted from extension since last export and delete some other files...
            $this->_removeUnusedFiles();

            // Update README.md file
            $this->_updateReadme();

            // Update Modman file
            $this->_updateModman();

            return true;
        }
        catch(Exception $e) {
            die($e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve Varien_Io_File object
     *
     * @return Varien_Io_File
     */
    public function getIo()
    {
        if(is_null($this->_io)) {
            $this->_io = new Varien_Io_File();
        }

        return $this->_io;
    }

    /**
     * Store files from a given path to the $dest property
     *
     * @param string $path
     * @param string $dest
     * @return array
     */
    protected function _collectFiles($path, $dest)
    {
        $collector = new Varien_Data_Collection_Filesystem();
        $collector->setCollectDirs(true);
        $collector->addTargetDir($path);

        $items = $collector->loadData();

        $files = array();
        foreach($items as $item) {
            $files[] = $item->getFilename();
        }

        $this->$dest = $files;

        return $this->$dest;
    }

    /**
     * Move tmp files to real destination dir
     *
     * @return Herve_Modex_Model_Modex
     */
    protected function _processTmpFiles()
    {
        $io = $this->getIo();

        $tmpFiles = $this->_collectFiles($this->getTmpDir(), '_tmpFiles');

        foreach($tmpFiles as $k => $tmpFile) {

            // Rework $tmpFile in order to remove tmp dir in filename. This is useful for removing unused files.
            // @see Herve_Modex_Model_Modex::_removeUnusedFiles()
            $filename = str_replace(self::TMP_DIR . DS, '', $tmpFile);
            $tmpFiles[$k] = $filename;

            if(is_dir($tmpFile)) {
                $io->mkdir($filename);
            }
            else {
                copy($tmpFile, $filename);
            }
        }

        // Update tmpFiles with new reworked filenames
        $this->_tmpFiles = $tmpFiles;

        $io->rmdir($this->getTmpDir(), true);

        return $this;
    }

    /**
     * Remove files that may have been deleted from extension since last export
     * and delete some other files...
     *
     * @return Herve_Modex_Model_Modex
     */
    protected function _removeUnusedFiles()
    {
        $io = $this->getIo();

        // Delete files that are in current dir but really removed from extension
        $itemsToRemove = array_diff($this->_currentFiles, $this->_tmpFiles);

        foreach($itemsToRemove as $itemToRemove) {

            // If creating .tgz archive is enabled, do not remove file
            if($this->getArchive() && $itemToRemove == $this->getArchivePath()) {
                continue;
            }

            if(is_dir($itemToRemove)) {
                $io->rmdir($itemToRemove, true);
            }
            else {
                @unlink($itemToRemove);
            }
        }

        // Rename package.xml file or remove it
        $packageFilename = $this->getPath() . DS . 'package.xml';
        if($this->getCreatePackage()) {
            $packageName = $this->getName() . '.xml';
            $connectPath = $this->getPath() . DS . 'var' . DS . 'connect';
            $io->mkdir($connectPath);
            @rename($packageFilename, $connectPath . DS . $packageName);
        }
        else {
            unlink($packageFilename);
        }

        return $this;
    }

    /**
     * Create, delete, update README.md file
     *
     * @return Herve_Modex_Model_Modex
     */
    protected function _updateReadme()
    {
        $readmeFile = $this->getPath() . DS . 'README.md';

        if(!$this->hasReadme() || $this->getReadme() == 0 || !$this->getReadme()) {
            @unlink($readmeFile);
            return $this;
        }

        $io = $this->getIo();
        $io->open(array('path' => $this->getPath()));
        $io->streamOpen($readmeFile);
        $io->streamWrite($this->getDescription());
        $io->streamClose();

        return $this;
    }

    /**
     * Create, delete, update Modman file
     *
     * @return Herve_Modex_Model_Modex
     */
    protected function _updateModman()
    {
        $modmanFile = $this->getPath() . DS . 'modman';

        if(!$this->hasModman() || $this->getModman() == 0 || !$this->getModman()) {
            @unlink($modmanFile);
            return $this;
        }

        $this->getPackage()->clearContents();
        $contents = $this->getData('contents');
        $modmanLines = array();

        foreach ($contents['target'] as $i=>$target) {
            if (0===$i) {
                continue;
            }

            switch($target) {
                case 'magelocal':
                    $prefix = 'app/code/local/';
                    break;
                case 'magecommunity':
                    $prefix = 'app/code/community/';
                    break;
                case 'magecore':
                    Mage::throwException('Please do not modify Core!');
                    break;
                case 'magedesign':
                    $prefix = 'app/design/';
                    break;
                case 'mageetc':
                    $prefix = 'app/etc/';
                    break;
                case 'magelib':
                    $prefix = 'lib/';
                    break;
                case 'magelocale':
                    $prefix = 'app/locale/';
                    break;
                case 'magemedia':
                    $prefix = 'media/';
                    break;
                case 'mageskin':
                    $prefix = 'skin/';
                    break;
                default:
                    $prefix = '';
                    break;
            }

            $modmanLines[] = $prefix . $contents['path'][$i] . ' ' . $prefix . $contents['path'][$i];
        }

        $modmanLines = implode("\n", $modmanLines);

        $io = $this->getIo();
        $io->open(array('path' => $this->getPath()));
        $io->streamOpen($modmanFile);
        $io->streamWrite($modmanLines);
        $io->streamClose();

        return $this;
    }

// Hervé Guétin Tag NEW_METHOD

}