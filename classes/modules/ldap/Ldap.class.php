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

    public function InitializeConnect(){
        require_once Plugin::GetPath(__CLASS__) . '/lib/external/adldap/adLDAP.php';

        if (!($ad = new adLDAP(array('base_dn' => Config::Get('plugin.ldap.ad.base_dn'), 'account_suffix' => Config::Get('plugin.ldap.ad.account_suffix'), 'domain_controllers' => Config::Get('plugin.ldap.ad.domain_controllers'), 'admin_username' => Config::Get('plugin.ldap.ad.admin_username'), 'admin_password' => Config::Get('plugin.ldap.ad.admin_password'), 'use_ssl' => Config::Get('plugin.ldap.ad.use_ssl'), 'use_tls' => Config::Get('plugin.ldap.ad.use_tls'), 'ad_port' => Config::Get('plugin.ldap.ad.use_ssl'))))) {
            return $this->Lang_Get('system_error');
        }
        $ad->close();
        $ad->connect();

        return $ad;
    }



}

