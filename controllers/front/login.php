<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of login
 *
 * @author david
 */
class FacebookLoginLoginModuleFrontController extends ModuleFrontController {
    
    public function init()
    {
        parent::init();     
    }

    public function initContent(){
        parent::initContent();
        //$this->setTemplate('facebookloginbutton.tpl');
    }
    
    public function postProcess(){
        if (Tools::isSubmit('submitCreateFacebook')){
            return $this->processFacebookRegister();
        }
        if (Tools::isSubmit('submitLoginFacebook')){
            return $this->processFacebookLogin();
        }
    }    
    
    /**
     * Init process to create register with facebook
     */
    private function processFacebookRegister(){
        $customer = new Customer();
        $_POST['lastname'] = $customer->lastname = Tools::getValue('lastName');
        $_POST['firstname'] = $customer->firstname = Tools::getValue('firstName');
        $password = rand(100000, 1000000);
        $customer->email = Tools::getValue('email');
        $customer->passwd = Tools::encrypt($password);
        $customer->is_guest = (Tools::isSubmit('is_new_customer') ? !Tools::getValue('is_new_customer', 1) : 0);
        $customer->active = 1;
        $idFacebook = Tools::getValue('idFacebook');
        if($this->getEmail($idFacebook) == ''){
            $this->saveUser($customer, $idFacebook, $password);
        }else{
            $this->processFacebookLogin();
        }        
    }
    
    /*
     * Init procces to login with facebook account once user register in system
     */
    private function processFacebookLogin(){
        $idFacebook = Tools::getValue('idFacebook');
        $c = new Customer();
        $customer = $c->getByEmail($this->getEmail($idFacebook));
        if (!$customer->id) {
            $this->errors[] = Tools::displayError('Authentication failed.');
        } else {
            $this->updateGeneralContext($customer);
            $this->context->cookie->id_compare = isset($this->context->cookie->id_compare) ? $this->context->cookie->id_compare: CompareProduct::getIdCompareByIdCustomer($customer->id);
            $this->context->cookie->is_guest = $customer->isGuest();

            if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0) && $id_cart = (int)Cart::lastNoneOrderedCart($this->context->customer->id)) {
                $this->context->cart = new Cart($id_cart);
            } else {
                $id_carrier = (int)$this->context->cart->id_carrier;
                $this->context->cart->id_carrier = 0;
                $this->context->cart->setDeliveryOption(null);
                $this->context->cart->id_address_delivery = (int)Address::getFirstCustomerAddressId((int)($customer->id));
                $this->context->cart->id_address_invoice = (int)Address::getFirstCustomerAddressId((int)($customer->id));
            }
            $this->context->cart->id_customer = (int)$customer->id;
            $this->context->cart->secure_key = $customer->secure_key;

            if ($this->ajax && isset($id_carrier) && $id_carrier && Configuration::get('PS_ORDER_PROCESS_TYPE')) {
                $delivery_option = array($this->context->cart->id_address_delivery => $id_carrier.',');
                $this->context->cart->setDeliveryOption($delivery_option);
            }

            $this->context->cart->save();
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
            $this->context->cookie->write();
            $this->context->cart->autosetProductAddress();

            Hook::exec('actionAuthentication', array('customer' => $this->context->customer));

            // Login information have changed, so we check if the cart rules still apply
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
            if ($this->ajax) {
                $return = array(
                    'id_customer' => (int)$this->context->cookie->id_customer,
                    'token' => Tools::getToken(false)
                );
                $this->ajaxDie(Tools::jsonEncode($return));
            }
        }
    }
    
    /**
     * Get user email 
     * @param type $idFacebook facebook user Id
     * @return type Email for user
     */
    private function getEmail($idFacebook){
        $email = Db::getInstance()->executeS('select email from '._DB_PREFIX_.'login_facebook'
                .' as a inner join '._DB_PREFIX_.'customer as b on a.id_customer = b.id_customer'
                .' where id_facebook = '.$idFacebook);
        return $email[0]['email'];
    }
    
    /**
     * 
     * @param type $customer
     * @param type $idFacebook
     */
    private function saveUser(Customer $customer, $idFacebook, $password){
        if ($customer->add()) {
            if (!$customer->is_guest) {
                if (!$this->sendConfirmationMail($customer, $password)) {
                    $this->errors[] = Tools::displayError('The email cannot be sent.');
                }
            }
            $this->updateContext($customer);
            $this->context->cart->update();
            $dateFaceArray = array('id_customer' => (int)$this->context->cookie->id_customer, 
                'id_facebook' => $idFacebook);
            Db::getInstance()->insert('login_facebook', $dateFaceArray);
            Hook::exec('actionCustomerAccountAdd', array('_POST' => $_POST, 'newCustomer' => $customer));
            if ($this->ajax) {
                $return = array(
                    'id_customer' => (int)$this->context->cookie->id_customer,
                    'token' => Tools::getToken(false)
                );
                $this->ajaxDie(Tools::jsonEncode($return));
            }
            // redirection: if cart is not empty : redirection to the cart
            if (count($this->context->cart->getProducts(true)) > 0) {
                $multi = (int)Tools::getValue('multi-shipping');
                Tools::redirect('index.php?controller=order'.($multi ? '&multi-shipping='.$multi : ''));
            }
            // else : redirection to the account
            else {
                Tools::redirect('index.php?controller='.(($this->authRedirection !== false) ? urlencode($this->authRedirection) : 'my-account'));
            }
        }        
    }
    
    /**
     * Update context after customer creation
     * @param Customer $customer Created customer
     */
    protected function updateContext(Customer $customer)
    {
        $this->updateGeneralContext($customer);
        $this->context->smarty->assign('confirmation', 1);        
        $this->context->cookie->is_guest = !Tools::getValue('is_new_customer', 1);
        // Update cart address
        $this->context->cart->secure_key = $customer->secure_key;
    }

    private function updateGeneralContext(Customer $customer){
        $customer->logged = 1;
        $this->context->customer = $customer;
        $this->context->cookie->id_customer = (int)$customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->logged = 1;
        $this->context->cookie->email = $customer->email;        
    }
    
    /**
     * sendConfirmationMail
     * @param Customer $customer
     * @return bool
     */
    protected function sendConfirmationMail(Customer $customer, $password)
    {
        if (!Configuration::get('PS_CUSTOMER_CREATION_EMAIL')) {
            return true;
        }
        
        try{
            return Mail::Send(
                $this->context->language->id,
                'account',
                Mail::l('Welcome!'),
                array(
                    '{firstname}' => $customer->firstname,
                    '{lastname}' => $customer->lastname,
                    '{email}' => $customer->email,
                    '{passwd}' => $password),
                $customer->email,
                $customer->firstname.' '.$customer->lastname
            );            
        } catch (Exception $ex) {
            $x = $ex;
        }
    }    
}
