{include file='header.tpl' noShowSystemMessage=false}
{*
<script language="JavaScript" type="text/javascript">
    var DIR_PLUGIN_SKIN='{$aTemplateWebPathPlugin.ldap}';
</script>
*}
<script type="text/javascript" src="{$aTemplateWebPathPlugin.ldap}js/ldap.js"></script>

<h2 class="page-header">{$aLang.plugin.ldap.users_import}</h2>

<br/>
<table class="table table-blogs" cellspacing="0">
    <thead>
    <tr>
        {* // пока отключил. Функция не написана
        <th class="cell-checkbox" width="15px"><input type="checkbox" name=""
                                         onclick="ls.tools.checkAll('form_users', this, true);"/></th>
*}
        <th>{$aLang.plugin.ldap.ldap_username}</th>
        <th>{$aLang.plugin.ldap.ldap_action}</th>
    </tr>
    </thead>
    <tbody>
    {if $aUsers}
        {foreach from=$aUsers item=aUser}
            <tr>
                {*
                <td class="cell-checkbox"><input type="checkbox" name="user_{$aUser.name}" class="form_users" /></td>
*}
                <td class="cell-name">
                        <span class="user-avatar">
							{$aUser.name}
						</span>
                </td>
                <td class="cell-readers"> {if !$aUser.is_ad}<a class="button button-primary"
                                                                  onclick="ls.ldap.import(this,'{$aUser.name}'); return false;">{$aLang.plugin.ldap.synchronize}</a>
                </td>{/if}
            </tr>
        {/foreach}
    {/if}
    </tbody>
</table>
{*
<a class="button">Синхронизировать выделенных</a>
<a class="button">Синхронизировать все</a>
*}
{include file='paging.tpl' aPaging="$aPaging"}
{include file='footer.tpl'}