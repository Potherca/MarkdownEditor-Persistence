<?php
namespace Potherca
{
    session_start();

    define('PROJECT_ROOT', realpath(__DIR__ . '/../'));
    define('WEB_ROOT', '/Playground/MarkdownEditor/MarkdownEditor-Persistence/www/');

    // Load our own classes
    spl_autoload_register(function ($p_sClass) {
        $bFound = false;
        if(strpos($p_sClass, __NAMESPACE__) === 0) {
            // @NOTE: The +1 is for the leading slash
            $sClass = substr($p_sClass, strlen(__NAMESPACE__) +1 );
            $sClass = str_replace('\\', DIRECTORY_SEPARATOR, $sClass);

            $sFilePath = PROJECT_ROOT . '/lib/class.' . $sClass . '.php';
            if (file_exists($sFilePath)) {
                $bFound = include $sFilePath;
            }#if
        }#if

        return $bFound;
    });

    // @TODO: Load Markdown_Parser class through autoloader
    require realpath(PROJECT_ROOT. '/vendor/php-markdown/markdown.php');

    $sFilesDirectory = realpath(PROJECT_ROOT. '/../Files');
    $sFrontendDirectory = realpath(PROJECT_ROOT. '/../MarkdownEditor-FrontEnd');

    $oRepository = new Repository($sFilesDirectory);

    //@TODO: $oRequest = new Request($_SERVER); instead of code below
    $aRequest = Utilities::fetchRewriteSource($_SERVER, WEB_ROOT);

    $oEditor = new MarkdownEditor($oRepository);

    $oEditor->setAuthenticationFile(realpath(PROJECT_ROOT . '/../credentials.ini'));
    $oEditor->setFrontendDirectory($sFrontendDirectory);
    $oEditor->setWebroot(WEB_ROOT);

    $oPage = $oEditor->populateForRequest($aRequest);

    echo $oPage;
}

#EOF