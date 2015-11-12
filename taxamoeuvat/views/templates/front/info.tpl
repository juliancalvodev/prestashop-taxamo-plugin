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

{include file="$tpl_dir./errors.tpl"}

{if $allow_sms_verification}
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">You can also verify with a quick SMS message:</h3>
        </div>
        <div class="panel-body">
            <div class="alert alert-danger" id="alertErrorInputPhone">Enter a cell phone number in {$country_name} </div>
            <div class="alert alert-danger" id="alertErrorProcess"></div>
            <div class="alert alert-success" id="alertSuccessInput">Already verification token to your cell phone was sent, enter the code we sent below.</div>
            <div class="alert alert-danger" id="alertErrorInputToken">Enter the token send</div>

            <form class="form-inline text-center" id="formsms">
                <div class="form-group">
                    <label class="sr-only">Phone</label>
                    <p class="form-control-static">Enter your phone number in {$country_name} </p>
                </div>
                <div class="form-group">
                    <label for="inputPhone" class="sr-only">Number phone</label>
                    <input type="number" class="form-control" required name="inputPhone" id="inputPhone" placeholder="cell phone number (no country code necessary)">
                </div>
                <button type="submit" id="buttonFormSms" class="btn btn-primary" data-loading-text="Requesting token..."  autocomplete="off">Confirm identity</button>
            </form>

            <form class="form-inline text-center" id="formtoken">
                <div class="form-group">
                    <label class="sr-only">Token</label>
                    <p class="form-control-static">Please enter the code you received via SMS</p>
                </div>
                <div class="form-group">
                    <label for="inputToken" class="sr-only">Token</label>
                    <input type="text" class="form-control" required name="inputToken" id="inputToken" placeholder="Code">
                </div>
                <button type="submit" id="buttonFormToken" class="btn btn-primary" data-loading-text="Verify token..."  autocomplete="off">Verify token</button>
            </form>
        </div>
    </div>
{/if}

<script>
$(document).ready(function() {
    $("#alertErrorInputPhone").hide();
    $("#alertErrorProcess").hide();
    $("#alertSuccessInput").hide();
    $("#alertErrorInputToken").hide();
    $("#formtoken").hide();

    $("#formsms").submit(function() {
        event.preventDefault();

        var $btn = $("#buttonFormSms").button('loading');

        if ($("#inputPhone").val())
        {
            $("#alertErrorInputPhone").hide();
            $("#alertErrorProcess").hide();

            var url = '{$base_dir}modules/taxamoeuvat/ajax-call.php';
            var iso_country_code = '{$iso_country_code}';

            $.post(
                url,
                { isoCountryCode: iso_country_code, recipient: $("#inputPhone").val() },
                function( data ) {
                    if (data.success)
                    {
                        $("#formsms").hide();
                        $("#alertErrorInputPhone").hide();
                        $("#alertErrorProcess").hide();
                        $("#alertSuccessInput").show();
                        $("#formtoken").show();
                    }
                    else
                    {
                        $("#alertErrorProcess").text(data.errors[0]);
                        $("#alertErrorProcess").show();
                        $btn.button('reset');
                        $("#inputPhone").focus();
                    }
                },
                "json"
                );
        }
        else
        {
            $("#alertErrorInputPhone").show();
            $btn.button('reset');
            $("#inputPhone").focus();
        }
    });

    $("#formtoken").submit(function() {
        event.preventDefault();

        var $btn = $("#buttonFormToken").button('loading');

        if ($("#inputToken").val())
        {
            $("#alertSuccessInput").hide();
            $("#alertErrorInputToken").hide();
            $("#alertErrorProcess").hide();

            var url = '{$base_dir}modules/taxamoeuvat/ajax-call.php';
            var iso_country_code = '{$iso_country_code}';

            $.post(
                url,
                { token: $("#inputToken").val() },
                function( data ) {
                    if (data.country_code == iso_country_code)
                    {
                        window.location = 'order?step=2&tokenTaxamo=' + $("#inputToken").val();
                    }
                    else
                    {
                        $("#alertErrorProcess").text(data.errors[0] + data.errors[1]);
                        $("#alertErrorProcess").show();
                        $btn.button('reset');
                        $("#inputToken").focus();
                    }
                },
                "json"
                );
        }
        else
        {
            $("#alertErrorInputPhone").show();
            $btn.button('reset');
            $("#inputToken").focus();
        }
    });
});
</script>
