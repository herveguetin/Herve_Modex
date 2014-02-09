# What is Modex

Modex stands for "Module Export".

It is integrated into Magento "Create Extension Package" feature in administration console and allows you to:

* export your Magento extension’s files to a separate directory on your server
* add a README.md file
* create a modman file
* create your extension’s XML package definition file in a var/connect/ directory
* link all the above to local and remote Git repositories

### Requirements

* be able to create Magento extensions from the administration console
* have basic knowledge with Git

# Installation

## With modman

Into your Magento installation directory:

`modman clone https://github.com/herveguetin/Herve_Modex.git`

(running modman init may be required)

## Other

Just download the archive and install as usual.

# Quick Start

1. Create a remote Git repository on your favorite server / Github / Bitbucket
* Create an empty directory on your server with write access to the webserver user
* Go to System > Magento Connect > Package Extension
* Create your extension as usual
* Go to the new "Export (Modex)" tab and configure as explained in the next part

> Each time you save your extension, both locally exported extension and its remote repositories are synchronized. As soon there are changes in your extensions files, Modex tracks them. Modex tracks everything...

# Modex configuration

## "Information" fields

Once "Module Export" is enabled…

* __Full path__: full server path to where you want your extension to be exported. This is the path to the directory created at step 2 of the above Quick Start. A new directory is created with your extension’s name and all its files go in there.
* __Create .tgz archive__: if you want to add an archive of your extension. It is the very same archive than the one created for Magento Connect 2 publishing.
* __Create package XML file__: if you want to add the Magento Connect XML package definition file to your export. A directory structure to var/connect/*Your_Module*.xml is automatically created.
* __Create README.md file__: this creates a README.md file with the content of your extension's description.
* __Create modman file__: this creates a modman file based on your extension's files and directories structure.

# "Version Control System" fields

Once "Module Export" and "Use Git VCS" are enabled…

* __Full path to Git bin__: where is your Git binary on your server? (`which git` may help)

* __Git commit message__:
> __CAUTION__ This is the commit message that will be used for the next commit. So, just saving your extension from Magento admin may lead to a commit if your extension files have changed. Once again, Modex tracks everything. If Git commit message is left empty, extension’s release notes are used.

## Using remote Git repository

Once you have set "Use Remote Repository" to "Yes"…

* __Git Remote Name__: the name of the remote repository.
* __Git Remote Branch__: to which remote branch should your extension's be pushed.
* __Git Remote Url__: the Url of the remote repository created at step 1 of Quick Start.
* __Git Remote Username__ and __Git Remote Password__: your credentials to connect to your account on the remote repository.

### Manually pushing to remote repository

It may happen that, for any reason, Git operations fail (pull needed prior to pushing, wrong account credentials, ...). And then, pushing to the remote repository may also fail.

The "Force Push to Remote Repository" button allows you to push to the remote repository once errors are fixed.

You may also want to push your extension to another branch. You then just need to update the "Git Remote Branch" field, save the extension and click "Force Push to Remote Repository".