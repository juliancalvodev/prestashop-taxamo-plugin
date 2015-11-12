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

class OrderHistory extends OrderHistoryCore
{
	public function add($autodate = true, $null_values = false)
	{
		if (!parent::add($autodate))
			return false;

		$order = new Order((int)$this->id_order);
		// Update id_order_state attribute in Order
		$order->current_state = $this->id_order_state;
		$order->update();

		// start of implementation of the module code - taxamo
		$operation = null;
		$list_orderstateoperation = TaxamoOrderStateOperation::getListValues();

		foreach ($list_orderstateoperation as $orderstateoperation)
		{
			if ($orderstateoperation['id_order_state'] == $order->current_state)
				$operation = $orderstateoperation['operation'];
		}

		if (!is_null($operation))
		{
			if ($operation == 1 || $operation == 2)
			{
				$last_id_order_transaction = TaxamoTransaction::getLastIdByOrder($order->id);

				if (is_null($last_id_order_transaction))
				{
					$res_process_store_transaction = Tools::taxamoStoreTransaction($order->id_currency,
						$order->id_address_invoice,
						$order->id_customer,
						$order->getCartProducts());

					if (is_null($res_process_store_transaction['key_transaction']))
						$res_process_store_transaction['comment'] .= '* Transaccion NO Adicionada';
					else
						$res_process_store_transaction['comment'] .= '* Transaccion ADICIONADA';

					TaxamoTransaction::addTransaction($order->id, $order->current_state,
						$res_process_store_transaction['key_transaction'],
						$res_process_store_transaction['comment']);
				}
				else
				{
					$reg_taxamo_transaction = null;
					$reg_taxamo_transaction = TaxamoTransaction::idExists((int)$last_id_order_transaction);
					$res_process_store_transaction['key_transaction'] = $reg_taxamo_transaction[0]['key_transaction'];
					$res_process_store_transaction['comment'] = '';
				}

				if ($operation == 2)
				{
					if (is_null($res_process_store_transaction['key_transaction']))
						$res_process_store_transaction['comment'] .= '* Transaccion NO Confirmada';
					else
					{
						$res_process_confirm_transaction = Tools::taxamoConfirmTransaction($res_process_store_transaction['key_transaction']);

						if (!is_null($res_process_confirm_transaction['status']) && $res_process_confirm_transaction['status'] == 'C')
							$res_process_store_transaction['comment'] .= '* Transaccion CONFIRMADA';
						else
						{
							if (!empty($res_process_confirm_transaction['error']))
							{
								$res_process_store_transaction['comment'] .= $res_process_confirm_transaction['error'];
								$res_process_store_transaction['comment'] .= '* Transaccion NO Confirmada';
							}
						}
					}

					TaxamoTransaction::addTransaction($order->id,
						$order->current_state,
						$res_process_store_transaction['key_transaction'],
						$res_process_store_transaction['comment']);
				}
			}
		}
		// end of code implementation module - taxamo

		Hook::exec('actionOrderHistoryAddAfter', array('order_history' => $this), null, false, true, false, $order->id_shop);

		return true;
	}
}
