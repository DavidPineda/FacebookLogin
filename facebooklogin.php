<?php
class FacebookLogin extends Module
{
    public function __construct() {
        $this->name = 'facebooklogin';
        $this->tab = 'front_office_feautures';
        $this->version = '0.1';
        $this->author = 'David Pineda';
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Facebook Login');
        $this->description = $this->l('Connect with your facebook account');
    }
    
    public function getContent(){
        $this->loadConfiguration();
        $this->saveConfiguration();
        return $this->display(__FILE__, 'facebookloginconfiguration.tpl');
    }
    
    public function install(){
        if (!parent::install() || !$this->registerHook('header') || !$this->registerHook('facebookRegisterHook')
                || !$this->registerHook('facebookLoginHook')) {
            return false;
        }
        $sql_file = dirname(__FILE__).'/install/install.sql';
        if(!$this->loadSqlFile($sql_file)){
            return false;
        }        
        return true;
    }
    
    public function uninstall(){
        if(!parent::uninstall() || !$this->unregisterHook('header') || !$this->registerHook('facebookRegisterHook')
                || !$this->registerHook('facebookLoginHook')){
            return false;
        }
        //Delete the overwrite controller
        unlink(_PS_OVERRIDE_DIR_.'controllers/front/AuthController.php');
        return true;        
    }
    
    public function hookHeader(){
        $appId = Configuration::get('FACE_CONNECT_APP_ID');
        $this->context->cookie->__set('appId',$appId);   
        $this->loadFiles();        
    }
    
    public function hookFacebookRegisterHook(){
        return $this->display(__FILE__, 'facebookRegisterbutton.tpl');
    }
    
    public function hookFacebookLoginHook(){
        return $this->display(__FILE__, 'facebookloginbutton.tpl');        
    }
    
    /**
     * Save Information of Configuration Facebook Login
     */
    private function saveConfiguration(){
        if(Tools::isSubmit('btnConfigAppFacebookConnect')){
            $appId = Tools::getValue('FbAppId');
            Configuration::updateValue('FACE_CONNECT_APP_ID', $appId);
            $this->smarty->assign('SaveOk', 'OK');
        }
    }
    
    /**
     * Load Information of Configuration Facebook Login
     */
    private function loadConfiguration(){
        $appId = Configuration::get('FACE_CONNECT_APP_ID');
        $this->smarty->assign('appId', $appId);
    }
    
    /*
     * Load CSS y JS for the Module
     */
    private function loadFiles(){
        $this->path = __PS_BASE_URI__.'modules/facebooklogin/';
        $this->context->controller->addCSS($this->path.'views/css/facebooklogin.css', 'all');
        $this->context->controller->addJS($this->path.'views/js/facebooklogin.js', 'all');        
    }
    
    /*
     * Run Sql statement in your data base
     */
    private function loadSqlFile($sql_file){
        // Get file content
        $sql_content = file_get_contents($sql_file);
        // Replace prefix with yor store prefix  
        $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
        // Save each statement in array
        $sql_request = preg_split("/;\*[\r\n]+/", $sql_content);
        // Exec statements
        $result = true;
        foreach($sql_request as $request){
            if(!empty($request))
                $result &= Db::getInstance()->execute(trim($request));
            return $result;            
        }
    }    
    
}
