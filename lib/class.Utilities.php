<?php
namespace Potherca
{
    class Utilities
    {
//////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
////////////////////////////// SETTERS AND GETTERS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
////////////////////////////////// PUBLIC API \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
        static public function fetchRewriteSource($p_aRequest, $p_sWebRoot)
        {
            $aRequest = array();

            if(substr($p_sWebRoot, -1) === '/') {
                $sWebRoot = substr($p_sWebRoot, 0, -1);
            }
            else {
                $sWebRoot = $p_sWebRoot;
            }

            foreach ($p_aRequest as $t_sKey => $t_sValue) {
                if (substr($t_sKey, 0, 9) === 'REDIRECT_') {
                    $p_aRequest[substr($t_sKey, 9)] = $t_sValue;
                }
            }

            foreach ($p_aRequest as $t_sKey => $t_sValue) {
                if (substr($t_sKey, 0, 8) === 'REWRITE_') {
                    $aRequest[substr($t_sKey, 8)] = $t_sValue;
                    unset($p_aRequest[$t_sKey]);
                }
            }

            $aRequest['URI'] = substr($aRequest['URI'],strlen($sWebRoot));

            if(substr($aRequest['URI'], 0, 1) === '/') {
                $aRequest['URI'] = substr($aRequest['URI'], 1);
            }

            if($aRequest['URI'] === false) {
                $aRequest['SOURCE'] = 'FRONT_PAGE';
                $aRequest['ACTION'] = 'view';
                $aRequest['REQUEST'] = '';
            }
            else {

                $aRequestParts = explode('/', $aRequest['URI']);

                $sAction = array_shift($aRequestParts);

                $aRequest['ACTION'] = $sAction;
                $aRequest['REQUEST'] = implode('/', $aRequestParts);
            }

            return $aRequest;
        }
//////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    }
}