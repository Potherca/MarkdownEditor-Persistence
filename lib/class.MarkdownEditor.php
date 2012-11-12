<?php
namespace Potherca
{
    class MarkdownEditor
    {
//////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        /**
         * @var string
         */
        protected $m_sAuthenticationFile;
        /**
         * @var string
         */
        protected $m_sFrontendDirectory;
        /**
         * @var string
         */
        protected $m_sWebRoot;
        /**
         * @var Template
         */
        protected $m_oTemplate;
        /**
         * @var Repository
         */
        protected $m_oRepository;

////////////////////////////// SETTERS AND GETTERS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        public function setAuthenticationFile($p_sAuthenticationFile)
        {
            $this->m_sAuthenticationFile = $p_sAuthenticationFile;
        }

        public function getAuthenticationFile()
        {
            return $this->m_sAuthenticationFile;
        }

        public function setFrontendDirectory($p_sFrontendDirectory)
        {
            $this->m_sFrontendDirectory = $p_sFrontendDirectory;
        }

        public function getFrontendDirectory()
        {
            return $this->m_sFrontendDirectory;
        }

        /**
         * @param string $p_sWebRoot
         */
        public function setWebRoot($p_sWebRoot)
        {
            $this->m_sWebRoot = (string) $p_sWebRoot;
        }

        /**
         * @return string
         */
        public function getWebRoot()
        {
            return $this->m_sWebRoot;
        }

        /**
         * @param \Potherca\Repository $p_oRepository
         */
        public function setRepository(\Potherca\Repository $p_oRepository)
        {
            $this->m_oRepository = $p_oRepository;
        }

        /**
         * @return \Potherca\Repository
         */
        public function getRepository()
        {
            return $this->m_oRepository;
        }

////////////////////////////////// PUBLIC API \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        function __construct(Repository $p_oRepository)
        {
            $this->setRepository($p_oRepository);
        }

        public function populateForRequest(Array $p_aRequest)
        {
            $sHtml = '';
            $sMarkdown = '';
            $sErrorMessage = null;

            $sAction = $p_aRequest['ACTION'];
            $sRequest = $p_aRequest['REQUEST'];
            $oRepository = $this->getRepository();

            $sTemplatePath = $this->getFrontendDirectory() . '/index.html';
            $oPage = new Template($sTemplatePath);
            $oPage->setWebRoot($this->getWebRoot());

            switch($sAction) {
                case 'view':
                    $sMarkdown = $oRepository->view($sRequest);
                    if (isset($_POST['markdown']) === true) {
                        $sUser = $this->fetchUserString();

                        $oRepository->setCurrentUser($sUser);
                        $sMarkdown = $_POST['markdown'];
                        $bSuccess = $oRepository->save($sRequest, $sMarkdown);
                        if ($bSuccess === true) {
                            $this->redirectTo($sRequest);
                        }
                        else {
                            $sErrorMessage = 'Could not save changes to file.';
                        }
                    }
                break;

                case 'login':
                    $sMarkdown = $oRepository->view($sRequest);

                    if(isset($_POST['username'])) {
                        if(isset($_POST['password']) === false) {
                            $sErrorMessage = 'Password can not be empty';
                        }
                        else {
                            $bLogin = $this->validateCredentials($_POST['username'], $_POST['password']);
                            if($bLogin === false) {
                                $sErrorMessage = 'Given credentials are not correct';
                            }
                            else {
                                $_SESSION['username'] = $_POST['username'];
                                $this->redirectTo($sRequest);
                            }#if
                        }#if
                    }#if

                    $bShowLoginForm = true;
                break;

                case 'blame':
                case 'create':
                case 'move':
                    throw new \Exception('Action "' . $sAction . '" has not been implemented yet.');
                break;


                default:
                    throw new \UnexpectedValueException('Action "' . $sAction . '" is not implemented.');
                break;
            }


            $bShowEditor = $this->editorVisible();

            if ($bShowEditor === false) {
                $oParser = new \Markdown_Parser();
                $sHtml =  $oParser->transform($sMarkdown);
                $oPage->clearEditor();
            }
            else {
                $oPage->fixEditorForm($sRequest, $sMarkdown);
            }#if

            if (isset($bShowLoginForm)) {
                $oPage->addLoginForm($sRequest, $sErrorMessage);
            }else if ($this->userIsLoggedIn() === false) {
                $oPage->addLoginButton($sRequest);
            }#if

            if(!empty($sErrorMessage)) {
                $oPage->addErrorMessage($sErrorMessage);
                // Make sure the user does not lose his edits
                $sHtml = '<textarea style="height: 20em;">' . $sMarkdown . '</textarea>';
            }#if

            if(!empty($sHtml)) {
                $oPage->addHtmlToPreview($sHtml);
            }#if

            return $oPage;

        }

//////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        protected function editorVisible()
        {
            $bShowEditor = false;

            if ($this->userIsLoggedIn() === true) {
                $bShowEditor = $this->getRepository()->isSubjectResourceEditable();
            }#if

            return $bShowEditor;
        }

        protected function userIsLoggedIn()
        {
            return isset($_SESSION['username']);
        }

        protected function redirectTo($p_sRequest)
        {
            $sUrl = $this->getWebRoot() . 'view/' . $p_sRequest;
            header('Location: ' . $sUrl , true, 303);
        }

        protected function validateCredentials($p_sUsername, $p_sPassword)
        {
            $aCredentials = $this->fetchCredentials();

            $bValid = isset($aCredentials[$p_sUsername]) &&
                $aCredentials[$p_sUsername]['pass'] === sha1($p_sPassword)
            ;

            return $bValid;
        }

        protected function fetchCredentials()
        {
            static $aCredentials;

            if(!isset($aCredentials)) {
                $sAuthenticationFile = $this->getAuthenticationFile();
                $aCredentials = parse_ini_file($sAuthenticationFile, true, INI_SCANNER_RAW);
            }

            return $aCredentials;
        }

        protected function fetchUserString()
        {
            $aCredentials = $this->fetchCredentials();

            $sCurrentUser = $_SESSION['username'];

            $aCurrentCredentials = $aCredentials[$sCurrentUser];

            return $sCurrentUser . ' <' . $aCurrentCredentials['email'] . '>';
        }

    }
}