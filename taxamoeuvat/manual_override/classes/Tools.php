<?php
/**
* 2015 Taxamo
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* It is available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Taxamo <johnoliver@keepersolutions.com>
*  @copyright 2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Taxamo
*/

class Tools extends ToolsCore
{
	/* start of implementation of the module code - taxamo */

	public static function validCurrency($id_currency)
	{
		$ret_valid_currency = null;

		if ($id_currency <= 0 || ( !($res_currency = Currency::getCurrency($id_currency)) || empty($res_currency) ))
			$ret_valid_currency['error'] = 'The selected currency is not valid';
		else
			$ret_valid_currency['currency_code'] = $res_currency['iso_code'];

		return $ret_valid_currency;
	}

	public static function getResApi($url, $verb, $params = null)
	{
		if (is_null($url) || is_null($verb) || !in_array($verb, array('GET', 'POST')))
		{
			// $this->_html .= $this->displayError($this->l('Error In Configuration Of The Api'));
			return false;
		}

		$url_api = $url;
		$params_string = null;
		if (count($params))
		{
			if ($verb == 'GET')
			{
				$params_string = http_build_query($params);
				$url_api .= '?'.$params_string;
			}
			elseif ($verb == 'POST')
				$params_string = Tools::jsonEncode($params);
		}

		$curl_obj = curl_init();
		curl_setopt($curl_obj, CURLOPT_URL, $url_api);

		// if ($verb == 'POST' && count($params))
		// {
		//     curl_setopt($curl_obj, CURLOPT_POST, true);
		//     curl_setopt($curl_obj, CURLOPT_POSTFIELDS, $params_string);
		//     curl_setopt($curl_obj, CURLOPT_HTTPHEADER, array(
		//         'Content-Type: application/json',
		//         'Content-Length: ' . strlen($params_string)
		//     ));
		// }
		if ($verb == 'POST')
		{
			curl_setopt($curl_obj, CURLOPT_POST, true);

			if (count($params))
			{
				curl_setopt($curl_obj, CURLOPT_POSTFIELDS, $params_string);
				curl_setopt($curl_obj, CURLOPT_HTTPHEADER, array(
					'Source-Id: ks-prestashop-1-rc-1-10',
					'Content-Type: application/json',
					'Content-Length: '.Tools::strlen($params_string)
				));
			}
			else
			{
				curl_setopt($curl_obj, CURLOPT_HTTPHEADER, array(
					'Source-Id: ks-prestashop-1-rc-1-10',
					'Content-Type: application/json',
					'Content-Length: 0'
				));
			}
		}
		else
		{
			curl_setopt($curl_obj, CURLOPT_HTTPHEADER, array(
				'Source-Id: ks-prestashop-1-rc-1-10'
			));
		}

		curl_setopt($curl_obj, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_obj, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_obj, CURLOPT_NOSIGNAL, true);
		curl_setopt($curl_obj, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_obj, CURLOPT_TIMEOUT, 30);

		$res_exec = curl_exec($curl_obj);

		$curl_errno = curl_errno($curl_obj);
		//$curl_error = curl_error($curl_obj);

		curl_close($curl_obj);

		if ($curl_errno)
		{
			// $this->_html .= $this->displayError($this->l('Error In API ') . $url . strval($curl_errno) . ' : ' . $curl_error);
			return false;
		}
		else
			return Tools::jsonDecode($res_exec, true);
	}

	public static function getMerchantsSelfSettings()
	{
		$res_api_merchants_self_settings = self::getResApi(
			'https://dashboard.taxamo.com/app/v1/merchants/self/settings/public',
			'GET',
			array(
				'public_token' => Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC', Configuration::get('TAXAMOEUVAT_TOKENPUBLIC'))
			)
		);

		$ret_merchants_self_settings = array();

		if ($res_api_merchants_self_settings && isset($res_api_merchants_self_settings['settings']))
		{
			$tmp_res_api = $res_api_merchants_self_settings['settings'];

			if (isset($tmp_res_api['allow_eu_b2b']))
				$ret_merchants_self_settings['allow_eu_b2b'] = $tmp_res_api['allow_eu_b2b'];
			else
				$ret_merchants_self_settings['allow_eu_b2b'] = false;

			if (isset($tmp_res_api['allow_contradictory_evidence']))
				$ret_merchants_self_settings['allow_contradictory_evidence'] = $tmp_res_api['allow_contradictory_evidence'];
			else
				$ret_merchants_self_settings['allow_contradictory_evidence'] = false;

			if (isset($tmp_res_api['allow_self_declaration']))
				$ret_merchants_self_settings['allow_self_declaration'] = $tmp_res_api['allow_self_declaration'];
			else
				$ret_merchants_self_settings['allow_self_declaration'] = false;

			if (isset($tmp_res_api['allow_sms_verification']))
				$ret_merchants_self_settings['allow_sms_verification'] = $tmp_res_api['allow_sms_verification'];
			else
				$ret_merchants_self_settings['allow_sms_verification'] = false;
		}
		else
			$ret_merchants_self_settings['error'] = 'Error In Api Of The Cart/merchant Settings';

		return $ret_merchants_self_settings;
	}

	public static function getDictionariesProductTypes()
	{
		$res_api_product_types = self::getResApi(
			'https://api.taxamo.com/api/v1/dictionaries/product_types',
			'GET',
			array(
				'public_token' => Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC', Configuration::get('TAXAMOEUVAT_TOKENPUBLIC'))
			)
		);

		$ret_dictionaries_product_types = array();

		if ($res_api_product_types && isset($res_api_product_types['dictionary']))
		{
			$product_types = array();
			$res_product_types_dictionary = $res_api_product_types['dictionary'];
			foreach ($res_product_types_dictionary as $product_type)
			{
				if (isset($product_type['code']))
					$product_types[] = $product_type['code'];
			}

			if (!count($product_types))
				$ret_dictionaries_product_types['error'] = 'Error In The Product Dictionary';
			else
				$ret_dictionaries_product_types = $product_types;
		}
		else
			$ret_dictionaries_product_types['error'] = 'Error In The Api Of The Product Dictionary';

		return $ret_dictionaries_product_types;
	}

	public static function getTaxCalculate($params_transaction, $params_transaction_lines)
	{
		$params_tax_calculate = array();

		$params_tax_calculate['transaction'] = $params_transaction;
		$params_tax_calculate['transaction']['transaction_lines'] = $params_transaction_lines;
		$params_tax_calculate['private_token'] = Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE', Configuration::get('TAXAMOEUVAT_TOKENPRIVATE'));

		$res_api_calculate_tax = self::getResApi(
			'https://api.taxamo.com/api/v1/tax/calculate',
			'POST',
			$params_tax_calculate
		);

		$ret_tax_calculate = array();

		if (isset($res_api_calculate_tax['errors']))
			$ret_tax_calculate['error'] = $res_api_calculate_tax['errors'][0];
		else
		{
			if ($res_api_calculate_tax && isset($res_api_calculate_tax['transaction']['transaction_lines']))
				$ret_tax_calculate = $res_api_calculate_tax['transaction'];
			else
				$ret_tax_calculate['error'] = 'Error In The Taxamo Tax Calculation.';
		}

		return $ret_tax_calculate;
	}

	public static function validTransaction($iso_country_code, $transaction)
	{
		$ret_valid_transaction = array();
		$ret_valid_transaction['success'] = false;
		$ret_valid_transaction['no_country_match'] = false;

		if (!$transaction || !isset($transaction['transaction_lines']))
			$ret_valid_transaction['error'] = 'Error In The Taxamo Tax Calculation..';
		elseif (!isset($transaction['tax_country_code']) || ($transaction['tax_country_code'] != $iso_country_code))
		{
			$ret_valid_transaction['no_country_match'] = true;
			$ret_valid_transaction['error'] = 'Information Does Not Match Your Billing Address, Please Check Information And Try Again';
		}
		elseif (!isset($transaction['invoice_address']['country']) || ($transaction['invoice_address']['country'] != $iso_country_code))
			$ret_valid_transaction['error'] = 'The Vat Address And Billing Address Do Not Match';
		else
			$ret_valid_transaction['success'] = true;

		return $ret_valid_transaction;
	}

	public static function getValidEvidence($index, $transaction)
	{
		$ret_valid_evidence = array();

		switch ($index)
		{
			case 'by_tax_number':
					if (isset($transaction['evidence']['by_tax_number']))
					{
						if (!(bool)$transaction['evidence']['by_tax_number']['used'])
						{
							$ret_valid_evidence['error'] = 'We Need To Verify Your Billing Address As The Vat Number Is From A Different Country.';
							$ret_valid_evidence['error'] .= ' Please Update Your Billing Address';
						}
					}
					else
						$ret_valid_evidence['error'] = 'We Need More Information To Verify Your Billing Address, Enter Your Vat Number On Your Billing Address';
				break;

			case 'by_cc':
					if (isset($transaction['evidence']['by_cc']))
					{
						if (!(bool)$transaction['evidence']['by_cc']['used'])
						{
							$ret_valid_evidence['error'] = 'We Need To Verify Your Billing Address, Your Credit Card Prefix Does Not Match';
							$ret_valid_evidence['error'] .= ' The Indicated Billing Country, Please Update Your User Data';
						}
					}
					else
					{
						$ret_valid_evidence['error'] = 'We Need More Information To Verify Your Billing Address, Please Enter Your Credit Card';
						$ret_valid_evidence['error'] .= ' Prefix In Your User Data <strong><a href="'
							.Tools::str2url('identity').'" class="alert-link">Click here.</a></strong>';
					}
				break;

			case 'forced':
					if (isset($transaction['evidence']['forced']))
					{
						if (!(bool)$transaction['evidence']['forced']['used'])
						{
							$ret_valid_evidence['error'] = 'We Need To Verify Your Billing Address, Your Country Of Residence Does Not Match';
							$ret_valid_evidence['error'] .= ' The Billing Country Indicated, Please Update Your User Data';
						}
					}
					else
					{
						$ret_valid_evidence['error'] = 'We Need More Information To Verify Your Billing Address, Please Enter Your Country Of';
						$ret_valid_evidence['error'] = ' Residence In Your User Data <strong><a href="'
							.Tools::str2url('identity').'" class="alert-link">Click here.</a></strong>';
					}
				break;
		}

		return $ret_valid_evidence;
	}

	public static function validTransactionLines($info_transaction_lines, $transaction)
	{
		$ret_valid_transaction_lines = array();
		$ret_valid_transaction_lines['errors'] = array();
		$diferencia_minima_a = 1;
		$diferencia_minima_b = 0.00001;

		foreach ($transaction['transaction_lines'] as $transaction_line)
		{
			$key = $transaction_line['custom_id'];

			if (isset($info_transaction_lines[$key]))
			{
				if (!isset($transaction_line['informative']))
				{
					if ($info_transaction_lines[$key]['product_type'] != $transaction_line['product_type'])
						$ret_valid_transaction_lines['errors'][] = 'Error In Verification Of Product Type Returned By The Api, Please Review The Configuration.';
				}
				if ($info_transaction_lines[$key]['quantity'] != $transaction_line['quantity'])
					$ret_valid_transaction_lines['errors'][] = 'Error In The Number Of Products Returned By The Api, Please Review Configuration.';
				if (abs($info_transaction_lines[$key]['unit_price'] - $transaction_line['unit_price']) > $diferencia_minima_a)
					$ret_valid_transaction_lines['errors'][] = 'Error In The Unit Value Returned By The Api Products, Please Check Configuration.';
				if (abs($info_transaction_lines[$key]['amount'] - $transaction_line['amount']) > $diferencia_minima_b)
					$ret_valid_transaction_lines['errors'][] = 'Error In The Number Of Products Returned By The Api, Please Review Configuration.';
				// if (abs($info_transaction_lines[$key]['tax_rate'] - $transaction_line['tax_rate']) > $diferencia_minima_b)
				// {
				//     $ret_valid_transaction_lines['errors'][] = 'Error In The Tax Rate Of Products Returned By The Api, Please Review Configuration.';
				// }
				if (abs($info_transaction_lines[$key]['tax_amount'] - $transaction_line['tax_amount']) > $diferencia_minima_b)
					$ret_valid_transaction_lines['errors'][] = 'Error In The Tax Rate Of Products Returned By The Api, Please Review Configuration.';
				if (abs($info_transaction_lines[$key]['total_amount'] - $transaction_line['total_amount']) > $diferencia_minima_b)
					$ret_valid_transaction_lines['errors'][] = 'Error In The Total Amount Of Products Returned By The Api, Please Review Configuration.';
			}
		}

		if (count($ret_valid_transaction_lines))
			$ret_valid_transaction_lines['success'] = false;
		else
			$ret_valid_transaction_lines['success'] = true;

		return $ret_valid_transaction_lines;
	}

	public static function transactions($params_transaction, $params_transaction_lines)
	{
		$params_tax_calculate = array();

		$params_tax_calculate['transaction'] = $params_transaction;
		$params_tax_calculate['transaction']['transaction_lines'] = $params_transaction_lines;
		$params_tax_calculate['private_token'] = Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE', Configuration::get('TAXAMOEUVAT_TOKENPRIVATE'));

		$res_api_calculate_tax = self::getResApi(
			'https://api.taxamo.com/api/v1/transactions',
			'POST',
			$params_tax_calculate
		);

		$ret_tax_calculate = array();

		if (isset($res_api_calculate_tax['errors']))
			$ret_tax_calculate['error'] = $res_api_calculate_tax['errors'][0];
		else
		{
			if ($res_api_calculate_tax && isset($res_api_calculate_tax['transaction']['transaction_lines']))
				$ret_tax_calculate = $res_api_calculate_tax['transaction'];
			else
				$ret_tax_calculate['error'] = 'Taxamo Api Error In Calculating Tax.';
		}

		return $ret_tax_calculate;
	}

	public static function taxamoVerifyTaxes($cart)
	{
		$errors = array();
		$transaction_lines = array();
		$info_transaction_lines = array();

		if (!isset($cart))  //determinar si hay un cart valido
			$errors[] = 'No Currency Type Selected In The Cart';
		else    //si hay cart valido procede a obtener la moneda usada en el cart
		{
			$id_currency = $cart->id_currency;

			$valid_currency = self::validCurrency($id_currency);

			if (isset($valid_currency['error']))
				$errors[] = $valid_currency['error'];
			else
				$currency_code = $valid_currency['currency_code'];
		}

		if (!count($errors))    //si no hay errores procede a obtener settings para evaluacion de evidencias
		{
			$merchants_self_settings = self::getMerchantsSelfSettings();

			if (isset($merchants_self_settings['error']))
				$errors[] = $merchants_self_settings['error'];
		}

		if (!count($errors))    //si no hay errores procede a obetner diccionario de tipos de productos
		{
			$product_types = self::getDictionariesProductTypes();

			if (isset($product_types['error']))
				$errors[] = $product_types['error'];
		}

		if (!count($errors))    //si no hay errores procede a preparar las lineas de la transaccion (items de productos del cart)
		{
			foreach ($cart->getProducts() as $product)
			{
				$product_type = null;

				foreach ($product_types as $find_product_type)
				{
					if (strpos($product['tax_name'], $find_product_type))
					{
						$product_type = $find_product_type;
						break 1;
					}
				}

				if (is_null($product_type))
				{
					$transaction_lines[] = array(
						'custom_id' => (string)$product['id_product'],
						'quantity' => $product['cart_quantity'],
						'unit_price' => $product['price'],
						'informative' => true,
						'tax_rate' => $product['rate']
					);
				}
				else
				{
					$transaction_lines[] = array(
						'custom_id' => (string)$product['id_product'],
						'quantity' => $product['cart_quantity'],
						'unit_price' => $product['price'],
						'product_type' => $product_type
					);
				}

				$info_transaction_lines[(string)$product['id_product']] = array(
					'product_type' => $product_type,
					'quantity' => $product['cart_quantity'],
					'unit_price' => $product['price'],
					'amount' => $product['total'],
					'tax_rate' => $product['rate'],
					'tax_amount' => $product['total_wt'] - $product['total'],
					'total_amount' => $product['total_wt']
				);
			}
		}

		if (count($transaction_lines))  //si hay lineas de transaccion preparadas procede al calculo del tax con las api de taxamo
		{
			$invoice_address = new Address((int)$cart->id_address_invoice);
			$iso_country_code = Country::getIsoById($invoice_address->id_country);

			$success_verify_taxes = false;

			if ($merchants_self_settings['allow_eu_b2b']) //si estan configuradas las opciones para b2b procede con este paso
			{
				if ((bool)Configuration::get('PS_B2B_ENABLE'))
				{
					if (!is_null($invoice_address->vat_number) && !empty($invoice_address->vat_number))
					{
						$params_tax_calculate = array(
							'currency_code' => $currency_code,
							'buyer_tax_number' => $invoice_address->vat_number,
							'invoice_address' => array(
								'street_name' => $invoice_address->address1,
								'address_detail' => $invoice_address->address2,
								'city' => $invoice_address->city,
								'postal_code' => $invoice_address->postcode,
								'country' => $iso_country_code
							)
						);

						$tax_calculate = self::getTaxCalculate($params_tax_calculate, $transaction_lines);

						if (isset($tax_calculate['error']))
							$errors[] = $tax_calculate['error'];
						else
						{
							$valid_transaction = self::validTransaction($iso_country_code, $tax_calculate);

							$success_verify_taxes = $valid_transaction['success'];

							if (isset($valid_transaction['error']))
							{
								$errors[] = $valid_transaction['error'];

								$valid_evidence = self::getValidEvidence('by_tax_number', $tax_calculate);

								if (isset($valid_evidence['error']))
									$errors[] = $valid_evidence['error'];
							}
						}
					}
				}
			}

			if (!count($errors) && !$success_verify_taxes)  //si el procedimiento de b2b no se ejecuto o no fue exitoso se procede a una validacion normal
			{
				if (!empty($_SERVER['HTTP_CLIENT_IP']))
				{
					$buyer_ip = $_SERVER['HTTP_CLIENT_IP'];
				// } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				//     $buyer_ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
				} elseif (!empty($_SERVER['REMOTE_ADDR']))
					$buyer_ip = $_SERVER['REMOTE_ADDR'];
				else
					$buyer_ip = null;

				$params_tax_calculate = array(
					'currency_code' => $currency_code,
					'buyer_ip' => $buyer_ip,
					'billing_country_code' => $iso_country_code,
					'invoice_address' => array(
						'street_name' => $invoice_address->address1,
						'address_detail' => $invoice_address->address2,
						'city' => $invoice_address->city,
						'postal_code' => $invoice_address->postcode,
						'country' => $iso_country_code
					)
				);

				$sms_verification = false;

				if ($merchants_self_settings['allow_sms_verification'])
				{
					$verification_token = TaxamoCCPrefix::getTokenByCustomer($cart->id_customer);

					if ($verification_token)
					{
						$res_api_verify_sms_token = self::taxamoVerifySmsToken($verification_token);

						if (isset($res_api_verify_sms_token['country_code']) && $res_api_verify_sms_token['country_code'] == $iso_country_code)
						{
							$params_tax_calculate['verification_token'] = $verification_token;
							$sms_verification = true;
						}
						else
						{
							$iso_country_residence = TaxamoCCPrefix::getCountryByCustomer($cart->id_customer);
							$cc_prefix = TaxamoCCPrefix::getPrefixByCustomer($cart->id_customer);
							TaxamoCCPrefix::updateCCPrefix($cart->id_customer, $iso_country_residence, $cc_prefix, null);
						}
					}
				}

				$tax_calculate = self::getTaxCalculate($params_tax_calculate, $transaction_lines);

				if (isset($tax_calculate['error']))
					$errors[] = $tax_calculate['error'];
				else
				{
					$valid_transaction = self::validTransaction($iso_country_code, $tax_calculate);

					$success_verify_taxes = $valid_transaction['success'];

					if (isset($valid_transaction['error']))
					{
						if (!$valid_transaction['no_country_match'] || ($valid_transaction['no_country_match'] && !$merchants_self_settings['allow_self_declaration']))
							$errors[] = $valid_transaction['error'];
					}
				}

				$no_buyer_credit_card_prefix = false;
				$no_force_country_code = false;

				//si el procedimiento de b2b y normal no se ejecuto o no fue exitoso se procede a una validacion con self declaration
				if (!$success_verify_taxes && !$sms_verification && $merchants_self_settings['allow_self_declaration'])
				{
					$errors = array();

					$buyer_credit_card_prefix = TaxamoCCPrefix::getPrefixByCustomer($cart->id_customer);

					if (is_null($buyer_credit_card_prefix) || empty($buyer_credit_card_prefix))
						$no_buyer_credit_card_prefix = true;
					else
						$params_tax_calculate['buyer_credit_card_prefix'] = $buyer_credit_card_prefix;

					$force_country_code = TaxamoCCPrefix::getCountryByCustomer($cart->id_customer);

					if (is_null($force_country_code) || empty($force_country_code))
						$no_force_country_code = true;
					else
						$params_tax_calculate['force_country_code'] = $force_country_code;

					if (!$no_buyer_credit_card_prefix || !$no_force_country_code)
					{
						$tax_calculate = self::getTaxCalculate($params_tax_calculate, $transaction_lines);

						if (isset($tax_calculate['error']))
							$errors[] = $tax_calculate['error'];
						else
						{
							$valid_transaction = self::validTransaction($iso_country_code, $tax_calculate);

							$success_verify_taxes = $valid_transaction['success'];

							if (isset($valid_transaction['error']))
								$errors[] = $valid_transaction['error'];
						}
					}

					if (!$success_verify_taxes)
					{
						$valid_evidence = self::getValidEvidence('by_cc', $tax_calculate);

						if (isset($valid_evidence['error']))
							$errors[] = $valid_evidence['error'];

						$valid_evidence = self::getValidEvidence('forced', $tax_calculate);

						if (isset($valid_evidence['error']))
							$errors[] = $valid_evidence['error'];
					}
				}
			}

			if ($success_verify_taxes)
			{
				$valid_transaction_lines = self::validTransactionLines($info_transaction_lines, $tax_calculate);

				if (!$valid_transaction_lines['success'])
				{
					$success_verify_taxes = false;
					$errors = $valid_transaction_lines['errors'];
				}
			}
		}

		return $errors;
	}

	public static function taxamoStoreTransaction($id_currency, $id_address_invoice, $id_customer, $order_products)
	{
		$exists_comments = false;
		$res_process = array(
			'key_transaction' => null,
			'comment' => ''
		);
		$transaction_lines = array();
		$info_transaction_lines = array();

		$valid_currency = self::validCurrency($id_currency);

		if (isset($valid_currency['error']))
		{
			$res_process['comment'] .= '* '.$valid_currency['error'];
			$exists_comments = true;
		}
		else
			$currency_code = $valid_currency['currency_code'];

		if (!$exists_comments)
		{
			$merchants_self_settings = self::getMerchantsSelfSettings();

			if (isset($merchants_self_settings['error']))
			{
				$res_process['comment'] .= '* '.$merchants_self_settings['error'];
				$exists_comments = true;
			}
		}

		if (!$exists_comments)
		{
			$product_types = self::getDictionariesProductTypes();

			if (isset($product_types['error']))
			{
				$res_process['comment'] .= '* '.$product_types['error'];
				$exists_comments = true;
			}
			else
			{
				$generic_name = Tools::getValue('TAXAMOEUVAT_GENERICNAME', Configuration::get('TAXAMOEUVAT_GENERICNAME'));
				$arr_tax_rules_group = array();

				foreach ($product_types as $product_type)
				{
					$tax_group_name = $generic_name.' - '.$product_type;

					$arr_tax_rules_group[$product_type] = TaxRulesGroup::getIdByName($tax_group_name);
				}
			}
		}

		if (!$exists_comments)
		{
			foreach ($order_products as $product)
			{
				$id_tax_rules_group = $product['id_tax_rules_group'];

				$product_type = null;

				foreach ($arr_tax_rules_group as $key_product_type => $val_id_product_type)
				{
					if ($id_tax_rules_group == $val_id_product_type)
					{
						$product_type = $key_product_type;
						break 1;
					}
				}

				if (is_null($product_type))
				{
					$transaction_lines[] = array(
						'custom_id' => (string)$product['id_product'],
						'quantity' => $product['cart_quantity'],
						'unit_price' => $product['price'],
						'informative' => true,
						'tax_rate' => $product['tax_rate']
					);
				}
				else
				{
					$transaction_lines[] = array(
						'custom_id' => (string)$product['id_product'],
						'quantity' => $product['cart_quantity'],
						'unit_price' => $product['price'],
						'product_type' => $product_type
					);
				}

				$info_transaction_lines[(string)$product['id_product']] = array(
					'product_type' => $product_type,
					'quantity' => $product['cart_quantity'],
					'unit_price' => $product['price'],
					'amount' => $product['total_price'],
					'tax_rate' => $product['tax_rate'],
					'tax_amount' => $product['total_wt'] - $product['total_price'],
					'total_amount' => $product['total_wt']
				);
			}
		}

		if (count($transaction_lines))
		{
			$invoice_address = new Address((int)$id_address_invoice);
			$iso_country_code = Country::getIsoById($invoice_address->id_country);

			$success_verify_taxes = false;

			if ($merchants_self_settings['allow_eu_b2b']) //si estan configuradas las opciones para b2b procede con este paso
			{
				if ((bool)Configuration::get('PS_B2B_ENABLE'))
				{
					if (!is_null($invoice_address->vat_number) && !empty($invoice_address->vat_number))
					{
						$params_tax_calculate = array(
							'currency_code' => $currency_code,
							'buyer_tax_number' => $invoice_address->vat_number,
							'invoice_address' => array(
								'street_name' => $invoice_address->address1,
								'address_detail' => $invoice_address->address2,
								'city' => $invoice_address->city,
								'postal_code' => $invoice_address->postcode,
								'country' => $iso_country_code
							)
						);

						$tax_calculate = self::transactions($params_tax_calculate, $transaction_lines);

						$valid_transaction = self::validTransaction($iso_country_code, $tax_calculate);

						$success_verify_taxes = $valid_transaction['success'];

						if (isset($valid_transaction['error']))
						{
							$res_process['comment'] .= '* '.$valid_transaction['error'];
							$exists_comments = true;

							$valid_evidence = self::getValidEvidence('by_tax_number', $tax_calculate);

							if (isset($valid_evidence['error']))
							{
								$res_process['comment'] .= '* '.$valid_evidence['error'];
								$exists_comments = true;
							}
						}
					}
				}
			}

			if (!$exists_comments && !$success_verify_taxes)  //si el procedimiento de b2b no se ejecuto o no fue exitoso se procede a una validacion normal
			{
				if (!empty($_SERVER['HTTP_CLIENT_IP']))
				{
					$buyer_ip = $_SERVER['HTTP_CLIENT_IP'];
				// } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				//     $buyer_ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
				} elseif (!empty($_SERVER['REMOTE_ADDR']))
					$buyer_ip = $_SERVER['REMOTE_ADDR'];
				else
					$buyer_ip = null;

				$params_tax_calculate = array(
					'currency_code' => $currency_code,
					'buyer_ip' => $buyer_ip,
					'billing_country_code' => $iso_country_code,
					'invoice_address' => array(
						'street_name' => $invoice_address->address1,
						'address_detail' => $invoice_address->address2,
						'city' => $invoice_address->city,
						'postal_code' => $invoice_address->postcode,
						'country' => $iso_country_code
					)
				);

				$sms_verification = false;

				if ($merchants_self_settings['allow_sms_verification'])
				{
					$verification_token = TaxamoCCPrefix::getTokenByCustomer($id_customer);

					if ($verification_token)
					{
						$res_api_verify_sms_token = self::taxamoVerifySmsToken($verification_token);

						if (isset($res_api_verify_sms_token['country_code']) && $res_api_verify_sms_token['country_code'] == $iso_country_code)
						{
							$params_tax_calculate['verification_token'] = $verification_token;
							$sms_verification = true;
						}
						else
						{
							$iso_country_residence = TaxamoCCPrefix::getCountryByCustomer($id_customer);
							$cc_prefix = TaxamoCCPrefix::getPrefixByCustomer($id_customer);
							TaxamoCCPrefix::updateCCPrefix($id_customer, $iso_country_residence, $cc_prefix, null);
						}
					}
				}

				$tax_calculate = self::transactions($params_tax_calculate, $transaction_lines);

				if (isset($tax_calculate['error']))
				{
					$res_process['comment'] .= '* '.$tax_calculate['error'];
					$exists_comments = true;
				}
				else
				{
					$valid_transaction = self::validTransaction($iso_country_code, $tax_calculate);

					$success_verify_taxes = $valid_transaction['success'];

					if (isset($valid_transaction['error']))
					{
						if (!$valid_transaction['no_country_match'] || ($valid_transaction['no_country_match'] && !$merchants_self_settings['allow_self_declaration']))
						{
							$res_process['comment'] .= '* '.$valid_transaction['error'];
							$exists_comments = true;
						}
					}
				}

				$no_buyer_credit_card_prefix = false;
				$no_force_country_code = false;

				//si el procedimiento de b2b y normal no se ejecuto o no fue exitoso se procede a una validacion con self declaration
				if (!$success_verify_taxes && !$sms_verification && $merchants_self_settings['allow_self_declaration'])
				{
					$exists_comments = false;
					$res_process['comment'] = '';

					$buyer_credit_card_prefix = TaxamoCCPrefix::getPrefixByCustomer($id_customer);

					if (is_null($buyer_credit_card_prefix) || empty($buyer_credit_card_prefix))
						$no_buyer_credit_card_prefix = true;
					else
						$params_tax_calculate['buyer_credit_card_prefix'] = $buyer_credit_card_prefix;

					$force_country_code = TaxamoCCPrefix::getCountryByCustomer($id_customer);

					if (is_null($force_country_code) || empty($force_country_code))
						$no_force_country_code = true;
					else
						$params_tax_calculate['force_country_code'] = $force_country_code;

					if (!$no_buyer_credit_card_prefix || !$no_force_country_code)
					{
						$tax_calculate = self::transactions($params_tax_calculate, $transaction_lines);

						$valid_transaction = self::validTransaction($iso_country_code, $tax_calculate);

						$success_verify_taxes = $valid_transaction['success'];

						if (isset($valid_transaction['error']))
						{
							$res_process['comment'] .= '* '.$valid_transaction['error'];
							$exists_comments = true;
						}
					}

					if (!$success_verify_taxes)
					{
						$valid_evidence = self::getValidEvidence('by_cc', $tax_calculate);

						if (isset($valid_evidence['error']))
						{
							$res_process['comment'] .= '* '.$valid_evidence['error'];
							$exists_comments = true;
						}

						$valid_evidence = self::getValidEvidence('forced', $tax_calculate);

						if (isset($valid_evidence['error']))
						{
							$res_process['comment'] .= '* '.$valid_evidence['error'];
							$exists_comments = true;
						}
					}
				}
			}

			// if ($success_verify_taxes)
			if (isset($tax_calculate['key']) && !is_null($tax_calculate['key']) && !empty($tax_calculate['key']))
			{
				$valid_transaction_lines = self::validTransactionLines($info_transaction_lines, $tax_calculate);

				if (isset($tax_calculate['key']))
					$res_process['key_transaction'] = $tax_calculate['key'];

				if (!$valid_transaction_lines['success'])
				{
					$success_verify_taxes = false;
					foreach ($valid_transaction_lines['errors'] as $error)
						$res_process['comment'] .= '* '.$error;
					$exists_comments = true;
				}
			}
		}

		return $res_process;
	}

	public static function taxamoConfirmTransaction($key_transaction)
	{
		$res_process = array(
			'status' => null,
			'error' => ''
		);

		$res_api_confirm_transaction = self::getResApi(
			'https://api.taxamo.com/api/v1/transactions/'.trim($key_transaction).'/confirm',
			'POST',
			array(
				'private_token' => Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE', Configuration::get('TAXAMOEUVAT_TOKENPRIVATE'))
			)
		);

		if (!$res_api_confirm_transaction || !isset($res_api_confirm_transaction['transaction']['status']))
			$res_process['error'] = '* Error In Confirmation Of Transaction Api';
		else
			$res_process['status'] = $res_api_confirm_transaction['transaction']['status'];

		return $res_process;
	}

	public static function taxamoRefunds($key_transaction, $id_line, $amount_with_tax)
	{
		// $res_api_transactions_refunds = self::getResApi(
		//     'https://api.taxamo.com/api/v1/transactions/' . trim($key_transaction) . '/refunds',
		//     'POST',
		//     array(
		//         'private_token' => Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE', Configuration::get('TAXAMOEUVAT_TOKENPRIVATE')),
		//         'custom_id' => strval($id_line),
		//         'total_amount' => $amount_with_tax
		//     )
		// );
		$param_url = 'https://api.taxamo.com/api/v1/transactions/'.trim($key_transaction).'/refunds?custom_id='.$id_line;
		$param_url .= '&private_token='.Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE', Configuration::get('TAXAMOEUVAT_TOKENPRIVATE'));
		$param_url .= '&total_amount='.$amount_with_tax;

		self::getResApi($param_url, 'POST');
	}

	public static function taxamoCreateSmsToken($country_code, $recipient)
	{
		$res_api_create_sms_token = self::getResApi(
			'https://api.taxamo.com/api/v1/verification/sms?country_code='.$country_code
				.'&public_token='.Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC', Configuration::get('TAXAMOEUVAT_TOKENPUBLIC'))
				.'&recipient='.$recipient,
			'POST'
		);

		return $res_api_create_sms_token;
	}

	public static function taxamoVerifySmsToken($token)
	{
		$param_url = 'https://api.taxamo.com/api/v1/verification/sms/'.$token;
		$param_url .= '?public_token='.Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC', Configuration::get('TAXAMOEUVAT_TOKENPUBLIC'));

		$res_api_verify_sms_token = self::getResApi(
			$param_url,
			'GET'
		);

		return $res_api_verify_sms_token;
	}

	public static function taxamoCreateParams()
	{
		$res_craete_params = array(
			'error' => null,
			'success' => false
			);
		$product_types = array();
		$public_token = Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC', Configuration::get('TAXAMOEUVAT_TOKENPUBLIC'));

		$res_api_product_types = self::getResApi(
			'https://api.taxamo.com/api/v1/dictionaries/product_types',
			'GET',
			array(
				'public_token' => $public_token
			)
		);

		if ($res_api_product_types && isset($res_api_product_types['dictionary']))
		{
			$res_product_types_dictionary = $res_api_product_types['dictionary'];

			foreach ($res_product_types_dictionary as $product_type)
			{
				if (isset($product_type['code']))
					$product_types[] = $product_type['code'];
			}

			if (count($product_types) <= 0)
			{
				$res_craete_params['error'] = 'Error In Product Types Dictionary';
				return $res_craete_params;
			}
		}
		else
		{
			$res_craete_params['error'] = 'Error In API Product Types Dictionary';
			return $res_craete_params;
		}

		@set_time_limit(0);
		$generic_name = Tools::getValue('TAXAMOEUVAT_GENERICNAME', Configuration::get('TAXAMOEUVAT_GENERICNAME'));
		$params_tax = array();

		$res_api_countries = self::getResApi(
			'https://api.taxamo.com/api/v1/dictionaries/countries',
			'GET',
			array(
				'tax_supported' => 'true',
				'public_token' => $public_token
			)
		);

		if ($res_api_countries && isset($res_api_countries['dictionary']))
		{
			$res_countries_dictionary = $res_api_countries['dictionary'];

			foreach ($res_countries_dictionary as $country)
			{
				if (isset($country['tax_supported'], $country['cca2']) && (bool)$country['tax_supported'])
				{
					$id_country = Country::getByIso($country['cca2']);
					if (!$id_country)
					{
						// continue;
						$res_craete_params['error'] = 'Error In Procedure Countries';
						return $res_craete_params;
					}

					foreach ($product_types as $product_type)
					{
						$res_api_calculate = self::getResApi(
							'https://api.taxamo.com/api/v1/tax/calculate',
							'GET',
							array(
								'public_token' => $public_token,
								'currency_code' => 'EUR',
								'force_country_code' => $country['cca2'],
								'buyer_tax_number' => null,
								'product_type' => $product_type,
								'amount' => 100
							)
						);

						if ($res_api_calculate && isset($res_api_calculate['transaction']['transaction_lines'][0]))
						{
							$res_tax = $res_api_calculate['transaction']['transaction_lines'][0];

							$tax_name = $generic_name.' '.$country['cca2'].' '.$product_type.' '.trim((string)$res_tax['tax_rate']).'%';

							$params_tax[] = array(
								'name' => $tax_name,
								'rate' => $res_tax['tax_rate'],
								'active' => 1,
								'product_type' => $product_type,
								'id_country' => $id_country
							);
						}
						else
						{
							$res_craete_params['error'] = 'Error In API Tax Calculate';
							return $res_craete_params;
						}
					}
				}
			}

			if (count($params_tax) <= 0)
			{
				$res_craete_params['error'] = 'Error In Countries Dictionary';
				return $res_craete_params;
			}
		}
		else
		{
			$res_craete_params['error'] = 'Error In API Countries Dictionary';
			return $res_craete_params;
		}

		foreach ($params_tax as $key => $tax_values)
		{
			if (!Validate::isGenericName($tax_values['name']))
			{
				// continue;
				$res_craete_params['error'] = 'Error In Procedure Tax';
				return $res_craete_params;
			}

			if (!$id_tax = Tax::getTaxIdByName($tax_values['name'], 1))
				$id_tax = Tax::getTaxIdByName($tax_values['name'], 0);

			if ($id_tax)
			{
				$tax = new Tax($id_tax);

				if (($tax->rate != (float)$tax_values['rate']) || ($tax->active != 1))
				{
					$tax->rate = (float)$tax_values['rate'];
					$tax->active = 1;

					if (($error = $tax->validateFields(false, true)) !== true || ($error = $tax->validateFieldsLang(false, true)) !== true)
					{
						$res_craete_params['error'] = 'Invalid tax properties (update). '.$error;
						return $res_craete_params;
					}

					if (!$tax->update())
					{
						$res_craete_params['error'] = 'An error occurred while updating the tax: '.(string)$tax_values['name'];
						return $res_craete_params;
					}
				}

				$params_tax[$key]['id_tax'] = $id_tax;
			}
			else
			{
				$tax = new Tax();
				$tax->name[(int)Configuration::get('PS_LANG_DEFAULT')] = (string)$tax_values['name'];
				$tax->rate = (float)$tax_values['rate'];
				$tax->active = 1;

				if (($error = $tax->validateFields(false, true)) !== true || ($error = $tax->validateFieldsLang(false, true)) !== true)
				{
					$res_craete_params['error'] = 'Invalid tax properties (add). '.$error;
					return $res_craete_params;
				}

				if (!$tax->add())
				{
					$res_craete_params['error'] = 'An error occurred while importing the tax: '.(string)$tax_values['name'];
					return $res_craete_params;
				}

				$params_tax[$key]['id_tax'] = $tax->id;
			}
		}

		foreach ($product_types as $product_type)
		{
			$tax_group_name = $generic_name.' - '.$product_type;

			if (!Validate::isGenericName($tax_group_name))
			{
				// continue;
				$res_craete_params['error'] = 'Error In Procedure Product Type';
				return $res_craete_params;
			}

			if ($id_tax_rules_group = TaxRulesGroup::getIdByName($tax_group_name))
			{
				$trg = new TaxRulesGroup($id_tax_rules_group);

				if ($trg->active != 1)
				{
					$trg->active = 1;

					if (!$trg->update())
					{
						$res_craete_params['error'] = 'An error occurred while updating the tax rule group: '.(string)$tax_values['name'];
						return $res_craete_params;
					}
				}

				$is_new_record = false;
			}
			else
			{
				$trg = new TaxRulesGroup();
				$trg->name = $tax_group_name;
				$trg->active = 1;

				if (!$trg->add())
				{
					$res_craete_params['error'] = 'An error occurred while importing the tax rule group: '.(string)$tax_values['name'];
					return $res_craete_params;
				}

				$is_new_record = true;
			}

			foreach ($params_tax as $tax_values)
			{
				if ($tax_values['product_type'] == $product_type)
				{
					if ($is_new_record)
					{
						// Creation
						$tr = new TaxRule();
						$tr->id_tax_rules_group = $trg->id;
						$tr->id_country = $tax_values['id_country'];
						$tr->id_state = 0;
						$tr->id_county = 0;
						$tr->zipcode_from = 0;
						$tr->zipcode_to = 0;
						$tr->behavior = 0;
						$tr->description = '';
						$tr->id_tax = $tax_values['id_tax'];
						$tr->save();
					}
				}
			}
		}

		$res_craete_params['success'] = true;
		return $res_craete_params;
	}

	/* end of implementation of the module code - taxamo */
}
