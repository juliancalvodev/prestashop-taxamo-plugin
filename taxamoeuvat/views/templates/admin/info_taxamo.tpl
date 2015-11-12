{*
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
*  @author Taxamo <johnoliver@keepersolutions.com>
*  @copyright  2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Taxamo
*}


<div id="taxamoeuvat_module_info_taxamo" class="panel">
    <h3><i class="icon-list-ul"></i> {l s='History transaction Taxamo' mod='taxamoeuvat'}
        <span class="badge">{$Q_history_transactions}</span>
    </h3>
    {if $Q_history_transactions > 0}
        <div class="row">
            <table class="table">
                <thead>
                    <tr>
                        <th><span class="title_box text-left">{l s='Order State' mod='taxamoeuvat'}</span></th>
                        <th><span class="title_box text-left">{l s='Key transaction' mod='taxamoeuvat'}</span></th>
                        <th><span class="title_box text-left">{l s='Comment' mod='taxamoeuvat'}</span></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $list_history_transactions as $historytransaction}
                    <tr>
                        <td class="text-left">{(int)$historytransaction['id_order_state']} - {$historytransaction['orderstate_name']}</td>
                        <td class="text-left">{$historytransaction['key_transaction']}</td>
                        <td class="text-left">{$historytransaction['comment']}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
            <div class="clearfix">&nbsp;</div>
        </div>
    {else}
        <div class="row alert alert-warning">{l s='No history transaction taxamo found.' mod='taxamoeuvat'}</div>
    {/if}
</div>
