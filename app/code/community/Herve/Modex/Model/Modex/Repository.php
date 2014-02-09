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
 * ModexRepository Model
 * @package Herve_Modex
 */

/**
 * @namespace
 */
namespace TQ;

/**
 * Includes and spl_autoload below are needed in order to load PSR-0 libraries
 * and put autoload on top of autoload stack thus avoiding conflict with Varien_Autoload
 */
include_once "PHP-Stream-Wrapper-for-Git/src/TQ/Autoloader.php";
spl_autoload_register(array(new Autoloader, 'load'), null, true);


class ModexRepository extends Git\Repository\Repository
{
    /**
     * @var Varien_Object
     */
    protected $_modex;
    /**
     * @var bool
     */
    protected $_canRemote;
    /**
     * @var Varien_Object
     */
    protected $_modexRemote;
    /**
     * @var \Mage_Core_Model_Encryption
     */
    protected $_encryptor;

    /**
     * Overloaded constructor in order to enable instanciation by factory
     *
     * @param string $repositoryPath
     * @param Git\Cli\Binary $git
     * @param \Herve_Modex_Model_Modex $modex
     */
    public function __construct($repositoryPath = null, Git\Cli\Binary $git = null, $modex = null)
    {
        if($modex) {
            $this->_modex = $modex;

            // Add specific data for Modex
            $this->_initGit();

            // Instanciate repository object
            $repository = $this->open($modex->getPath(), $modex->getGitBin(), true);

            // Retrieve useful stuff for parent constructor
            $git = $repository->getGit();
            $repositoryPath = $modex->getPath();
        }

        parent::__construct($repositoryPath, $git);
    }

    /**
     * Retrieve author from Connect extension configuration
     *
     * @TODO UTF-8 Manage accents
     * @return string
     */
    public function getModexAuthor()
    {
        $connectAuthors = $this->_modex->getAuthors();
        return utf8_decode($connectAuthors['name'][0]) . ' <' . $connectAuthors['email'][0] . '>';
    }

    /**
     * Retrieve data from remote
     *
     * @return Varien_Object
     */
    public function getModexRemote()
    {
        return $this->_modexRemote;
    }

    /**
     * Set data for remote
     *
     * @param \Varien_Object $modexRemote
     * @return $this
     */
    public function setModexRemote(\Varien_Object $modexRemote)
    {
        $this->_modexRemote = $modexRemote;
        return $this;
    }

    /**
     * Retrieve all remotes
     *
     * @return array
     */
    public function getRemotes()
    {
        $result = $this->getGit()->{'remote'}($this->getRepositoryPath(), array(
            '-v'
        ));
        $result->assertSuccess(sprintf('Cannot remote "%s"', $this->getRepositoryPath()));

        $tmp = $result->getStdOut();

        preg_match_all('/([a-z]*)\h(.*)\h\((.*)\)/', $tmp, $matches);

        return $matches;
    }

    /**
     * Push to repo
     *
     * @throws \Exception
     */
    public function push()
    {
        $modexRemote = $this->getModexRemote();
        $branch = ($modexRemote->getBranch()) ? $modexRemote->getBranch() : 'master';

        // Add user data to remote url in order to be able to push without prompt nor ssh keys
        $this->_switchRemoteUrl(true);

        $result = $this->getGit()->{'push'}($this->getRepositoryPath(), array(
            $modexRemote->getName(),
            'master:' . $branch,
        ));

        // Remove user data from remote url
        $this->_switchRemoteUrl(false);

        if ($result->getReturnCode() > 0) {
            $stdErr = $this->_obfuscatePassword($result->getStdErr());
            throw new \Exception('Impossible to push to remote repository:<br/><i>' . $stdErr . '</i>');
        }
    }

    /**
     * Update repo
     */
    public function update()
    {
        if(!$this->isDirty()) {
            return;
        }

        $modex = $this->_modex;

        // Git add
        $this->add();

        // Git commit
        $commitMsg = 'Commit from ' . now();
        if($modex->getNotes()) {
            $commitMsg = $modex->getNotes();
        }
        if($modex->getGitCommitMsg()) {
            $commitMsg = $modex->getGitCommitMsg();
        }
        $this->commit($commitMsg, null, $this->getModexAuthor());

        // Push to remote
        if($this->_canRemote) {

            // Check if remote exists, if not, create it
            $this->_checkRemote();

            // Push
            $this->push();
        }
    }

    /**
     * Init Git repository object
     *
     * @return Git\Repository\Repository|ModexRepository
     */
    protected function _initGit()
    {
        $modex = $this->_modex;

        // Manage author
        $this->setAuthor($this->getModexAuthor());

        // Manage remote
        if($modex->getGitRemoteEnable()) {

            $this->_canRemote = true;

            $modexRemoteData = array(
                'name'      => $modex->getGitRemoteName(),
                'branch'    => $modex->getGitRemoteBranch(),
                'url'       => $modex->getGitRemoteUrl(),
                'username'  => $modex->getGitRemoteUsername(),
                'password'  => $modex->getGitRemotePassword(),
            );
            $modexRemote = new \Varien_Object($modexRemoteData);
            $this->setModexRemote($modexRemote);
        }

        return $this;
    }

    /**
     * Create remote if does not exist
     */
    protected function _checkRemote()
    {
        $modexRemote = $this->getModexRemote();

        if(!$this->_hasRemote($modexRemote->getName())) {
            $result = $this->getGit()->{'remote'}($this->getRepositoryPath(), array(
                'add',
                $modexRemote->getName(),
                $modexRemote->getUrl(),
            ));

            $result->assertSuccess(sprintf('Cannot add remote "%s"', $modexRemote->getName()));
        }
    }

    /**
     * Check if remote exists
     *
     * @param $remoteName
     * @return bool
     */
    protected function _hasRemote($remoteName)
    {
        $remotes = $this->getRemotes();
        $remoteNames = array_unique($remotes[1]);

        return (in_array($remoteName, $remoteNames));
    }

    /**
     * Check if remote branch exists
     *
     * @param string $remoteName
     * @param string $remoteBranchName
     * @return bool
     */
    protected function _hasRemoteBranch($remoteName, $remoteBranchName)
    {
        $localBranch = $remoteName . '/' . $remoteBranchName;
        $remoteBranches = $this->getBranches(self::BRANCHES_REMOTE);

        return (in_array($localBranch, $remoteBranches));
    }

    /**
     * Change remote url from/to original (without user data) to/from unsafe remote url (with user data)
     *
     * @param bool $withUserData
     */
    protected function _switchRemoteUrl($withUserData = false)
    {
        $modexRemote = $this->getModexRemote();
        $remoteUrl = $modexRemote->getUrl();

        if($withUserData) {
            $remoteUrl = $this->_buildUnsafeRemoteUrl();
        }

        $this->getGit()->{'remote'}($this->getRepositoryPath(), array(
            'set-url',
            $modexRemote->getName(),
            $remoteUrl,
        ));
    }

    /**
     * Build repo url with username and password
     *
     * https://github.com/user/Repo.git --> https://username:password@github.com/user/Repo.git
     *
     * @return string
     */
    protected function _buildUnsafeRemoteUrl()
    {
        $modexRemote = $this->getModexRemote();
        $parsedUrl = parse_url($modexRemote->getUrl());
        $host = $modexRemote->getUsername() . ':' . $this->_decrypt($modexRemote->getPassword()) . '@' . $parsedUrl['host'];

        // @TODO: see how to use something like http_build_url() without installing any lib
        $repoUrl = $parsedUrl['scheme'] . '://' . $host . $parsedUrl['path'];

        return $repoUrl;
    }

    /**
     * Replace password with *****
     *
     * @param string $str
     * @return string
     */
    protected function _obfuscatePassword($str)
    {
        return str_replace(
            $this->_decrypt($this->getModexRemote()->getPassword()),
            '*****',
            $str
        );
    }

    /**
     * Decrypt string
     *
     * @param $str string
     * @return string
     */
    protected function _decrypt($str)
    {
        if(is_null($this->_encryptor)) {
            $this->_encryptor = new \Mage_Core_Model_Encryption();
        }

        return $this->_encryptor->decrypt($str);
    }
}

