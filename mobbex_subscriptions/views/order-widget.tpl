<tr>
    <th>
        <h3>Subscription Data</h3>
    </th>
</tr>
<tr>
    <td>Uid:</td>
    <td>{$subscriber['subscription_uid']}</td>
</tr>
<tr class="mobbex-color-column">
    <td>Start Date:</td>
    <td>{$subscriber['start_date']}</td>
</tr>
<tr>
    <td>Last Execution:</td>
    <td>{$subscriber['last_execution']}</td>
</tr>
<tr class="mobbex-color-column">
    <td>Next Execution:</td>
    <td>{$subscriber['next_execution']}</td>
</tr>

<tr>
    <th>
        <h3>Subscriber Data</h3>
    </th>
</tr>
<tr class="mobbex-color-column">
    <td>Uid:</td>
    <td>{$subscriber['uid']}</td>
</tr>
<tr>
    <td>State:</td>
    <td>{$subscriber['state']}</td>
</tr>
<tr class="mobbex-color-column">
    <td>Test:</td>
    <td>{$subscriber['test']}</td>
</tr>
<tr>
    <td>Name:</td>
    <td>{$subscriber['name']}</td>
</tr>
<tr class="mobbex-color-column">
    <td>Email:</td>
    <td>{$subscriber['email']}</td>
</tr>
<tr>
    <td>phone:</td>
    <td>{$subscriber['phone']}</td>
<tr class="mobbex-color-column">
    <td>Identification:</td>
    <td>{$subscriber['identification']}</td>
</tr>
<tr>
    <td>Customer id:</td>
    <td>{$subscriber['customer_id']}</td>
</tr>
<tr class="mobbex-color-column">
    <td>Source Url:</td>
    <td><a href="{$subscriber['source_url']}">VER</a></td>
</tr>
<tr>
    <td>Control Url:</td>
    <td><a href="{$subscriber['control_url']}">VER</a></td>
</tr>

<tr>
    <th>
        <h3>Last Executions</h3>
    </th>
</tr>

{foreach from=$executions item=execution}

    <tr class="mobbex-color-column">
        <td>Uid:</td>
        <td>{$execution['execution']['uid']}</td>
    </tr>
    <tr>
        <td>{l s='Execution Date:' mod='mobbex'}</td>
        <td>{$execution['payment']['created']}</td>
    </tr>
    <tr class="mobbex-color-column">
        <td>Status:</td>
        <td>{$execution['context']['status']}</td>
    </tr>
    <tr>
        <td>Used Source:</td>
        <td>{$execution['execution']['source']}</td>
    </tr>
    <tr class="mobbex-color-column">
        <td>Description:</td>
        <td>{$execution['payment']['description']}</td>
    </tr>

    <tr class="mobbex-end-table">
        {if $execution['payment']['status']['code'] >= 400 && $execution['context']['status'] !== 'retried successfully'} 
            <th><a id="mobbex-retry" href="{$retryUrl}&subscriber={$subscriber['uid']}&subscription={$subscriber['subscription_uid']}&execution={$execution['execution']['uid']}&hash={$hash}&url={$returnUrl}">Retry Execution</a></th>
        {/if}
    </tr>

{/foreach}

{literal}
    <style>
        #mobbex-retry {
            text-align: center;
            padding: 2px 10px;
            border-radius: 5px;
            color: white;
            background-color: #6f00ff;
        }
    </style>
{/literal}
