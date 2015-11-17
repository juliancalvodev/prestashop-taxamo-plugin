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

class OrderSlip extends OrderSlipCore
{
	public function addSlipDetail($order_detail_list, $product_qty_list)
	{
		// start of implementation of the module code - taxamo
		$reg_taxamo_transaction = null;
		$last_id_order_transaction = Taxamoeuvat::getLastIdByOrder($this->id_order);

		if (!is_null($last_id_order_transaction))
			$reg_taxamo_transaction = Taxamoeuvat::idExistsTransaction((int)$last_id_order_transaction);
		// end of code implementation module - taxamo

		foreach ($order_detail_list as $key => $id_order_detail)
		{
			if ($qty = (int)$product_qty_list[$key])
			{
				$order_detail = new OrderDetail((int)$id_order_detail);

				// if (Validate::isLoadedObject($order_detail))
				// 	Db::getInstance()->insert('order_slip_detail', array(
				// 		'id_order_slip' => (int)$this->id,
				// 		'id_order_detail' => (int)$id_order_detail,
				// 		'product_quantity' => $qty,
				// 		'amount_tax_excl' => $order_detail->unit_price_tax_excl * $qty,
				// 		'amount_tax_incl' => $order_detail->unit_price_tax_incl * $qty
				// 	));
				// start of implementation of the module code - taxamo
				// se comento el condicional y el insert anterior para incluir en el mismo condicional el llamado a la api de taxamo - refunds
				if (Validate::isLoadedObject($order_detail))
				{
					Db::getInstance()->insert('order_slip_detail', array(
						'id_order_slip' => (int)$this->id,
						'id_order_detail' => (int)$id_order_detail,
						'product_quantity' => $qty,
						'amount_tax_excl' => $order_detail->unit_price_tax_excl * $qty,
						'amount_tax_incl' => $order_detail->unit_price_tax_incl * $qty
					));

					if (!is_null($reg_taxamo_transaction))
						Tools::taxamoRefunds($reg_taxamo_transaction[0]['key_transaction'], $order_detail->product_id, $order_detail->unit_price_tax_incl * $qty);
				}
				// end of code implementation module - taxamo
			}
		}
	}

	public function addPartialSlipDetail($order_detail_list)
	{
		// start of implementation of the module code - taxamo
		$reg_taxamo_transaction = null;
		$last_id_order_transaction = Taxamoeuvat::getLastIdByOrder($this->id_order);

		if (!is_null($last_id_order_transaction))
			$reg_taxamo_transaction = Taxamoeuvat::idExistsTransaction((int)$last_id_order_transaction);
		// end of code implementation module - taxamo

		foreach ($order_detail_list as $id_order_detail => $tab)
		{
			$order_detail = new OrderDetail($id_order_detail);
			$order_slip_resume = self::getProductSlipResume($id_order_detail);

			if ($tab['amount'] + $order_slip_resume['amount_tax_incl'] > $order_detail->total_price_tax_incl)
				$tab['amount'] = $order_detail->total_price_tax_incl - $order_slip_resume['amount_tax_incl'];

			if ($tab['amount'] == 0)
				continue;

			if ($tab['quantity'] + $order_slip_resume['product_quantity'] > $order_detail->product_quantity)
				$tab['quantity'] = $order_detail->product_quantity - $order_slip_resume['product_quantity'];

			$tab['amount_tax_excl'] = $tab['amount_tax_incl'] = $tab['amount'];
			$id_tax = (int)Db::getInstance()->getValue('SELECT `id_tax` FROM `'
				._DB_PREFIX_
				.'order_detail_tax` WHERE `id_order_detail` = '
				.(int)$id_order_detail);
			if ($id_tax > 0)
			{
				$rate = (float)Db::getInstance()->getValue('SELECT `rate` FROM `'._DB_PREFIX_.'tax` WHERE `id_tax` = '.(int)$id_tax);
				if ($rate > 0)
				{
					$rate = 1 + ($rate / 100);
					$tab['amount_tax_excl'] = $tab['amount_tax_excl'] / $rate;
				}
			}

			if ($tab['quantity'] > 0 && $tab['quantity'] > $order_detail->product_quantity_refunded)
			{
				$order_detail->product_quantity_refunded = $tab['quantity'];
				$order_detail->save();
			}

			$insert_order_slip = array(
				'id_order_slip' => (int)$this->id,
				'id_order_detail' => (int)$id_order_detail,
				'product_quantity' => (int)$tab['quantity'],
				'amount_tax_excl' => (float)$tab['amount_tax_excl'],
				'amount_tax_incl' => (float)$tab['amount_tax_incl'],
			);

			Db::getInstance()->insert('order_slip_detail', $insert_order_slip);

			// start of implementation of the module code - taxamo
			if (!is_null($reg_taxamo_transaction))
				Tools::taxamoRefunds($reg_taxamo_transaction[0]['key_transaction'], $order_detail->product_id, (float)$tab['amount_tax_incl']);
			// end of code implementation module - taxamo
		}
	}
}
