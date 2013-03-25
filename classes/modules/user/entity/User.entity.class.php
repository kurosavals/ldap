<?php

class PluginLdap_ModuleUser_EntityUser extends PluginLdap_Inherit_ModuleUser_EntityUser
{

    public function ValidateLoginExists($sValue, $aParams)
    {
        $sError = parent::ValidateLoginExists($sValue, $aParams);
        if (!($sError === true)) {
            return $sError;
        }

        $ad = $this->PluginLdap_Ldap_InitializeConnect();

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


        $ad = $this->PluginLdap_Ldap_InitializeConnect();

        if ($aLdapUser = $ad->user()->info($sValue, array('*'), false, true)) {
            return $this->Lang_Get('registration_mail_error_used');
        }


    }
}