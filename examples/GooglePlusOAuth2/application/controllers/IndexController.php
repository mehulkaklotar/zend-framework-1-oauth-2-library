<?php

/**
 * IndexController
 * 
 * Manage your google APIs: https://code.google.com/apis/console/
 * 
 */
class IndexController extends Zend_Controller_Action
{
 
    public function init()
    {
    
        //Zend_Debug::dump('Hello World');
        
    }

    /**
     * google+ oauth 2 workflow start
     * 
     * https://developers.google.com/+/api/oauth
     * https://developers.google.com/accounts/docs/OAuth2
     * https://developers.google.com/oauthplayground/
     * 
     */
    public function indexAction()
    {
    
        //Zend_Debug::dump('IndexController indexAction');
        
        // retrieve the google+ api configuration
        $googlePlusOAuth2Configuration = new Zend_Config_Ini(APPLICATION_PATH.'/configs/google_plus_api.ini');
        
        // create a secret state, insert it in the options and put it into the
        // session to validate it during the next step
        $state = $googlePlusOAuth2Configuration->stateSecret.md5(uniqid(rand(), TRUE));
        
        $oauthSessionNamespace = new Zend_Session_Namespace('oauthSessionNamespace');
        $oauthSessionNamespace->state = $state;
        
        $googlePlusOAuth2ConfigurationArray = $googlePlusOAuth2Configuration->toArray();
        
        $googlePlusOAuth2ConfigurationArray['state'] = $state;
        
        // start the google+ oauth 2 workflow
        $chriswebOauth2 = new Chrisweb_Oauth2($googlePlusOAuth2ConfigurationArray);
        
        $chriswebOauth2->authorizationRedirect();
        
    }
    
    /**
     * google+ oauth 2 redirect_uri call
     * 
     * If you get an error like: Unable to Connect to ssl://www.google.com:443
     * ensure open_ssl extension is enabled in your php.ini, then restart apache
     */
    public function googlepluscallbackAction()
    {

        $rawCode = $this->_request->getParam('code', null);
        $stateParameter = $this->_request->getParam('state', null);
        $errorReason = $this->_request->getParam('error_reason', null);
        
        $oauthSessionNamespace = new Zend_Session_Namespace('oauthSessionNamespace');
        
        //Zend_Debug::dump($stateParameter, '$stateParameter');
        //Zend_Debug::dump($oauthSessionNamespace->state, '$oauthSessionNamespace->state');

        if (is_null($stateParameter)) {
            
            // user refused to grant permission(s)
            Zend_Debug::dump('dialog no valid state found');
            exit;
            
        } else if ($stateParameter !== $oauthSessionNamespace->state) {
            
            Zend_Debug::dump('dialog state values don\'t match');
            exit;
            
        }
        
        if (!is_null($errorReason)) {
            
            // user refused to grant permission(s)
            Zend_Debug::dump('user refused to grant permission(s): '.$errorReason);
            exit;
            
        }

        $filterChain = $this->getFilterChain();

        $verificationCode = $filterChain->filter($rawCode);
        
        $googlePlusOAuth2Configuration = new Zend_Config_Ini(APPLICATION_PATH.'/configs/google_plus_api.ini');

        $chriswebOauth2 = new Chrisweb_Oauth2($googlePlusOAuth2Configuration);
        
        $oauthResponse = null;
        
        try {

            /**
             * if you try to exchange an expired or invalid token, google will
             * reply "invalid_grant"
             */
            $oauthResponse = $chriswebOauth2->requestAccessToken($verificationCode);

        } catch (Exception $e) {

            Zend_Debug::dump($e->getMessage(), 'error');

        }
        
        if (is_array($oauthResponse)) {
            
            Zend_Debug::dump($oauthResponse, '$oauthResponse: ');
            
            // save OAuth Response
            $oauthSessionNamespace->oauthResponse = $oauthResponse;

        }
        
    }
    
    /**
     * 
     * @return \Zend_Filter
     */
    protected function getFilterChain() {
        
        $filterStripTags = new Zend_Filter_StripTags();
        $filterHtmlEntities = new Zend_Filter_HtmlEntities();
        $filterStripNewLines = new Zend_Filter_StripNewLines();
        $filterStringTrim = new Zend_Filter_StringTrim();
        
        $filterChain = new Zend_Filter();
        
        $filterChain->addFilter($filterStripTags)
                    ->addFilter($filterHtmlEntities)
                    ->addFilter($filterStripNewLines)
                    ->addFilter($filterStringTrim);
        
        return $filterChain;
        
    }
    
}