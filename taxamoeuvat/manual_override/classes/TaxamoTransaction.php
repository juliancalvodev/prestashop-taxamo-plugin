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

class TaxamoTransaction
{
	public static function addTransaction($id_order, $id_order_state, $key_transaction, $comment = null)
	{
		if (!is_null($id_order) && !is_null($id_order_state))
		{
			Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'taxamo_transaction` (`id_order`, `id_order_state`, `key_transaction`, `comment`)
				VALUES('.$id_order.', '.$id_order_state.", '".$key_transaction."', '".$comment."')"
			);
		}
	}

	public static function getLastIdByOrder($id_order)
	{
		$sql = 'SELECT MAX(`id_taxamo_transaction`)
				FROM `'._DB_PREFIX_.'taxamo_transaction`
				WHERE id_order = '.(int)$id_order;

		$id_taxamo_transaction = DB::getInstance()->getValue($sql);

		return (is_numeric($id_taxamo_transaction)) ? $id_taxamo_transaction : null;
	}

	public static function idExists($id_taxamo_transaction)
	{
		$query = new DbQuery();
		$query->select('id_taxamo_transaction');
		$query->select('id_order');
		$query->select('id_order_state');
		$query->select('key_transaction');
		$query->select('comment');
		$query->from('taxamo_transaction');
		$query->where('id_taxamo_transaction = '.$id_taxamo_transaction);

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
	}

	public static function getHistoryTransactions($id_order)
	{
		$query = new DbQuery();
		$query->select('id_order_state');
		$query->select('key_transaction');
		$query->select('comment');
		$query->from('taxamo_transaction');
		$query->where('id_order = '.$id_order);
		$query->orderBy('id_taxamo_transaction DESC');

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
	}
}
