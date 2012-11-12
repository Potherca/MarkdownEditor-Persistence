<?php
namespace Potherca
{
    class Template extends \DOMDocument
    {
//////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        /**
         * @var string
         */
        protected $m_sWebRoot;
////////////////////////////// SETTERS AND GETTERS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
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

////////////////////////////////// PUBLIC API \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        function __construct($p_sTemplatePath)
        {
            parent::__construct();
            $this->loadHTMLFile($p_sTemplatePath);
        }

        public function __toString()
        {
            $this->fixTemplate();
            return $this->saveHTML();
        }

        /**
         * @param \DOMElement $p_oDomNode
         * @return \DOMNodeList
         */
        public function removeChildrenFromNode(\DOMElement $p_oDomNode)
        {
            //@TODO: Add removed children to a DOMNodeList and return that.
            if ($p_oDomNode->hasChildNodes()) {
                $oChildNodes = $p_oDomNode->childNodes;

                while ($oChildNodes->length > 0) {
                    $p_oDomNode->removeChild($oChildNodes->item(0));
                }#while
            }#if
        }

        public function clearEditor()
        {
            $oEditorElement = $this->getElementById('editor');
            $this->removeChildrenFromNode($oEditorElement);
        }

        public function addLoginButton($p_sRequest)
        {
            $oEditorElement = $this->getElementById('editor');
            $oLink = $this->createElement('a');
            $oLink->setAttribute('href', $this->getWebRoot() . 'login/' . $p_sRequest);
            $oLink->appendChild($this->createTextNode('Please Login to Edit'));
            $oEditorElement->appendChild($oLink);
        }

        public function addLogoutButton($p_sRequest)
        {
            $oFooter = $this->getElementById('footer');
            $oLink = $this->createElement('a');
            $oLink->setAttribute('href', $this->getWebRoot() . 'logout/' . $p_sRequest);
            $oLink->setAttribute('class', 'toolbutton logout-button');
            $oLink->appendChild($this->createTextNode('logout'));
            $oFooter->appendChild($oLink);

        }

        public function fixEditorForm($p_sRequest, $p_sMarkdown)
        {
            $sUrl = $this->getWebRoot() . 'view/' . $p_sRequest;

            $oEditorElement = $this->getElementById('editor');
            $oParentNode = $oEditorElement->parentNode;
            $oParentNode->removeChild($oEditorElement);

            $oForm = $this->createElement('form');
            $oForm->setAttribute('action', $sUrl);
            $oForm->setAttribute('method', 'post');
            $oForm->appendChild($oEditorElement);

            $oParentNode->appendChild($oForm);

            $oInput = $this->getElementById('input');
            $oInput->setAttribute('name', 'markdown');
            $oInput->appendChild($this->createTextNode($p_sMarkdown));
        }

        public function addLoginForm($p_sRequest, $p_sErrorMessage=null)
        {
            $sUrl = $this->getWebRoot() . 'login/' . $p_sRequest;
            $sError = '';
            if(empty($p_sErrorMessage) === false)
            {
                $sMessage = '<p style="color: red;">' . $p_sErrorMessage . '</p>';
            }
            else {
                $sMessage = '<h2>Please log in :</h2>';
            }
            $sUsername = isset($_POST['username'])?$_POST['username']:'';
            $sHtml = '<form action="' . $sUrl . '" method="post">'
                    . $sMessage
                    .'
                    <p>
                        <label>Username:</label>
                        <input type="text" name="username" value="' . $sUsername . '"/>
                    </p>
                    <p>
                        <label>Password : </label>
                        <input type="password" name="password" />
                    </p>
                    <button type="submit">Login</button>
                </form>'
            ;
            $oEditorElement = $this->getElementById('editor');
            $oFragment = $this->createDocumentFragment();
            $oFragment->appendXML($sHtml);
            $oEditorElement->appendChild($oFragment);
        }

        public function addHtmlToPreview($p_sHtml)
        {
            $oPreview = $this->getElementById('preview');
            $oFragment = $this->createDocumentFragment();
            $oFragment->appendXML($p_sHtml);
            $oPreview->appendChild($oFragment);
        }

        public function addErrorMessage($sErrorMessage)
        {
            $oPreview = $this->getElementById('preview');
            $oFragment = $this->createDocumentFragment();
            $oFragment->appendXML('<p style="color:red;">'.$sErrorMessage.'</p>');
            $oPreview->insertBefore($oFragment,$oPreview->firstChild);
        }
//////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        protected function fixTemplate()
        {
            $this->fixResourceLink('script', 'src');
            $this->fixResourceLink('link', 'href');
            $this->fixResourceLink('a', 'href', array('PREPEND' => 'view/'));
            // Wrap #editor in a <form> tag
            $this->fixToolBar();
        }

        protected function fixResourceLink($p_sTagName, $p_sAttributeName, Array $p_aMutations=array())
        {
            $oNodeList = $this->getElementsByTagName($p_sTagName);
            foreach ($oNodeList as $t_oNode) {
                /**
                 * @var \DOMElement $t_oNode
                 */
                if ($t_oNode->hasAttribute($p_sAttributeName)) {
                    $sSourceAttribute = $t_oNode->getAttribute($p_sAttributeName);
                    if (
                        strpos($sSourceAttribute, 'http') !== 0
                        && strpos($sSourceAttribute, '/') !== 0
                    ) {
                        if(isset($p_aMutations['PREPEND'])){
                            $sSourceAttribute = $this->getWebRoot() . $p_aMutations['PREPEND'] . $sSourceAttribute;
                        }
                        else {
                            $sSourceAttribute = $this->getWebRoot() . $sSourceAttribute;
                        }#if

                        $t_oNode->setAttribute($p_sAttributeName, $sSourceAttribute);
                    }#if

                }#if
            }#foreach
        }

        protected function fixToolBar()
        {
            $oElement = $this->getElementById('tools');
            if($oElement instanceof \DOMElement)
            {
                $this->removeChildrenFromNode($oElement);

                $oButton = $this->createElement('button');
                $oButton->setAttribute('type', 'submit');
                $oButton->setAttribute('class', 'toolbutton');
                $oButton->setAttribute('id', 'save');
                $oButton->appendChild($this->createTextNode('Save'));


                $oElement->appendChild($oButton);
            }#if
        }
    }
}