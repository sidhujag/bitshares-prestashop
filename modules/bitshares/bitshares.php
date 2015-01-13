<?php

/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2011-2014 Bitshares
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */


if (!defined('_PS_VERSION_'))
  exit;

function bplog($contents) {
  if(isset($contents)) {
    if(is_resource($contents))
      return error_log(serialize($contents));
    else
      return error_log(var_dump($contents, true));
  } else {
    return false;
  }
}

class Bitshares extends PaymentModule {
    private $_html       = '';
    private $_postErrors = array();
    private $key;

    public function __construct() {

      $this->name            = 'bitshares';
      $this->version         = '1.0';
      $this->author          = 'sidhujag';
      $this->className       = 'Bitshares';
      $this->currencies      = true;
      $this->currencies_mode = 'checkbox';
      $this->tab             = 'payments_gateways';
	  /**
	  * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
	  */
	  $this->bootstrap = true;	  	
	  $this->need_instance = 1;	
      if (_PS_VERSION_ > '1.5')
      $this->controllers = array('payment', 'validation');

      parent::__construct();

      $this->page = basename(__FILE__, '.php');
      $this->displayName      = $this->l('Bitshares');
      $this->description      = $this->l('Accepts Bitshares payments.');
      $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

      // Backward compatibility
      require(_PS_MODULE_DIR_ . 'bitshares/backward_compatibility/backward.php');

      $this->context->smarty->assign('base_dir',__PS_BASE_URI__);

    }

    public function install() {

      if(!function_exists('curl_version')) {
        $this->_errors[] = $this->l('Sorry, this module requires the cURL PHP extension but it is not enabled on your server.  Please ask your web hosting provider for assistance.');
        return false;
      }

      if (!parent::install() || !$this->registerHook('invoice') || !$this->registerHook('payment') || !$this->registerHook('paymentReturn')) {
        return false;
      }

      $db = Db::getInstance();

      $query = "CREATE TABLE `"._DB_PREFIX_."order_bitshares` (
                `id_payment` int(11) NOT NULL AUTO_INCREMENT,
                `id_currency` int(11) NOT NULL,
                `total` decimal(20,6) NOT NULL,
                `cart_id` int(11) NOT NULL,
                `invoice_id` varchar(255) NOT NULL,
                `status` int(11) NOT NULL,
                PRIMARY KEY (`id_payment`),
                UNIQUE KEY `cart_id` (`cart_id`)
                ) ENGINE="._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

      $db->Execute($query);
      $query = "INSERT IGNORE INTO `ps_configuration` (`name`, `value`, `date_add`, `date_upd`) VALUES ('PS_OS_BITSHARES', '13', NOW(), NOW());";
      $db->Execute($query);

      return true;
    }

    public function uninstall() {

      return parent::uninstall();
    }

    public function getContent() {
      $this->_html .= '<h2>'.$this->l('Bitshares').'</h2>';

      $this->_postProcess();
      $this->_setBitsharesSubscription();
      $this->_setConfigurationForm();

      return $this->_html;
    }

    public function hookPayment($params) {
     if (!$this->active)
		return;	
					
      global $smarty;
      $smarty->assign(array(
                            'this_path' => $this->_path,
                            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__."modules/{$this->name}/")
                           );

      return $this->display(__FILE__, 'payment.tpl');
    }

    private function _setBitsharesSubscription() {
      $this->_html .= '<div style="float: right; width: 440px; height: 150px; border: dashed 1px #666; padding: 8px; margin-left: 12px;">
                       <h2>'.$this->l('Opening your Bitshares account').'</h2>
                       <div style="clear: both;"></div>
                       <p>'.$this->l('If you do not have a Bitshares account click on the following image:').'</p>
                       <p style="text-align: center;"><a href="http://bitshares.org/"><img src="../modules/bitshares/prestashop_bitshares.png" alt="PrestaShop & Bitshares" style="margin-top: 12px;" /></a></p>
                       <div style="clear: right;"></div>
                       </div>
                       <img src="../modules/bitshares/bitshares.png" style="float:left; margin-right:15px;" />
                       <b>'.$this->l('This module allows you to accept payments by Bitshares.').'</b><br /><br />
                       '.$this->l('If the client chooses this payment mode, your Bitshares account will be automatically credited.').'<br />
                       '.$this->l('You need to configure your Bitshares account before using this module.').'
                       <div style="clear:both;">&nbsp;</div>';
    }

    private function _setConfigurationForm() {
      $this->_html .= '<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">
                       <script type="text/javascript">
                       var pos_select = '.(($tab = (int)Tools::getValue('tabs')) ? $tab : '0').';
                       </script>';

      if (_PS_VERSION_ <= '1.5') {
        $this->_html .= '<script type="text/javascript" src="'._PS_BASE_URL_._PS_JS_DIR_.'tabpane.js"></script>
                         <link type="text/css" rel="stylesheet" href="'._PS_BASE_URL_._PS_CSS_DIR_.'tabpane.css" />';
      } else {
        $this->_html .= '<script type="text/javascript" src="'._PS_BASE_URL_._PS_JS_DIR_.'jquery/plugins/tabpane/jquery.tabpane.js"></script>
                         <link type="text/css" rel="stylesheet" href="'._PS_BASE_URL_._PS_JS_DIR_.'jquery/plugins/tabpane/jquery.tabpane.css" />';
      }

      $this->_html .= '<input type="hidden" name="tabs" id="tabs" value="0" />
                       <div class="tab-pane" id="tab-pane-1" style="width:100%;">
                       <div class="tab-page" id="step1">
                       <h4 class="tab">'.$this->l('Settings').'</h2>
                       '.$this->_getSettingsTabHtml().'
                       </div>
                       </div>
                       <div class="clear"></div>
                       <script type="text/javascript">
                       function loadTab(id){}
                       setupAllTabs();
                       </script>
                       </form>';
    }

    private function _getSettingsTabHtml() {
      global $cookie;

      $html = '<h2>'.$this->l('Settings').'</h2>             
               <p class="center"><input class="button" type="submit" name="submitBitshares" value="'.$this->l('Save settings').'" /></p>';

      return $html;
    }

    private function _postProcess() {
      global $currentIndex, $cookie;

      if (Tools::isSubmit('submitBitshares')) {
        $template_available = array('A', 'B', 'C');
        $this->_errors      = array();

       
        if (count($this->_errors) > 0) {
          $error_msg = '';
          
          foreach ($this->_errors AS $error)
            $error_msg .= $error.'<br />';
          
          $this->_html = $this->displayError($error_msg);
        } else {
          $this->_html = $this->displayConfirmation($this->l('Settings updated'));
        }

      }

    }
    public function updateOrder($cart_id, $invoice_id, $status) {
      $invoice_id = stripslashes(str_replace("'", '', $invoice_id));
      $id_order = (int)Order::getOrderByCartId($cart_id);
	  if($id_order === 0)
		return;
      
      $db = Db::getInstance();
      $result = $db->Execute('UPDATE `' . _DB_PREFIX_ . 'order_bitshares` SET `invoice_id` = "'.$invoice_id.'", `status` = ' . intval($status) . ' WHERE `cart_id` = '.intval($cart_id));
    }
    public function createOrder($cart_id) {
      $id_order = (int)Order::getOrderByCartId($cart_id);
	  if($id_order === 0)
		return;   
      $order = new Order($id_order);
      $status = Configuration::get('PS_OS_PREPARATION');
      $db = Db::getInstance();
      $result = $db->Execute('INSERT INTO `' . _DB_PREFIX_ . 'order_bitshares` (`total`, `cart_id`, `id_currency`, `invoice_id`, `status`) VALUES(' . floatval($order->total_paid) . ', ' . intval($cart_id) . ', ' . intval($order->id_currency). ', "Waiting for TX", ' . intval($status) . ')');
	
    }
	public function getReturnURL($cart_id)
	{
      $id_order = (int)Order::getOrderByCartId($cart_id);
      if($id_order === 0)
		return "";	
	  $cart = new Cart(intval($cart_id));	
	  $customer = new Customer((int)$cart->id_customer);
      $url    = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'index.php?controller=order-confirmation?key='.$customer->secure_key.'&id_cart='.intval($cart_id).'&id_module='.$this->id.'&id_order='.intval($id_order);

      return $url;  	
	}
    public function readBitsharespaymentdetails($cart_id) {
      $db = Db::getInstance();
      $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_bitshares` WHERE `cart_id` = ' . intval($cart_id) . ';');
      return $result[0];
    }
    public function getOpenOrders() {
      $db = Db::getInstance();
      $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_bitshares` WHERE `status` = ' . Configuration::get('PS_OS_PREPARATION') . ';');
      return $result;
    }
    public function getOpenOrder($cart_id) {
    
      $db = Db::getInstance();
      $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_bitshares` WHERE `status` = ' . Configuration::get('PS_OS_PREPARATION') . ' AND `cart_id` = ' . intval($cart_id) . ';');
      return $result[0];
    }
    public function getCompleteOrder($cart_id) {
     $db = Db::getInstance();
      $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_bitshares` WHERE `status` = ' . Configuration::get('PS_OS_PAYMENT') . ' AND `cart_id` = ' . intval($cart_id) . ';');
      return $result[0];
    }       
    public function hookInvoice($params) {
      global $smarty;

      $id_order = $params['id_order'];

      $bitsharespaymentdetails = $this->readBitsharespaymentdetails($id_order);
      $invoiceIDMessage = $bitsharespaymentdetails['invoice_id'];
      $invoiceURL = '';
      if($bitsharespaymentdetails['status'] == Configuration::get('PS_OS_PAYMENT'))
      {
		$invoiceURL = 'bts:Trx/'.$bitsharespaymentdetails['invoice_id'];
      }
		
      $smarty->assign(array(
                            'invoiceIDMessage' => $invoiceIDMessage,
                            'invoiceURL'    => $invoiceURL,
                            'status'        => $bitsharespaymentdetails['status']
                           ));
	
      return $this->display(__FILE__, 'invoice_block.tpl');
    }

    public function hookPaymentReturn($params) {
		if (!$this->active)
			return ;

		global $smarty;

		$currency = $params['currencyObj']->iso_code;
		$total = $params['total_to_pay'];
		$order = ($params['objOrder']);
		$order_id = (int)$order->id_cart;
		
		$state = $order->current_state;
        $checkoutURL = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'bitshares/redirect2bitshares.php?order_id='.$order_id.'&code='.$currency.'&total='.$total;
		
		if($state === Configuration::get('PS_OS_PREPARATION'))
		{
			header('refresh:3;url=' . $checkoutURL);
		}	
		$smarty->assign(array(
							'initialcode'  => Configuration::get('PS_OS_PREPARATION'),
							'successcode'  => Configuration::get('PS_OS_PAYMENT'),
							'state'         => $state,
							'url'         => $checkoutURL
							));	
		return $this->display(__FILE__, 'payment_return.tpl');
    }
  }

?>