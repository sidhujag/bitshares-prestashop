<?php
class BTSMailAlert extends ObjectModel
{
	public $id_customer;

	public $customer_email;

	public $id_product;

	public $id_product_attribute;

	public $id_shop;

	public $id_lang;

	public static function getAllMessages($id)
	{
		$messages = Db::getInstance()->executeS('
			SELECT `message`
			FROM `'._DB_PREFIX_.'message`
			WHERE `id_order` = '.(int)$id.'
			ORDER BY `id_message` ASC');
		$result = array();
		foreach ($messages as $message)
			$result[] = $message['message'];

		return implode('<br/>', $result);
	}	
	public static function actionPaymentConfirmation($id_order, $vendor_emails)
	{
		if (empty($vendor_emails))
			return;
		if (!$id_order)
			return;

		if (!is_object($id_order) && is_numeric($id_order))
			$order = new Order((int)$id_order);
		else
			return;
	
		$mails = explode(',', $vendor_emails);
		// Getting differents vars
		$context = Context::getContext();
		$id_lang = (int)$context->language->id;
		$id_shop = (int)$context->shop->id;
		$currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		$customer = new Customer((int)$order->id_customer);
		$configuration = Configuration::getMultiple(
			array(
				'PS_SHOP_EMAIL',
				'PS_MAIL_METHOD',
				'PS_MAIL_SERVER',
				'PS_MAIL_USER',
				'PS_MAIL_PASSWD',
				'PS_SHOP_NAME',
				'PS_MAIL_COLOR'
			), $id_lang, null, $id_shop
		);
		$delivery = new Address((int)$order->id_address_delivery);
		$invoice = new Address((int)$order->id_address_invoice);
		$order_date_text = Tools::displayDate($order->date_add);
		$carrier = new Carrier((int)$order->id_carrier);
		$message = BTSMailAlert::getAllMessages($order->id);
		if (!$message || empty($message))
			$message = 'No message';

		$items_table = '';

		$products = $order->getProducts();
		$customized_datas = Product::getAllCustomizedDatas((int)$order->id_cart);
		Product::addCustomizationPrice($products, $customized_datas);
		foreach ($products as $key => $product)
		{
			$unit_price = $product['product_price_wt'];

			$customization_text = '';
			if (isset($customized_datas[$product['product_id']][$product['product_attribute_id']]))
			{
				foreach ($customized_datas[$product['product_id']][$product['product_attribute_id']][$order->id_address_delivery] as $customization)
				{
					if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD]))
						foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text)
							$customization_text .= $text['name'].': '.$text['value'].'<br />';

					if (isset($customization['datas'][Product::CUSTOMIZE_FILE]))
						$customization_text .= count($customization['datas'][Product::CUSTOMIZE_FILE]).' '.'image(s)'.'<br />';

					$customization_text .= '---<br />';
				}
				if (method_exists('Tools', 'rtrimString'))
					$customization_text = Tools::rtrimString($customization_text, '---<br />');
				else
					$customization_text = preg_replace('/---<br \/>$/', '', $customization_text);
			}

			$items_table .=
				'<tr style="background-color:'.($key % 2 ? '#DDE2E6' : '#EBECEE').';">
					<td style="padding:0.6em 0.4em;text-align:center;">'.$product['product_reference'].'</td>
					<td style="padding:0.6em 0.4em;">
						<strong>'
							.$product['product_name']
							.(isset($product['attributes_small']) ? ' '.$product['attributes_small'] : '')
							.(!empty($customization_text) ? '<br />'.$customization_text : '')
						.'</strong>
					</td>
					<td style="padding:0.6em 0.4em; text-align:center;">'.(int)$product['product_quantity'].'</td>
					<td style="padding:0.6em 0.4em;">
					<table class="table" style="width:100%;border-collapse:collapse">
					<tr>
						<td width="10" style="color:#333;padding:0">&nbsp;</td>
						<td align="right" style="color:#333;padding:0">
							<font size="2" face="Open-sans, sans-serif" color="#555454">
								-
							</font>
						</td>
						<td width="10" style="color:#333;padding:0">&nbsp;</td>
					</tr>
					</table>
					</td>
				</tr>';
		}
		foreach ($order->getCartRules() as $discount)
		{
			$items_table .=
				'<tr style="background-color:#EBECEE;">
						<td colspan="4" style="padding:0.6em 0.4em; text-align:right;">'.'Voucher code:'.' '.$discount['name'].'</td>
			</tr>';
		}
		if ($delivery->id_state)
			$delivery_state = new State((int)$delivery->id_state);
		if ($invoice->id_state)
			$invoice_state = new State((int)$invoice->id_state);

		// Filling-in vars for email
		$template_vars = array(
			'{firstname}' => $customer->firstname,
			'{lastname}' => $customer->lastname,
			'{email}' => $customer->email,
			'{delivery_block_txt}' => BTSMailAlert::getFormatedAddress($delivery, "\n"),
			'{invoice_block_txt}' => BTSMailAlert::getFormatedAddress($invoice, "\n"),
			'{delivery_block_html}' => BTSMailAlert::getFormatedAddress(
					$delivery, '<br />', array(
						'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>',
						'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>'
					)
				),
			'{invoice_block_html}' => BTSMailAlert::getFormatedAddress(
					$invoice, '<br />', array(
						'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>',
						'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>'
					)
				),
			'{delivery_company}' => $delivery->company,
			'{delivery_firstname}' => $delivery->firstname,
			'{delivery_lastname}' => $delivery->lastname,
			'{delivery_address1}' => $delivery->address1,
			'{delivery_address2}' => $delivery->address2,
			'{delivery_city}' => $delivery->city,
			'{delivery_postal_code}' => $delivery->postcode,
			'{delivery_country}' => $delivery->country,
			'{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
			'{delivery_phone}' => $delivery->phone ? $delivery->phone : $delivery->phone_mobile,
			'{delivery_other}' => $delivery->other,
			'{invoice_company}' => $invoice->company,
			'{invoice_firstname}' => $invoice->firstname,
			'{invoice_lastname}' => $invoice->lastname,
			'{invoice_address2}' => $invoice->address2,
			'{invoice_address1}' => $invoice->address1,
			'{invoice_city}' => $invoice->city,
			'{invoice_postal_code}' => $invoice->postcode,
			'{invoice_country}' => $invoice->country,
			'{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
			'{invoice_phone}' => $invoice->phone ? $invoice->phone : $invoice->phone_mobile,
			'{invoice_other}' => $invoice->other,
			'{order_name}' => $order->reference,
			'{shop_name}' => $configuration['PS_SHOP_NAME'],
			'{date}' => $order_date_text,
			'{carrier}' => (($carrier->name == '0') ? $configuration['PS_SHOP_NAME'] : $carrier->name),
			'{payment}' => Tools::substr($order->payment, 0, 32),
			'{items}' => $items_table,
			'{total_paid}' => Tools::displayPrice($order->total_paid, $currency),
			'{total_products}' => Tools::displayPrice($order->getTotalProductsWithTaxes(), $currency),
			'{total_discounts}' => Tools::displayPrice($order->total_discounts, $currency),
			'{total_shipping}' => Tools::displayPrice($order->total_shipping_tax_excl, $currency),
			'{total_tax_paid}' => Tools::displayPrice(
					($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl),
					$currency,
					false
				),
			'{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $currency),
			'{currency}' => $currency->sign,
			'{message}' => $message
		);
		// Shop iso
		$iso = Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT'));

		// Send 1 email by merchant mail, because Mail::Send doesn't work with an array of recipients
		foreach ($mails as $mymailaddress)
		{
			$mymailaddress = trim($mymailaddress);
			// Default language
			$mail_id_lang = $id_lang;
			$mail_iso = $iso;

			// Use the merchant lang if he exists as an employee
			$results = Db::getInstance()->executeS('
				SELECT `id_lang` FROM `'._DB_PREFIX_.'employee`
				WHERE `email` = \''.pSQL($merchant_mail).'\'
			');
			if ($results)
			{
				$user_iso = Language::getIsoById((int)$results[0]['id_lang']);
				if ($user_iso)
				{
					$mail_id_lang = (int)$results[0]['id_lang'];
					$mail_iso = $user_iso;
				}
			}

			$dir_mail = false;
			if (file_exists(dirname(__FILE__).'/mails/'.$mail_iso.'/vendor_order.txt') &&
				file_exists(dirname(__FILE__).'/mails/'.$mail_iso.'/vendor_order.html'))
				$dir_mail = dirname(__FILE__).'/mails/';
      else if(file_exists(dirname(__FILE__).'/mails/en/vendor_order.txt') &&
				file_exists(dirname(__FILE__).'/mails/en/vendor_order.html'))
      {
        $dir_mail = dirname(__FILE__).'/mails/';
      }
			else if (file_exists(_PS_MAIL_DIR_.$mail_iso.'/vendor_order.txt') &&
				file_exists(_PS_MAIL_DIR_.$mail_iso.'/vendor_order.html'))
				$dir_mail = _PS_MAIL_DIR_;
    
			if ($dir_mail)
				Mail::Send(
					$mail_id_lang,
					'vendor_order',
					sprintf(Mail::l('New order : #%d - %s', $mail_id_lang), $order->id, $order->reference),
					$template_vars,
					$mymailaddress,
					null,
					$configuration['PS_SHOP_EMAIL'],
					$configuration['PS_SHOP_NAME'],
					null,
					null,
					$dir_mail,
					null,
					$id_shop
				);
		}
	}


	/*
	 * Generate correctly the address for an email
	 */
	public static function getFormatedAddress(Address $address, $line_sep, $fields_style = array())
	{
		return AddressFormat::generateAddress($address, array('avoid' => array()), $line_sep, ' ', $fields_style);
	}


}

