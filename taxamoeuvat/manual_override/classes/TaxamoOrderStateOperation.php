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

class TaxamoOrderStateOperation
{
	public static function getListValues()
	{
		$query = new DbQuery();
		$query->select('id_taxamo_orderstateoperation');
		$query->select('id_order_state');
		$query->select('operation');
		$query->from('taxamo_orderstateoperation');

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
	}

	public static function idExists($id_taxamo_orderstateoperation)
	{
		$query = new DbQuery();
		$query->select('id_taxamo_orderstateoperation');
		$query->select('id_order_state');
		$query->select('operation');
		$query->from('taxamo_orderstateoperation');
		$query->where('id_taxamo_orderstateoperation = '.$id_taxamo_orderstateoperation);

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
	}

	public static function orderStateExists($id_order_state)
	{
		$query = new DbQuery();
		$query->select('id_taxamo_orderstateoperation');
		$query->select('id_order_state');
		$query->select('operation');
		$query->from('taxamo_orderstateoperation');
		$query->where('id_order_state = '.$id_order_state);

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
	}

	public static function addOrderStateOperation($id_order_state, $operation)
	{
		return Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'taxamo_orderstateoperation` (`id_order_state`, `operation`)
			VALUES('.$id_order_state.', '.$operation.')'
		);
	}

	public static function updateOrderStateOperation($id_order_state, $operation)
	{
		return Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'taxamo_orderstateoperation` SET `operation` = '.$operation.'
			WHERE `id_order_state` = '.$id_order_state
		);
	}

	public static function deleteOrderStateOperation($id_taxamo_orderstateoperation)
	{
		return Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'taxamo_orderstateoperation` WHERE `id_taxamo_orderstateoperation` = '.$id_taxamo_orderstateoperation
		);
	}
}
