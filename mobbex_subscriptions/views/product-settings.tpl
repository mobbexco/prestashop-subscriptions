<div class="form-group align-items-center" style="margin-top: 2em;">
    <h2 class="mr-3" style="display: inline-block;">Modalidad de Suscripción</h2>
    <span class="{if constant('_PS_VERSION_') >= '1.7'}switch prestashop-switch{/if} fixed-width-lg">
        <input type="hidden" name="subscription_mode" value="no">
        <input type="checkbox" data-toggle="switch" id="subscription_mode" name="subscription_mode" value="yes" {if $subscription_mode}checked{/if}>
    <span>
</div>
<div class="row form-group subscription_field {if !$subscription_mode}hidden{/if}">
    <div class="col-md-12">
        <label for="charge_interval">Cobrar cada...</label>
    </div>
    <div class="col-md-3">
        <fieldset>
            <select id="charge_interval" name="charge_interval" class="custom-select">
                <option value="1" {if $charge_interval == '1'}selected{/if}>1</option>
                <option value="2" {if $charge_interval == '2'}selected{/if}>2</option>
                <option value="3" {if $charge_interval == '3'}selected{/if}>3</option>
                <option value="6" {if $charge_interval == '6'}selected{/if}>6</option>
                <option value="7" {if $charge_interval == '7'}selected{/if}>7</option>
                <option value="15" {if $charge_interval == '15'}selected{/if}>15</option>
            </select>
        </fieldset>
    </div>
    <div class="col-md-3">
        <fieldset>
            <select id="charge_period" name="charge_period" class="custom-select">
                <option value="d" {if $charge_period == 'd'}selected{/if}>días</option>
                <option value="m" {if $charge_period == 'm'}selected{/if}>meses</option>
                <option value="y" {if $charge_period == 'y'}selected{/if}>años</option>
            </select>
        </fieldset>
    </div>
</div>
<div class="row form-group subscription_field {if !$subscription_mode}hidden{/if}">
    <div class="col-md-3">
        <label for="free_trial" class="control-label">
            <span class="label-tooltip" data-toggle="tooltip" data-original-title="Cantidad de períodos durante los cuales la suscripción será gratuita.">Períodos de prueba</span>
        </label>
        <span class="help-box" data-toggle="popover" data-content="Cantidad de períodos durante los cuales la suscripción será gratuita."></span>
        <input type="text" id="free_trial" name="free_trial" class="form-control" value="{$free_trial}" placeholder="0">
    </div>
    <div class="col-md-3">
        <label for="signup_fee" class="control-label">
            <span class="label-tooltip" data-toggle="tooltip" data-original-title="Tarifa extra cobrada al inicio de la suscripción.">Tarifa de registro</span>
        </label>
        <span class="help-box" data-toggle="popover" data-content="Tarifa extra cobrada al inicio de la suscripción."></span>
        <input type="text" id="signup_fee" name="signup_fee" class="form-control" value="{$signup_fee}" placeholder="0">
    </div>
</div>

{literal}
<style>
    .hidden {
        display: none;
    }

    .subscription_field {
        max-width: 850px;
    }
</style>
<script>
    function mbbxsRenderOptions(optionValues, select) {
        // Clear current options
        var selectValue = select.value;
        select.innerHTML = '';

        // Add new options
        for (var value of optionValues) {
            var option = document.createElement('option');
            option.text = value;
            option.value = value;

            if (value == selectValue) {
                option.selected = true;
            }

            select.add(option);
        }
    }

    $(document).ready(function() {
        $('#subscription_mode').on('change', () => $('.subscription_field').toggleClass('hidden'));

    // Get charge interval and period
    var chargeInterval = document.querySelector('#charge_interval');
    var chargePeriod   = document.querySelector('#charge_period');

    // This select only exists when the subscription type is dynamic
    if (chargeInterval) {
        // The intervals in dynamic subscriptions are limited depending on the period
        var intervals = {
            d: [7, 15],
            m: [1, 2, 3, 6],
            y: [1],
        };

        var periodIntervals = intervals[chargePeriod.value];
        mbbxsRenderOptions(periodIntervals, chargeInterval);
        chargePeriod.onchange = function () {
            // Render intervals of selected period
            var periodIntervals = intervals[chargePeriod.value];
            mbbxsRenderOptions(periodIntervals, chargeInterval);
        }
    }
    });
</script>
{/literal}