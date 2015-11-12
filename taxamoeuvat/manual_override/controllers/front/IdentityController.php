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

class IdentityController extends IdentityControllerCore
{
	/**
	 * Assign template vars related to page content
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		if ($this->customer->birthday)
			$birthday = explode('-', $this->customer->birthday);
		else
			$birthday = array('-', '-', '-');

		/* Generate years, months and days */
		$this->context->smarty->assign(array(
				'years' => Tools::dateYears(),
				'sl_year' => $birthday[0],
				'months' => Tools::dateMonths(),
				'sl_month' => $birthday[1],
				'days' => Tools::dateDays(),
				'sl_day' => $birthday[2],
				'errors' => $this->errors,
				'genders' => Gender::getGenders(),
			));

		if (Module::isInstalled('blocknewsletter'))
			$this->context->smarty->assign('newsletter', (int)Module::getInstanceByName('blocknewsletter')->active);

		// start of implementation of the module code - taxamo
		// Get selected country
		if (Tools::isSubmit('taxamoisocountryresidence') && !is_null(Tools::getValue('taxamoisocountryresidence')))
			$selected_country = Tools::getValue('taxamoisocountryresidence');
		else
			$selected_country = TaxamoCCPrefix::getCountryByCustomer($this->customer->id);

		// Generate countries list
		if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES'))
			$countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
		else
			$countries = Country::getCountries($this->context->language->id, true);

		/* todo use helper */
		$list = '<option value="">-</option>';
		foreach ($countries as $country)
		{
			$selected = ($country['iso_code'] == $selected_country) ? 'selected="selected"' : '';
			$list .= '<option value="'.$country['iso_code'].'" '.$selected.'>'.htmlentities($country['name'], ENT_COMPAT, 'UTF-8').'</option>';
		}

		// Get selected cc prefix
		if (Tools::isSubmit('taxamoccprefix') && !is_null(Tools::getValue('taxamoccprefix')))
			$taxamo_cc_prefix = Tools::getValue('taxamoccprefix');
		else
			$taxamo_cc_prefix = TaxamoCCPrefix::getPrefixByCustomer($this->customer->id);

		if ($this->customer->id)
		{
			$this->context->smarty->assign(array(
				'countries_list' => $list,
				'taxamoisocountryresidence' => $selected_country,
				'taxamoccprefix' => $taxamo_cc_prefix
			));
		}
		// end of code implementation module - taxamo

		$this->setTemplate(_PS_THEME_DIR_.'identity.tpl');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_THEME_CSS_DIR_.'identity.css');
		$this->addJS(_PS_JS_DIR_.'validate.js');
	}

}
