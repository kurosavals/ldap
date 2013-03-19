{include file='header.tpl' noShowSystemMessage=false}
{*
<script language="JavaScript" type="text/javascript">
    var DIR_PLUGIN_SKIN='{$aTemplateWebPathPlugin.ldap}';
</script>
*}
<script type="text/javascript" src="{$aTemplateWebPathPlugin.ldap}js/ldap.js"></script>

<h2 class="page-header">{$aLang.plugin.ldap.users_import}</h2>

{include file='paging.tpl' aPaging="$aPaging"}

<table class="table table-blogs" cellspacing="0">
    <tbody>
        {if $aUsers}
            {foreach from=$aUsers item=aUser}
                <tr>
                    <td class="cell-name">
                        <span class="user-avatar">
							{$aUser['name']}
						</span>
                    </td>
                   <td class="cell-readers">{if !$aUser['is_ad']}<a class="button button-primary" onclick="ls.ldap.import(this,'{$aUser['name']}'); return false;">{$aLang.plugin.ldap.synchronize}</a> </td>{/if}
                </tr>
            {/foreach}
        {/if}
    </tbody>
</table>

{include file='paging.tpl' aPaging="$aPaging"}
{include file='footer.tpl'}