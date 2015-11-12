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

<div class="panel">
    <h3><i class="icon-list-ul"></i> {l s='Order State list' mod='taxamoeuvat'}
        <span class="badge">{$Q_orderstateoperation}</span>
    </h3>
    {if $Q_orderstateoperation > 0}
        <div class="row">
            <table class="table">
                <thead>
                    <tr>
                        <th><span class="title_box text-center">{l s='ID' mod='taxamoeuvat'}</span></th>
                        <th><span class="title_box text-left">{l s='Order State' mod='taxamoeuvat'}</span></th>
                        <th><span class="title_box text-left">{l s='Operation' mod='taxamoeuvat'}</span></th>
                        <th><span class="title_box text-right">{l s='Actions' mod='taxamoeuvat'}</span></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $list_orderstateoperation as $orderstateoperation}
                    <tr>
                        <td class="text-center">{(int)$orderstateoperation['id_taxamo_orderstateoperation']}</td>
                        <td class="text-left">{(int)$orderstateoperation['id_order_state']} - {$orderstateoperation['orderstate_name']}</td>
                        <td class="text-left">{(int)$orderstateoperation['operation']} - {$orderstateoperation['operation_name']}</td>
                        <td>
                            <div class="btn-group-action">
                                <div class="btn-group pull-right">
                                    <a href="{$link->getAdminLink('AdminModules')}&configure=taxamoeuvat&edit_id_taxamo_orderstateoperation={$orderstateoperation.id_taxamo_orderstateoperation}" class="btn btn-default">
                                        <i class="icon-pencil"></i> {l s='Edit' mod='taxamoeuvat'}
                                    </a>
                                    <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <span class="caret"></span>&nbsp;
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{$link->getAdminLink('AdminModules')}&configure=taxamoeuvat&delete_id_taxamo_orderstateoperation={$orderstateoperation.id_taxamo_orderstateoperation}" onclick="return confirm('{l s='Do you really want to delete this order state' mod='taxamoeuvat'}');">
                                                <i class="icon-trash"></i> {l s='Delete' mod='taxamoeuvat'}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
            <div class="clearfix">&nbsp;</div>
        </div>
    {else}
        <div class="row alert alert-warning">{l s='No orders state found.' mod='taxamoeuvat'}</div>
    {/if}
    <div class="panel-footer">
        <a class="btn btn-default pull-right" href="{$link->getAdminLink('AdminModules')}&configure=taxamoeuvat&addOrderStateOperation=1">
            <i class="process-icon-plus"></i> {l s='Add new order state' mod='taxamoeuvat'}
        </a>
    </div>
</div>
