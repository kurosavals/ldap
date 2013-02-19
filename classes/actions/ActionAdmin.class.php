<?php

class PluginLdap_ActionAdmin extends PluginLdap_Inherit_ActionAdmin {

    protected function RegisterEvent() {
        parent::RegisterEvent();
        $this->AddEvent('reloadldapprofiles', 'EventReloadLdapProfiles');
    }

    protected function EventReloadLdapProfiles() {
        $bAdmin = false;
        $oGeoObject = null;
        $this->Security_ValidateSendForm();
        set_time_limit(0);
        $this->SetTemplateAction('index');

        require_once Plugin::GetPath(__CLASS__) . '/lib/external/adldap/adLDAP.php';

        if (!($ad = new adLDAP(array('base_dn' => Config::Get('plugin.ldap.ad.base_dn'), 'account_suffix' => Config::Get('plugin.ldap.ad.account_suffix'), 'domain_controllers' => Config::Get('plugin.ldap.ad.domain_controllers'), 'admin_username' => Config::Get('plugin.ldap.ad.admin_username'), 'admin_password' => Config::Get('plugin.ldap.ad.admin_password'), 'use_ssl' => Config::Get('plugin.ldap.ad.use_ssl'), 'use_tls' => Config::Get('plugin.ldap.ad.use_tls'), 'ad_port' => Config::Get('plugin.ldap.ad.use_ssl'))))) {
            $this->Message_AddError($this->Lang_Get('system_error'));
        }

        $ad->close();
        $ad->connect();

        $aUsers = $ad->user()->all();
        foreach ($aUsers as $user) {
            $aLdapUser = $ad->user()->info($user, array('*'));
            $sNewPassword = md5(func_generator(7));
            if (!$oUser = $this->User_GetUserByLogin($user)) {
                $oUser = Engine::GetEntity('ModuleUser_EntityUser');
                if (!$this->updateBasicProfile($oUser, $aLdapUser)) {
                    //$this->Message_AddError($this->Lang_Get('plugin.ldap.ldap_register_ad_error'));
                    //return;
                    continue;
                }

                $oUser->setPassword($sNewPassword);
                $oUser->setIpRegister(func_getIp());
                $oUser->setLogin($aLdapUser[0]['samaccountname'][0]);
                $oUser->setMail($aLdapUser[0]['mail'][0]);
                $oUser->setDateRegister(date("Y-m-d H:i:s"));
                $oUser->setActivate(1);
                $this->User_Add($oUser);
            }

            foreach (Config::Get('plugin.ldap.security.admin_groups') as $sGroup) {
                if ($ad->user()->inGroup($user, $sGroup)) {
                    $bAdmin = true;
                }

            }
            if ($bAdmin) {
                if (!$oUser->isAdministrator()) {
                    $this->PluginLdap_Ldap_setAdmin($oUser->getId());
                }
            } else {
                $this->PluginLdap_Ldap_delAdmin($oUser->getId());
            }

            $aType = array('contact', 'social');
            $aFields = $this->User_getUserFields($aType);

            $aProf = Config::Get('plugin.ldap.profile.userfield');
            $aUserFields = array();
            foreach ($aProf as $key => $value) {
                if ($aFieldId = $this->User_userFieldExistsByName($key) and isset($aFieldId)) {
                    $aUserFields[$aFieldId[0]['id']] = $value;
                }
            }

            /**
             * Удаляем все поля с этим типом
             */
            $this->User_DeleteUserFieldValues($oUser->getId(), $aType);

            $aFieldsContactType = array_keys($aUserFields);
            $aFieldsContactValue = array_values($aUserFields);
            if (is_array($aFieldsContactType)) {
                foreach ($aFieldsContactType as $k => $v) {
                    $v = (string)$v;
                    if (isset($aFields[$v]) and isset($aFieldsContactValue[$k]) and is_string($aFieldsContactValue[$k]) and isset($aLdapUser[0][$aFieldsContactValue[$k]][0])) {
                        $this->User_setUserFieldsValues($oUser->getId(), array($v => $aLdapUser[0][$aFieldsContactValue[$k]][0]), Config::Get('module.user.userfield_max_identical'));
                    }
                }
            }
            if (!($oUserNew = $this->updateBasicProfile($oUser, $aLdapUser))) {
               // $this->Message_AddErrorSingle($this->Lang_Get('plugin.ldap.ldap_register_ad_error'));
               // return;
                continue;
            }
            if ($oUserNew->getProfileCity() or $oUserNew->getProfileRegion() or $oUserNew->getProfileCountry()) {
                $oGeoObject = $this->updateGeo($oUserNew);
            }


            if ($oGeoObject) {
                $this->Geo_CreateTarget($oGeoObject, 'user', $oUserNew->getId());

                if ($oCountry = $oGeoObject->getCountry()) {
                    $oUserNew->setProfileCountry($oCountry->getName());
                } else {
                    $oUserNew->setProfileCountry(null);
                }
                if ($oRegion = $oGeoObject->getRegion()) {
                    $oUserNew->setProfileRegion($oRegion->getName());
                } else {
                    $oUserNew->setProfileRegion(null);
                }
                if ($oCity = $oGeoObject->getCity()) {
                    $oUserNew->setProfileCity($oCity->getName());
                } else {
                    $oUserNew->setProfileCity(null);
                }
            } else {
                $this->Geo_DeleteTargetsByTarget('user', $oUserNew->getId());
                $oUserNew->setProfileCountry(null);
                $oUserNew->setProfileRegion(null);
                $oUserNew->setProfileCity(null);
            }
            $this->User_Update($oUserNew);


        }


        $this->Message_AddNotice($this->Lang_Get('plugin.ldap.reimport_ldap_users_ok'), $this->Lang_Get('attention'));

    }

    protected function updateBasicProfile($oUser, $aLdapUser) {
        $aUpdate = Config::Get('plugin.ldap.profile.basic');
        foreach ($aUpdate as $key => $value) {
            if (!array_key_exists($value, $aLdapUser[0])) {
                return false;
            }
            $oUser->$key($aLdapUser[0][$value][0]);
        }
        return $oUser;
    }

    protected function updateGeo($oUser) {

        if ($oUser->getProfileCity()) {
            if ($oGeoObject = $this->PluginLdap_Ldap_GetGeoName('city', $oUser->getProfileCity())) {
                return $oGeoObject;
            }
        }

        if ($oUser->getProfileRegion()) {
            if ($oGeoObject = $this->PluginLdap_Ldap_GetGeoName('region', $oUser->getProfileRegion())) {
                return $oGeoObject;
            }
        }

        if ($oUser->getProfileCountry()) {
            if ($oGeoObject = PluginLdap_Ldap_GetGeoName('country', $oUser->getProfileCountry())) {
                return $oGeoObject;
            }
        }

        return null;
    }
}