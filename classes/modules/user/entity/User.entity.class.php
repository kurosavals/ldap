<?php

class PluginLdap_ModuleUser_EntityUser extends PluginLdap_Inherit_ModuleUser_EntityUser
{

    public function ValidateLoginExists($sValue, $aParams)
    {
        $sError = parent::ValidateLoginExists($sValue, $aParams);
        if (!($sError === true)) {
            return $sError;
        }

        require_once Plugin::GetPath(__CLASS__) . '/lib/external/adldap/adLDAP.php';

        if (!($ad = new adLDAP(array('base_dn' => Config::Get('plugin.ldap.ad.base_dn'), 'account_suffix' => Config::Get('plugin.ldap.ad.account_suffix'), 'domain_controllers' => Config::Get('plugin.ldap.ad.domain_controllers'), 'admin_username' => Config::Get('plugin.ldap.ad.admin_username'), 'admin_password' => Config::Get('plugin.ldap.ad.admin_password'), 'use_ssl' => Config::Get('plugin.ldap.ad.use_ssl'), 'use_tls' => Config::Get('plugin.ldap.ad.use_tls'), 'ad_port' => Config::Get('plugin.ldap.ad.use_ssl'))))) {
            return $this->Lang_Get('system_error');
        }
        $ad->close();
        $ad->connect();

        if ($aLdapUser = $ad->user()->info($sValue, array('*'))) {
            return $this->Lang_Get('registration_login_error_used');
        }

    }

    public function ValidateMailExists($sValue, $aParams)
    {
        $sError = parent::ValidateMailExists($sValue, $aParams);

        if (!($sError === true)) {
            return $sError;
        }

        require_once Plugin::GetPath(__CLASS__) . '/lib/external/adldap/adLDAP.php';

        if (!($ad = new adLDAP(array('base_dn' => Config::Get('plugin.ldap.ad.base_dn'), 'account_suffix' => Config::Get('plugin.ldap.ad.account_suffix'), 'domain_controllers' => Config::Get('plugin.ldap.ad.domain_controllers'), 'admin_username' => Config::Get('plugin.ldap.ad.admin_username'), 'admin_password' => Config::Get('plugin.ldap.ad.admin_password'), 'use_ssl' => Config::Get('plugin.ldap.ad.use_ssl'), 'use_tls' => Config::Get('plugin.ldap.ad.use_tls'), 'ad_port' => Config::Get('plugin.ldap.ad.use_ssl'))))) {
            return $this->Lang_Get('system_error');
        }
        $ad->close();
        $ad->connect();

        if ($aLdapUser = $ad->user()->info($sValue, array('*'), false, true)) {
            return $this->Lang_Get('registration_mail_error_used');
        }


    }
}