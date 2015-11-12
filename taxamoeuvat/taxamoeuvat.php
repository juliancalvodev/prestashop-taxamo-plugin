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

if (!defined('_PS_VERSION_'))
	exit;

if (!defined('_CAN_LOAD_FILES_'))
	exit;

include_once(dirname(__FILE__).'/manual_override/classes/TaxamoOrderStateOperation.php');

class Taxamoeuvat extends Module
{
	private $_html = '';

	public function __construct()
	{
		$this->name = 'taxamoeuvat';
		$this->tab = 'billing_invoicing';
		$this->version = '1.1.10';
		$this->author = 'Taxamo';
		$this->module_key = 'a742d881752030853d3985b6c3c96a38';
		$this->need_instance = 0;
		$this->secure_key = Tools::encrypt($this->name);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Taxamo EU VAT');
		$this->description = $this->l('Module To Use Taxamo Services To Comply With Eu Rules On Vat.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('taxamoeuvat'))
			$this->warning = $this->l('No name provided');
	}

	public function install()
	{
		/* Adds Module */
		if (parent::install() && $this->createTableCCPrefix() && $this->createTableTransaction()
			&& $this->createTableOrderStateOperation() && $this->registerHook('displayAdminOrder'))
			return true;
		return false;
	}

	public function uninstall()
	{
		/* Deletes Module */
		if (parent::uninstall())
		{
			$res = $this->dropTableCCPrefix();
			$res &= $this->dropTableTransaction();
			$res &= $this->dropTableOrderStateOperation();
			$res &= $this->unregisterHook('displayAdminOrder');
			$res &= Configuration::deleteByName('TAXAMOEUVAT_TOKENPUBLIC');
			$res &= Configuration::deleteByName('TAXAMOEUVAT_TOKENPRIVATE');
			$res &= Configuration::deleteByName('TAXAMOEUVAT_GENERICNAME');

			return (bool)$res;
		}
		return false;
	}

	private function createTableCCPrefix()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'taxamo_ccprefix`(
			`id_taxamo_ccprefix` int(10) unsigned NOT NULL auto_increment,
			`id_customer` int(10) unsigned NOT NULL,
			`iso_country_residence` varchar(3) NULL,
			`token` varchar(30) NULL,
			`cc_prefix` int(6) unsigned NULL,
			PRIMARY KEY (`id_taxamo_ccprefix`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

		return Db::getInstance()->execute($sql);
	}

	private function createTableTransaction()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'taxamo_transaction`(
			`id_taxamo_transaction` int(10) unsigned NOT NULL auto_increment,
			`id_order` int(10) unsigned NOT NULL,
			`id_order_state` int(10) unsigned NOT NULL,
			`key_transaction` varchar(255) NULL,
			`comment` varchar(255) NULL,
			PRIMARY KEY (`id_taxamo_transaction`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

		return Db::getInstance()->execute($sql);
	}

	private function createTableOrderStateOperation()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'taxamo_orderstateoperation`(
			`id_taxamo_orderstateoperation` int(10) unsigned NOT NULL auto_increment,
			`id_order_state` int(10) unsigned NOT NULL,
			`operation` int(10) unsigned NOT NULL,
			PRIMARY KEY (`id_taxamo_orderstateoperation`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

		return Db::getInstance()->execute($sql);
	}

	private function dropTableCCPrefix()
	{
		$sql = 'DROP TABLE IF EXISTS
			`'._DB_PREFIX_.'taxamo_ccprefix`';

		return Db::getInstance()->execute($sql);
	}

	private function dropTableTransaction()
	{
		$sql = 'DROP TABLE IF EXISTS
			`'._DB_PREFIX_.'taxamo_transaction`';

		return Db::getInstance()->execute($sql);
	}

	private function dropTableOrderStateOperation()
	{
		$sql = 'DROP TABLE IF EXISTS
			`'._DB_PREFIX_.'taxamo_orderstateoperation`';

		return Db::getInstance()->execute($sql);
	}

	public function getContent()
	{
		$this->_html = null;

		if (Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_ALL)
		{
			$this->_html .= '<p class="alert alert-danger">'.
				$this->l(sprintf('You cannot add configuration from a "All Shops" or a "Group Shop" context')).
				'</p>';
		}
		else
		{
			if (Tools::isSubmit('addOrderStateOperation') || Tools::isSubmit('edit_id_taxamo_orderstateoperation'))
				$this->_html .= $this->renderAddForm();
			else
			{
				if (Tools::isSubmit('submitSettings') || Tools::isSubmit('submitOrderState') || Tools::isSubmit('delete_id_taxamo_orderstateoperation'))
				{
					if ($this->postValidation())
						$this->postProcess();
				}

				$this->_html .= $this->renderForm();
				$this->_html .= $this->renderList();
			}
		}

		return $this->_html;
	}

	private function postValidation()
	{
		$errors = array();

		if (Tools::isSubmit('submitSettings'))
		{
			$token_public = Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC');
			if (!$token_public || empty($token_public) || !Validate::isString($token_public))
				$errors[] = $this->l('Key public invalid value');

			$token_private = Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE');
			if (!$token_private || empty($token_private) || !Validate::isString($token_private))
				$errors[] = $this->l('Key private invalid value');

			$generic_name = Tools::getValue('TAXAMOEUVAT_GENERICNAME');
			if (!$generic_name || empty($generic_name) || !Validate::isString($generic_name))
				$errors[] = $this->l('Generic name invalid value');

			$process = Tools::getValue('TAXAMOEUVAT_PROCESS');
			if (is_null($process) || !Validate::isInt($process) || ($process != 0 && $process != 1))
				$errors[] = $this->l('Process invalid value');
		}
		elseif (Tools::isSubmit('submitOrderState'))
		{
			if (Tools::isSubmit('id_taxamo_orderstateoperation'))
			{
				$id_taxamo_orderstateoperation = Tools::getValue('id_taxamo_orderstateoperation');
				if (!$id_taxamo_orderstateoperation || empty($id_taxamo_orderstateoperation) || !Validate::isInt($id_taxamo_orderstateoperation))
					$errors[] = $this->l('Invalid id_taxamo_orderstateoperation');
			}

			$id_order_state = Tools::getValue('id_order_state');
			if (!$id_order_state || empty($id_order_state) || !Validate::isInt($id_order_state))
				$errors[] = $this->l('Order State invalid value');

			$operation = Tools::getValue('operation');
			if (!$operation || empty($operation) || !in_array($operation, array(1, 2)))
				$errors[] = $this->l('Operation invalid value');
		}
		elseif (Tools::isSubmit('delete_id_taxamo_orderstateoperation'))
		{
			$delete_id_taxamo_orderstateoperation = Tools::getValue('delete_id_taxamo_orderstateoperation');
			if (!$delete_id_taxamo_orderstateoperation || empty($delete_id_taxamo_orderstateoperation)
				|| !Validate::isInt($delete_id_taxamo_orderstateoperation))
				$errors[] = $this->l('Invalid delete_id_taxamo_orderstateoperation');
		}

		/* Display errors if needed */
		if (count($errors))
		{
			$this->_html .= $this->displayError(implode('<br />', $errors));

			return false;
		}

		/* Returns if validation is ok */
		return true;
	}

	private function postProcess()
	{
		$errors = array();

		if (Tools::isSubmit('submitSettings'))
		{
			$res = Configuration::updateValue('TAXAMOEUVAT_TOKENPUBLIC', Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC'));
			$res &= Configuration::updateValue('TAXAMOEUVAT_TOKENPRIVATE', Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE'));
			$res &= Configuration::updateValue('TAXAMOEUVAT_GENERICNAME', Tools::getValue('TAXAMOEUVAT_GENERICNAME'));
			if (!$res)
				$errors[] = $this->displayError($this->l('The configuration could not be updated.'));
			elseif (Tools::getValue('TAXAMOEUVAT_PROCESS'))
			{
				// if ($this->createParams())
				// {
				//     $this->_html .= $this->displayConfirmation($this->l('Parameters Created'));
				// }
				$res_taxamo_create_params = Tools::taxamoCreateParams();

				if ($res_taxamo_create_params['success'])
					$this->_html .= $this->displayConfirmation($this->l('Parameters Created'));
				else
					$this->_html .= $this->displayError($res_taxamo_create_params['error']);
			}
		}
		elseif (Tools::isSubmit('submitOrderState'))
		{
			$id_taxamo_orderstateoperation = Tools::getValue('id_taxamo_orderstateoperation');
			if ($id_taxamo_orderstateoperation)
			{
				if (!TaxamoOrderStateOperation::idExists($id_taxamo_orderstateoperation))
					$errors[] = $this->displayError($this->l('Taxamo id_taxamo_orderstateoperation does not exists.'));
			}

			$id_order_state = (int)Tools::getValue('id_order_state');
			$operation = (int)Tools::getValue('operation');

			if (!$errors)
			{
				/* Adds */
				if (!$id_taxamo_orderstateoperation)
				{
					if (!TaxamoOrderStateOperation::orderStateExists($id_order_state))
					{
						if (!TaxamoOrderStateOperation::addOrderStateOperation($id_order_state, $operation))
							$errors[] = $this->displayError($this->l('The order state could not be added.'));
					}
					else
						$errors[] = $this->displayError($this->l('The order state could not be added, exists.'));
				}
				/* Update */
				else
				{
					if (TaxamoOrderStateOperation::orderStateExists($id_order_state))
					{
						if (!TaxamoOrderStateOperation::updateOrderStateOperation($id_order_state, $operation))
							$errors[] = $this->displayError($this->l('The order state could not be updated.'));
					}
					else
						$errors[] = $this->displayError($this->l('The order state could not be updated, exists.'));
				}
			}
		}
		elseif (Tools::isSubmit('delete_id_taxamo_orderstateoperation'))
		{
			$delete_id_taxamo_orderstateoperation = Tools::getValue('delete_id_taxamo_orderstateoperation');
			if (TaxamoOrderStateOperation::idExists($delete_id_taxamo_orderstateoperation))
			{
				if (!TaxamoOrderStateOperation::deleteOrderStateOperation($delete_id_taxamo_orderstateoperation))
					$errors[] = $this->displayError($this->l('The product could not be deleted.'));
			}
			else
				$errors[] = $this->displayError($this->l('delete_id_taxamo_orderstateoperation does not exists.'));
		}

		/* Display errors if needed */
		if (count($errors))
			$this->_html .= $this->displayError(implode('<br />', $errors));
		elseif (Tools::isSubmit('submitOrderState'))
		{
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name
				.'&tab_module='.$this->tab.'&module_name='.$this->name);
		}
	}

	private function createParams()
	{
		$product_types = array();
		$public_token = Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC', Configuration::get('TAXAMOEUVAT_TOKENPUBLIC'));

		$res_api_product_types = $this->getResApi(
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
				$this->_html .= $this->displayError($this->l('Error In Product Types Dictionary'));
				return false;
			}
		}
		else
			return false;

		@set_time_limit(0);
		$generic_name = Tools::getValue('TAXAMOEUVAT_GENERICNAME', Configuration::get('TAXAMOEUVAT_GENERICNAME'));
		$params_tax = array();

		$res_api_countries = $this->getResApi(
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
						continue;

					foreach ($product_types as $product_type)
					{
						$res_api_calculate = $this->getResApi(
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
					}
				}
			}
		}

		foreach ($params_tax as $key => $tax_values)
		{
			if (!Validate::isGenericName($tax_values['name']))
				continue;

			if ($id_tax = Tax::getTaxIdByName($tax_values['name']))
			{
				$params_tax[$key]['id_tax'] = $id_tax;
				continue;
			}

			$tax = new Tax();
			$tax->name[(int)Configuration::get('PS_LANG_DEFAULT')] = (string)$tax_values['name'];
			$tax->rate = (float)$tax_values['rate'];
			$tax->active = 1;

			if (($error = $tax->validateFields(false, true)) !== true || ($error = $tax->validateFieldsLang(false, true)) !== true)
			{
				$this->_html .= $this->displayError('Invalid tax properties.').' '.$error;
				return false;
			}

			if (!$tax->add())
			{
				$this->_html .= $this->displayError('An error occurred while importing the tax: ').(string)$tax_values['name'];
				return false;
			}

			$params_tax[$key]['id_tax'] = $tax->id;
		}

		foreach ($product_types as $product_type)
		{
			$tax_group_name = $generic_name.' - '.$product_type;

			if (!Validate::isGenericName($tax_group_name))
				continue;

			if (TaxRulesGroup::getIdByName($tax_group_name))
			{
				$this->_html .= $this->displayError('This tax rule group cannot be saved, exists.');
				return false;
			}

			$trg = new TaxRulesGroup();
			$trg->name = $tax_group_name;
			$trg->active = 1;

			if (!$trg->save())
			{
				$this->_html .= $this->displayError('This tax rule group cannot be saved.');
				return false;
			}

			foreach ($params_tax as $tax_values)
			{
				if ($tax_values['product_type'] == $product_type)
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

		return true;
	}

	private function getResApi($url, $verb, $params = null)
	{
		if (is_null($url) || is_null($verb) || !in_array($verb, array('GET', 'POST')))
		{
			$this->_html .= $this->displayError($this->l('Error In Configuration Of The Api'));
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

		if ($verb == 'POST' && count($params))
		{
			curl_setopt($curl_obj, CURLOPT_POST, true);
			curl_setopt($curl_obj, CURLOPT_POSTFIELDS, $params_string);
			curl_setopt($curl_obj, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: '.Tools::strlen($params_string)
			));
		}

		curl_setopt($curl_obj, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_obj, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_obj, CURLOPT_NOSIGNAL, true);
		curl_setopt($curl_obj, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_obj, CURLOPT_TIMEOUT, 30);

		$res_exec = curl_exec($curl_obj);

		$curl_errno = curl_errno($curl_obj);
		$curl_error = curl_error($curl_obj);

		curl_close($curl_obj);

		if ($curl_errno)
		{
			$this->_html .= $this->displayError($this->l('API error ').$url.(string)$curl_errno.' : '.$curl_error);
			return false;
		}
		else
			return Tools::jsonDecode($res_exec, true);
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Key public'),
						'name' => 'TAXAMOEUVAT_TOKENPUBLIC',
						'suffix' => 'token',
						'size' => 64,
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Key private'),
						'name' => 'TAXAMOEUVAT_TOKENPRIVATE',
						'suffix' => 'token',
						'size' => 64,
						'required' => true
					),
					// array(
					//     'type' => 'text',
					//     'label' => $this->l('Generic name'),
					//     'name' => 'TAXAMOEUVAT_GENERICNAME',
					//     'desc' => $this->l('Name For The Creation Of Taxes And Tax Rules'),
					//     'size' => 64,
					//     'required' => true
					// ),
					array(
						'type' => 'hidden',
						'name' => 'TAXAMOEUVAT_GENERICNAME'
					),
					// array(
					//     'type' => 'radio',
					//     'label' => $this->l('After Recording'),
					//     'desc' => $this->l('Please Decide If You Want The Module To Create Tax Parameters For You?'),
					//     'name' => 'TAXAMOEUVAT_PROCESS',
					//     'required' => true,
					//     'is_bool' => true,
					//     'values' => array(
					//         array(
					//             'id' => 'process_off',
					//             'value' => 0,
					//             'label' => $this->l('Do Not Create Parameters')
					//         ),
					//         array(
					//             'id' => 'process_on',
					//             'value' => 1,
					//             'label' => $this->l('Create Parameters')
					//         )
					//     )
					// )
					array(
						'type' => 'hidden',
						'name' => 'TAXAMOEUVAT_PROCESS'
					)
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;
		$this->fields_form = array();

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitSettings';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name;
		$helper->currentIndex .= '&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues()
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'TAXAMOEUVAT_TOKENPUBLIC' => Tools::getValue('TAXAMOEUVAT_TOKENPUBLIC', Configuration::get('TAXAMOEUVAT_TOKENPUBLIC')),
			'TAXAMOEUVAT_TOKENPRIVATE' => Tools::getValue('TAXAMOEUVAT_TOKENPRIVATE', Configuration::get('TAXAMOEUVAT_TOKENPRIVATE')),
			// 'TAXAMOEUVAT_GENERICNAME' => Tools::getValue('TAXAMOEUVAT_GENERICNAME', Configuration::get('TAXAMOEUVAT_GENERICNAME')),
			// 'TAXAMOEUVAT_PROCESS' => 0
			'TAXAMOEUVAT_GENERICNAME' => 'EU VAT',
			'TAXAMOEUVAT_PROCESS' => 1
		);
	}

	public function renderList()
	{
		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		$list_orderstateoperation = TaxamoOrderStateOperation::getListValues();
		$q_orderstateoperation = 0;

		foreach ($list_orderstateoperation as $key => $orderstateoperation)
		{
			foreach ($statuses as $status)
			{
				if ($status['id_order_state'] == $orderstateoperation['id_order_state'])
				{
					$list_orderstateoperation[$key]['orderstate_name'] = trim($status['name']);
					break;
				}
			}

			if ($orderstateoperation['operation'] == 1)
				$list_orderstateoperation[$key]['operation_name'] = $this->l('Store Transaction');
			else
				$list_orderstateoperation[$key]['operation_name'] = $this->l('Store and Confirm transaction');

			$q_orderstateoperation++;
		}

		$this->context->smarty->assign(
			array(
				'link' => $this->context->link,
				'list_orderstateoperation' => $list_orderstateoperation,
				'Q_orderstateoperation' => $q_orderstateoperation
			)
		);

		return $this->display(__FILE__, 'views/templates/admin/list.tpl');
	}

	public function renderAddForm()
	{
		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		$array_operations = array(
			array(
				'type_operation' => 1,
				'name' => $this->l('Store Transaction')
			),
			array(
				'type_operation' => 2,
				'name' => $this->l('Store and Confirm transaction')
			)
		);

		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Order State -> Operation'),
					'icon' => 'icon-cogs'
				),
				'input' => array(),
				'submit' => array(
					'title' => $this->l('Save')
				)
			)
		);

		if (Tools::isSubmit('edit_id_taxamo_orderstateoperation')
			&& TaxamoOrderStateOperation::idExists((int)Tools::getValue('edit_id_taxamo_orderstateoperation')))
		{
			$fields_form['form']['input'][] = array(
				'type' => 'hidden',
				'name' => 'id_taxamo_orderstateoperation'
			);
			$fields_form['form']['input'][] = array(
				'type' => 'hidden',
				'name' => 'id_order_state'
			);
			$fields_form['form']['input'][] = array(
				'type' => 'text',
				'label' => $this->l('Order State'),
				'name' => 'orderstate_name',
				'readonly' => true
			);
		}
		else
		{
			$array_order_state = array();
			foreach ($statuses as $status)
			{
				$array_order_state[] = array(
					'id_orderstate' => $status['id_order_state'],
					'name' => $status['id_order_state'].' - '.$status['name']
				);
			}

			$fields_form['form']['input'][] = array(
				'type' => 'select',
				'label' => $this->l('Order state'),
				'desc' => $this->l('Select an Order State'),
				'name' => 'id_order_state',
				'required' => true,
				'options' => array(
					'query' => $array_order_state,
					'id' => 'id_orderstate',
					'name' => 'name'
				)
			);
		}

		$fields_form['form']['input'][] = array(
			'type' => 'select',
			'label' => $this->l('Operation'),
			'desc' => $this->l('Select Operation Type'),
			'name' => 'operation',
			'required' => true,
			'options' => array(
				'query' => $array_operations,
				'id' => 'type_operation',
				'name' => 'name'
			)
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;
		$this->fields_form = array();
		$helper->module = $this;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitOrderState';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name;
		$helper->currentIndex .= '&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getAddFieldsValues($statuses),
		);

		$helper->override_folder = '/';

		return $helper->generateForm(array($fields_form));
	}

	public function getAddFieldsValues($statuses = null)
	{
		$fields = array();

		$fields['id_order_state'] = null;
		$fields['operation'] = null;

		if (Tools::isSubmit('edit_id_taxamo_orderstateoperation'))
		{
			$row = TaxamoOrderStateOperation::idExists((int)Tools::getValue('edit_id_taxamo_orderstateoperation'));

			if ($row)
			{
				$fields['id_taxamo_orderstateoperation'] = $row[0]['id_taxamo_orderstateoperation'];
				$fields['id_order_state'] = $row[0]['id_order_state'];
				$fields['operation'] = $row[0]['operation'];

				if (!is_null($statuses))
				{
					foreach ($statuses as $status)
					{
						if ($status['id_order_state'] == $fields['id_order_state'])
						{
							$fields['orderstate_name'] = $status['name'];
							break;
						}
					}
				}
			}
		}

		return $fields;
	}

	public function hookDisplayAdminOrder($params)
	{
		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		$list_history_transactions = TaxamoTransaction::getHistoryTransactions($params['id_order']);
		$q_history_transactions = 0;

		foreach ($list_history_transactions as $key => $history_transaction)
		{
			foreach ($statuses as $status)
			{
				if ($status['id_order_state'] == $history_transaction['id_order_state'])
				{
					$list_history_transactions[$key]['orderstate_name'] = trim($status['name']);
					break;
				}
			}

			$q_history_transactions++;
		}

		$this->context->smarty->assign(
			array(
				'list_history_transactions' => $list_history_transactions,
				'Q_history_transactions' => $q_history_transactions
			)
		);

		return $this->display(__FILE__, 'views/templates/admin/info_taxamo.tpl');
	}
}
