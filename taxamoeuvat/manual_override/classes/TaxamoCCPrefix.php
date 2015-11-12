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

class TaxamoCCPrefix
{
	public static function addCCPrefix($id_customer, $iso_country_residence, $cc_prefix, $token)
	{
		if (!is_null($id_customer))
		{
			if ((is_null($iso_country_residence) || empty($iso_country_residence)) && (is_null($cc_prefix) || empty($cc_prefix)))
			{
				if ($token)
				{
					Db::getInstance()->execute('
							INSERT INTO `'._DB_PREFIX_.'taxamo_ccprefix` (`id_customer`, `iso_country_residence`, `cc_prefix`, `token`)
							VALUES('.$id_customer.", NULL, NULL,  '".$token."')"
						);
				}
				else
				{
					Db::getInstance()->execute('
						INSERT INTO `'._DB_PREFIX_.'taxamo_ccprefix` (`id_customer`, `iso_country_residence`, `cc_prefix`, `token`)
						VALUES('.$id_customer.', NULL, NULL, NULL)'
					);
				}
			}
			elseif (is_null($iso_country_residence) || empty($iso_country_residence))
			{
				Db::getInstance()->execute('
					INSERT INTO `'._DB_PREFIX_.'taxamo_ccprefix` (`id_customer`, `iso_country_residence`, `cc_prefix`, `token`)
					VALUES('.$id_customer.', NULL, '.$cc_prefix.', NULL)'
				);
			}
			elseif (is_null($cc_prefix) || empty($cc_prefix))
			{
				Db::getInstance()->execute('
					INSERT INTO `'._DB_PREFIX_.'taxamo_ccprefix` (`id_customer`, `iso_country_residence`, `cc_prefix`, `token`)
					VALUES('.$id_customer.", '".$iso_country_residence."', NULL, NULL)"
				);
			}
			else
			{
				Db::getInstance()->execute('
					INSERT INTO `'._DB_PREFIX_.'taxamo_ccprefix` (`id_customer`, `iso_country_residence`, `cc_prefix`, `token`)
					VALUES('.$id_customer.", '".$iso_country_residence."', ".$cc_prefix.', NULL)'
				);
			}
		}
	}

	public static function getIdByCustomer($id_customer)
	{
		return Db::getInstance()->getValue('
			SELECT `id_taxamo_ccprefix` FROM `'._DB_PREFIX_.'taxamo_ccprefix`
			WHERE `id_customer` = '.$id_customer
		);
	}

	public static function updateCCPrefix($id_customer, $iso_country_residence, $cc_prefix, $token = null)
	{
		if (!is_null($id_customer))
		{
			if ($id_taxamo_ccprefix = self::getIdByCustomer($id_customer))
			{
				if ((is_null($iso_country_residence) || empty($iso_country_residence)) && (is_null($cc_prefix) || empty($cc_prefix)))
				{
					if ($token)
					{
						Db::getInstance()->execute('
							UPDATE `'._DB_PREFIX_."taxamo_ccprefix` SET `token` = '".$token."'
							WHERE `id_taxamo_ccprefix` = ".$id_taxamo_ccprefix
						);
					}
					else
					{
						Db::getInstance()->execute('
							UPDATE `'._DB_PREFIX_.'taxamo_ccprefix` SET `iso_country_residence` = NULL, `cc_prefix` = NULL, `token` = NULL
							WHERE `id_taxamo_ccprefix` = '.$id_taxamo_ccprefix
						);
					}
				}
				elseif (is_null($iso_country_residence) || empty($iso_country_residence))
				{
					Db::getInstance()->execute('
						UPDATE `'._DB_PREFIX_.'taxamo_ccprefix` SET `iso_country_residence` = NULL, `cc_prefix` = '.$cc_prefix.', `cc_prefix` = NULL
						WHERE `id_taxamo_ccprefix` = '.$id_taxamo_ccprefix
					);
				}
				elseif (is_null($cc_prefix) || empty($cc_prefix))
				{
					Db::getInstance()->execute('
						UPDATE `'._DB_PREFIX_."taxamo_ccprefix` SET `iso_country_residence` = '".$iso_country_residence."', `cc_prefix` = NULL, `cc_prefix` = NULL
						WHERE `id_taxamo_ccprefix` = ".$id_taxamo_ccprefix
					);
				}
				else
				{
					Db::getInstance()->execute('
						UPDATE `'._DB_PREFIX_
						."taxamo_ccprefix` SET `iso_country_residence` = '".$iso_country_residence
						."', `cc_prefix` = ".$cc_prefix
						.', `cc_prefix` = NULL WHERE `id_taxamo_ccprefix` = '.$id_taxamo_ccprefix
					);
				}
			}
			else
				self::addCCPrefix($id_customer, $iso_country_residence, $cc_prefix, $token);
		}
	}

	public static function deleteCCPrefix($id_customer)
	{
		if (!is_null($id_customer))
		{
			Db::getInstance()->execute('
				DELETE FROM `'._DB_PREFIX_.'taxamo_ccprefix` WHERE `id_customer` = '.$id_customer
			);
		}
	}

	public static function getPrefixByCustomer($id_customer)
	{
		if (!is_null($id_customer))
		{
			return Db::getInstance()->getValue('
				SELECT `cc_prefix` FROM `'._DB_PREFIX_.'taxamo_ccprefix`
				WHERE `id_customer` = '.$id_customer
			);
		}
		else
			return null;
	}

	public static function getCountryByCustomer($id_customer)
	{
		if (!is_null($id_customer))
		{
			return Db::getInstance()->getValue('
				SELECT `iso_country_residence` FROM `'._DB_PREFIX_.'taxamo_ccprefix`
				WHERE `id_customer` = '.$id_customer
			);
		}
		else
			return null;
	}

	public static function getTokenByCustomer($id_customer)
	{
		if (!is_null($id_customer))
		{
			return Db::getInstance()->getValue('
				SELECT `token` FROM `'._DB_PREFIX_.'taxamo_ccprefix`
				WHERE `id_customer` = '.$id_customer
			);
		}
		else
			return null;
	}
}
