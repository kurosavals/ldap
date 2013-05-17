<?php

class PluginLdap_ActionAdmin extends PluginLdap_Inherit_ActionAdmin
{

    protected function RegisterEvent()
    {
        parent::RegisterEvent();
        $this->AddEventPreg('/^users$/i', '/^(page([1-9]\d{0,5}))?$/i', array('EventUsers', 'users'));
        $this->AddEvent('users', array('EventUsers', 'users'));
        // $this->AddEvent('reloadldapprofiles', 'EventReloadLdapProfiles');
        $this->AddEvent('ajaxldapimport', 'AjaxLdapImport');
        $this->AddEvent('ajaxdelayimport', 'AjaxDelayImport');
    }

    protected function AjaxDelayImport()
    {
        $this->Viewer_SetResponseAjax('json');
        /*
         * Пользователь - администратор?
         */

        if (!$this->oUserCurrent or !$this->oUserCurrent->isAdministrator()) {
            $this->Message_AddErrorSingle($this->Lang_Get('not_access'), $this->Lang_Get('error'));
            return;
        }

        $sUserLogin = getRequestStr('userLogin', null, 'post');

        if ($this->PluginLdap_Ldap_GetNextSyncUser($sUserLogin)) {
            $this->Message_AddErrorSingle($this->Lang_Get('plugin.ldap.delay_user_already_add_tosync'), $this->Lang_Get('error'));
            $this->Viewer_AssignAjax('bState', false);
        } else {
            if ($this->PluginLdap_Ldap_DelaySyncUser($sUserLogin)) {
                $this->Message_AddNoticeSingle($this->Lang_Get('plugin.ldap.delay_sync_is_ok'), $this->Lang_Get('attention'));
                $this->Viewer_AssignAjax('bState', true);
            } else {
                $this->Message_AddNoticeSingle('Get Problem!', $this->Lang_Get('error'));
                $this->Viewer_AssignAjax('bState', false);
            }
        }


    }

    protected function AjaxLdapImport()
    {
        $this->Viewer_SetResponseAjax('json');
        $bAdmin = false;
        $oGeoObject = null;

        /*
         * Пользователь - администратор?
         */

        if (!$this->oUserCurrent or !$this->oUserCurrent->isAdministrator()) {
            $this->Message_AddErrorSingle($this->Lang_Get('not_access'), $this->Lang_Get('error'));
            return;
        }

        $ad = $this->PluginLdap_Ldap_InitializeConnect();

        $sUserLogin = getRequestStr('userLogin', null, 'post');
        if (!$aResult = $this->PluginLdap_Ldap_Synchronize($ad, $sUserLogin)) {
            $this->Message_AddErrorSingle($this->Lang_Get('system_error'), $this->Lang_Get('error'));
            return;
        }

        if ($aResult['status'] === 1) {
            $this->Message_AddNoticeSingle($this->Lang_Get('plugin.ldap.user_import_ok'), $this->Lang_Get('attention'));
            $this->Viewer_AssignAjax('bState', true);
        } else {
            $this->Message_AddErrorSingle($aResult['data'], $this->Lang_Get('attention'));
            $this->Viewer_AssignAjax('bState', false);
        }


    }


    protected function EventUsers()
    {

        $iPage = $this->GetParamEventMatch(0, 2) ? $this->GetParamEventMatch(0, 2) : 1;

        $ad = $this->PluginLdap_Ldap_InitializeConnect();

        $aldapUsers = $ad->user()->all();

        $pages = array_chunk($aldapUsers, Config::Get('module.blog.users_per_page'));
        $aUsers = array();
        $data = array();
        foreach ($pages[$iPage - 1] as $sUser) {
            $data['name'] = $sUser;
            if ($oUser = $this->User_GetUserByLogin($sUser)) {
                $data['is_ad'] = true;
            } else {
                $data['is_ad'] = false;
            }
            $aUsers[] = $data;
        }
        $this->Viewer_Assign('aUsers', $aUsers);
        /*
         * Пагинация пользователей
         */
        $aPaging = $this->Viewer_MakePaging(count($pages), $iPage, 1, Config::Get('pagination.pages.count'), Router::GetPath('admin') . "users/");
        $this->Viewer_Assign('aPaging', $aPaging);

    }


    protected function EventReloadLdapProfiles()
    {
        $bAdmin = false;
        $oGeoObject = null;
        $this->Security_ValidateSendForm();
        set_time_limit(0);
        $this->SetTemplateAction('index');

        $ad = $this->PluginLdap_Ldap_InitializeConnect();

        $aUsers = $ad->user()->all();
        foreach ($aUsers as $user) {
            $aLdapUser = $ad->user()->info($user, array('*'));
            $sNewPassword = md5(func_generator(7));
            if (!$oUser = $this->User_GetUserByLogin($user)) {
                $oUser = Engine::GetEntity('ModuleUser_EntityUser');
                if (!$this->PluginLdap_Ldap_updateBasicProfile($oUser, $aLdapUser)) {
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
            if (!($oUserNew = $this->PluginLdap_Ldap_updateBasicProfile($oUser, $aLdapUser))) {
                // $this->Message_AddErrorSingle($this->Lang_Get('plugin.ldap.ldap_register_ad_error'));
                // return;
                continue;
            }
            if ($oUserNew->getProfileCity() or $oUserNew->getProfileRegion() or $oUserNew->getProfileCountry()) {
                $oGeoObject = $this->PluginLdap_Ldap_updateGeo($oUserNew);
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
}