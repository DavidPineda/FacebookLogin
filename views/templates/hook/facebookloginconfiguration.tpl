{if isset($SaveOk)}
    <div class="alert alert-success">
        {l s='Settings Successfully Saved' mod='facebooklogin'}
    </div>
{/if}
<fieldset>
    <h2>{l s='Configuration Facebook Module Connect' mod='facebooklogin'}</h2>
    <br/>
    <div class="panel">
        <div class="panel-heading">
            <legend>
                {l s='Configuration' mod='facebooklogin'}
            </legend>
        </div>
        <form action="" method="POST">
            <div class="form-group clearfix">
                <div class="col-lg-9">
                    <label for="FbAppId">{l s='Facebook App Id' mod='facebooklogin'}</label>
                    <input type="text" id="FbAppId" name="FbAppId" class="form-control" {if not empty($appId)} value="{$appId}" {/if} >
                </div>
            </div>
            <div class="panel-footer">
                <input class="btn btn-default pull-right" type="submit" id="btnConfigAppFacebookConnect" name="btnConfigAppFacebookConnect" value="{l s='Save'}"/>
            </div>                    
        </form>
    </div>
</fieldset>