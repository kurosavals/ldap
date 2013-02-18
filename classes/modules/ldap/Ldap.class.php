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

}

