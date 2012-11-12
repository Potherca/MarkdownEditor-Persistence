<?php
namespace Potherca
{
    class Repository
    {
//////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        protected $m_sRootDirectory;
        protected $m_bIsSubjectResourceEditable = false;

        /**
         * @var string
         */
        protected $m_sCurrentUser;

////////////////////////////// SETTERS AND GETTERS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        public function setRootDirectory($p_sRootDirectory)
        {
            $this->m_sRootDirectory = $p_sRootDirectory;
        }

        public function getRootDirectory()
        {
            return $this->m_sRootDirectory;
        }

        public function isSubjectResourceEditable()
        {
            return $this->m_bIsSubjectResourceEditable;
        }

        /**
         * @param string $p_sCurrentUser
         */
        public function setCurrentUser($p_sCurrentUser)
        {
            $this->m_sCurrentUser = (string) $p_sCurrentUser;
        }

        /**
         * @return string
         */
        public function getCurrentUser()
        {
            return $this->m_sCurrentUser;
        }

////////////////////////////////// PUBLIC API \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        function __construct($p_sRootPath)
        {
            if(is_dir($p_sRootPath) === false)
            {
                throw new \UnexpectedValueException('Given path "' . $p_sRootPath . '" is not a directory.');
            }
            else
            {
                $this->setRootDirectory($p_sRootPath);
            }
        }

        public function blame($p_sSubject)
        {
        }

        protected function itemIsAccessibleToRead(\DirectoryIterator $t_oItem)
        {
            $bAccessible = false;
            if($t_oItem->isDot() === false)
            {
                if($t_oItem->isDir())
                {
                    // Skip "hidden" folders
                    $bAccessible = substr($t_oItem->getFilename(), 0, 1) !== '.';
                }
                elseif($t_oItem->isFile()) {
                    $sExtension = pathinfo($t_oItem->getFilename(), PATHINFO_EXTENSION);
                    $bAccessible = ($sExtension === 'md');
                }
            }
            return $bAccessible;
        }

        public function view($p_sSubjectPath)
        {
            //@TODO: Sanitize $p_sSubjectPath so it cannot go out of the RootDirectory
            $sContents = '';

            $sPath = $this->getRootDirectory() . DIRECTORY_SEPARATOR . $p_sSubjectPath;

            $oFileInfo = new \SplFileInfo($sPath);
            if($oFileInfo->isDir())
            {
                $aList = $this->buildList($oFileInfo);
                foreach($aList as $t_sItemPath => $t_sItemName)
                {
                    $sContents .= ' - [' . $t_sItemName . '](' . $t_sItemPath . ')' . "\n";
                }#foreach
            }
            else if (file_exists($sPath) === false) {
                $sContents .= 'Could not find resource for ' . $p_sSubjectPath ."\n\n";
                $sContents .= 'Create page for [' . $p_sSubjectPath . ']('. $p_sSubjectPath . ')';
            }
            else {
                $sContents .= $this->getFileContents($oFileInfo);
            }#if

            return $sContents;
        }

        public function save($p_sSubjectPath, $p_sMarkdown)
        {
            $bSuccess = false;

            $sPath = $this->getRootDirectory() . DIRECTORY_SEPARATOR . $p_sSubjectPath;
            if (is_writable($sPath)) {
                $iWritten = file_put_contents($sPath, $p_sMarkdown, LOCK_EX);

                $bSuccess = $iWritten !== false;
                if($bSuccess === true)
                {
                    $sMessage = 'Updates ' . $p_sSubjectPath;

                    $sSubCommand =
                          ' --git-dir=\'' . $this->getRootDirectory() . '/.git\''
                        . ' --work-tree=\'' . $this->getRootDirectory() . '\' '
                    ;

                    $sCommand =
                          'git ' . $sSubCommand . ' add --force \'' . $p_sSubjectPath . '\''
                        . ' 2>&1 && '
                        . 'git ' . $sSubCommand . ' commit'
                        . ' --message=\'' . $sMessage . '\''
                        . ' --author=\'' . $this->getCurrentUser() .'\''
                        . ' -- \'' . $p_sSubjectPath . '\''
                        . ' 2>&1'
                    ;

                    $aOutput = array();
                    exec($sCommand, $aOutput, $iErrorCode);

                    if($iErrorCode > 0)
                    {
                        throw new \Exception('Could not commit saved changes. ' . implode("\n", $aOutput));
                    }
                }
            }

            return $bSuccess;
        }

        public function recentChanges()
        {
            //@TODO: To show what's going on in the repo we could use the `git whatchanged` command
        }
//////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        protected function buildList(\SplFileInfo $p_oFileInfo)
        {
            $aList = array();

            $iLength = strlen($this->getRootDirectory());
            $oDirectories = new \DirectoryIterator($p_oFileInfo->getPathname());
            foreach($oDirectories as $t_oItem)
            {
                /** @var \DirectoryIterator $t_oItem */
                if($this->itemIsAccessibleToRead($t_oItem)) {

                    $sItemPath = substr($t_oItem->getPathname(), $iLength + 1);

                    if($t_oItem->isDir())
                    {
                        $sItemName = $sItemPath . '/';
                    }
                    else //if($t_oItem->isFile())
                    {
                        $sItemName = substr($sItemPath, 0, -3);
                    }

                    $aList[$sItemPath] = $sItemName;
                }#if
            }#foreach

            return $aList;
        }

        protected function getFileContents(\SplFileInfo $oFileInfo)
        {
            $sPath = $oFileInfo->getPathname();
            if(is_writable($sPath))
            {
                $this->m_bIsSubjectResourceEditable = true;
            }

            return file_get_contents($sPath);
        }
    }
}