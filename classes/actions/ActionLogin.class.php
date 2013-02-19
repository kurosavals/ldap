<?php

class PluginLdap_ActionLogin extends PluginLdap_Inherit_ActionLogin {

	protected function RegisterEvent() {
		parent::RegisterEvent();
		// $this->AddEvent('ajax-login','EventAjaxLdapLogin');
	}

	protected function EventReminder() {
		return parent::EventNotFound();
	}

	/*
	 * Ldap авторизация
	 */
	protected function EventAjaxLogin() {
		/**
		 * Устанвливаем формат Ajax ответа
		 */
		$this->Viewer_SetResponseAjax('json');

		/*
		 * Подгружаем библиотеку adLDAP
		 */
		require_once Plugin::GetPath(__CLASS__) . '/lib/external/adldap/adLDAP.php';

		if (!($ad = new adLDAP(array('base_dn' => Config::Get('plugin.ldap.ad.base_dn'), 'account_suffix' => Config::Get('plugin.ldap.ad.account_suffix'), 'domain_controllers' => Config::Get('plugin.ldap.ad.domain_controllers'), 'admin_username' => Config::Get('plugin.ldap.ad.admin_username'), 'admin_password' => Config::Get('plugin.ldap.ad.admin_password'), 'use_ssl' => Config::Get('plugin.ldap.ad.use_ssl'), 'use_tls' => Config::Get('plugin.ldap.ad.use_tls'), 'ad_port' => Config::Get('plugin.ldap.ad.use_ssl'))))) {
			$this->Message_AddErrorSingle($this->Lang_Get('system_error'));
		}

		$ad->close();
		$ad->connect();
		$sUserLogin = getRequest('login');

		/**
		 * Логин и пароль являются строками?
		 */
		if (!is_string($sUserLogin) or !is_string(getRequest('password'))) {
			$this->Message_AddErrorSingle($this->Lang_Get('system_error'));
			return;
		}
		if (!$bUserAuth = $ad->authenticate($sUserLogin, getRequest('password'))) {
			$this->Message_AddErrorSingle($this->Lang_Get('user_login_bad'));
			return;
		}

		$aLdapUser = $ad->user()->info($sUserLogin, array('*'));


		$sNewPassword = md5(func_generator(7));
		if (!$oUser = $this->User_GetUserByLogin(getRequest('login'))) {
			$oUser = Engine::GetEntity('ModuleUser_EntityUser');
			if (!$this->updateBasicProfile($oUser, $aLdapUser)) {
				$this->Message_AddErrorSingle($this->Lang_Get('plugin.ldap.ldap_register_ad_error'));
				return;
			}

			$oUser->setPassword($sNewPassword);
			$oUser->setIpRegister(func_getIp());
			$oUser->setLogin($aLdapUser[0]['samaccountname'][0]);
			$oUser->setMail($aLdapUser[0]['mail'][0]);
			$oUser->setDateRegister(date("Y-m-d H:i:s"));
			$oUser->setActivate(1);
			$this->User_Add($oUser);
		}


		$bAdmin = false;
		foreach (Config::Get('plugin.ldap.security.admin_groups') as $sGroup) {
			if ($ad->user()->inGroup($sUserLogin, $sGroup)) {
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

		$bRemember = getRequest('remember', false) ? true : false;

		if (!($oUserNew = $this->updateBasicProfile($oUser, $aLdapUser))) {
			$this->Message_AddErrorSingle($this->Lang_Get('plugin.ldap.ldap_register_ad_error'));
			return;
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


		/**
		 * Авторизуем
		 */

		$this->User_Authorization($oUser, $bRemember);
		/**
		 * Определяем редирект
		 */
		$sUrl = Config::Get('module.user.redirect_after_login');
		if (getRequestStr('return-path')) {
			$sUrl = getRequestStr('return-path');
		}
		$this->Viewer_AssignAjax('sUrlRedirect', $sUrl ? $sUrl : Config::Get('path.root.web'));
		return;


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

?>