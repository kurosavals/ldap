<?php

class PluginLdap_ModuleLdap extends Module {

    public function Init() {
        $this->oMapper = Engine::GetMapper(__CLASS__);
    }

    public function setAdmin($iUserId) {

        return $this->oMapper->setAdmin($iUserId);

    }

    public function delAdmin($iUserId) {
        return $this->oMapper->delAdmin($iUserId);
    }

	public function GetGeoName($sType,$sName) {
		$sType=strtolower($sType);
		if (!$this->Geo_IsAllowGeoType($sType)) {
			return null;
		}

		switch($sType) {
			case 'country':
				return $this->GetCountryByName($sName);
				break;
			case 'region':
				return $this->GetRegionByName($sName);
				break;
			case 'city':
				return $this->GetCityByName($sName);
				break;
			default:
				return null;
		}
	}

	public function GetCountryByName($sName) {
		$aRes=$this->Geo_GetCountries(array('name_ru'=>$sName),array(),1,1);
		if (isset($aRes['collection'][0])) {
			return $aRes['collection'][0];
		}
		return null;
	}

	public function GetRegionByName($sName) {
		$aRes=$this->Geo_GetRegions(array('name_ru'=>$sName),array(),1,1);
		if (isset($aRes['collection'][0])) {
			return $aRes['collection'][0];
		}
		return null;
	}

	public function GetCityByName($sName) {
		$aRes=$this->Geo_GetCities(array('name_ru'=>$sName),array(),1,1);
		if (isset($aRes['collection'][0])) {
			return $aRes['collection'][0];
		}
		return null;
	}

    /*
     * классы, связанные с LDAP/AD
     */

    public function GetAdUserById($iUserId) {
        if (false === ($data = $this->Cache_Get("ad_user_{$iUserId}"))) {
            $data = $this->oMapper->GetAdUserById($iUserId);
            $this->Cache_Set($data, "ad_user_{$iUserId}", array("ad_user_{$iUserId}"), 60*60*24*5);
        }
        return $data;
    }

    public function DelAdUser($iUserId) {
        if ($this->oMapper->delAdUser($iUserId)) {
            //чистим зависимые кеши
            $this->Cache_Clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG,array("ad_user_{$iUserId}"));
            return true;
        }
        return false;
    }

    public function AddAdUser($iUserId) {
        if ($sId=$this->oMapper->addAdUser($iUserId)) {
            //чистим зависимые кеши
            $this->Cache_Clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG,array("ad_user_{$iUserId}"));
            return true;
        }
        return false;
    }


    /*
     *
     * ===============================================================================================================
     * Внутренние функции для взаимодействия с adLDAP
     * ===============================================================================================================
     *
     */

     /*
      * Создаем коннект к AD
      */
    public function InitializeConnect(){
        require_once Plugin::GetPath(__CLASS__) . '/lib/external/adldap/adLDAP.php';

        if (!($ad = new adLDAP(array('base_dn' => Config::Get('plugin.ldap.ad.base_dn'), 'account_suffix' => Config::Get('plugin.ldap.ad.account_suffix'), 'domain_controllers' => Config::Get('plugin.ldap.ad.domain_controllers'), 'admin_username' => Config::Get('plugin.ldap.ad.admin_username'), 'admin_password' => Config::Get('plugin.ldap.ad.admin_password'), 'use_ssl' => Config::Get('plugin.ldap.ad.use_ssl'), 'use_tls' => Config::Get('plugin.ldap.ad.use_tls'), 'ad_port' => Config::Get('plugin.ldap.ad.use_ssl'))))) {
            return $this->Lang_Get('system_error');
        }
        $ad->close();
        $ad->connect();

        return $ad;
    }


    /*
     * синхронизируем профиль
     */

    public function updateBasicProfile($oUser, $aLdapUser) {
        $aUpdate = Config::Get('plugin.ldap.profile.basic');
        foreach ($aUpdate as $key => $value) {
            if (!array_key_exists($value, $aLdapUser[0])) {
                $oUser->$key(null);
                continue;
            }
            $oUser->$key($aLdapUser[0][$value][0]);
        }
        return $oUser;
    }


    /*
     * синхронизируем гео-объект
     */
    public function updateGeo($oUser) {

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

	/*
	 * @todo рефакторинг функции. есть возможность упростить код и исправить баги!
	 */
    public function Synchronize($ad,$sUserLogin){
        $aLdapUser = $ad->user()->info($sUserLogin, array('*'));
        $aResult = array();


        $sNewPassword = md5(func_generator(7));
        if (!$oUser = $this->User_GetUserByLogin(getRequest('login'))) {
            $oUser = Engine::GetEntity('ModuleUser_EntityUser');
            if (!$this->PluginLdap_Ldap_updateBasicProfile($oUser, $aLdapUser)) {
	            $aResult['status']=0;
                $aResult['data']=$this->Lang_Get('plugin.ldap.ldap_register_ad_error');
                return $aResult;
            }

            $oUser->setPassword($sNewPassword);
            $oUser->setIpRegister(func_getIp());
            $oUser->setDateRegister(date("Y-m-d H:i:s"));
            $oUser->setActivate(1);
            if(!$oUser->getLogin() or !$oUser->getMail()){
	            $aResult['status']=0;
                $aResult['data']=$this->Lang_Get('plugin.ldap.ldap_register_ad_error');
                return $aResult;
            }
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



        $oGeoObject = false;

        if (!($oUserNew = $this->PluginLdap_Ldap_updateBasicProfile($oUser, $aLdapUser))) {
            $this->Message_AddErrorSingle($this->Lang_Get('plugin.ldap.ldap_register_ad_error'));
            return false;
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
        if (!$this->PluginLdap_Ldap_GetAdUserById($oUser->getId())){
            $this->PluginLdap_Ldap_AddAdUser($oUser->getId());
        }

        $aResult['code']=1;
        $aResult['data']=$oUser;
	    return $aResult;
    }

}

