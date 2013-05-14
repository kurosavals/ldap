<?php

class PluginLdap_ModuleLdap_MapperLdap extends Mapper {


    public function  GetNextSyncUser($sUserLogin) {

        $sql = "
			SELECT user_name
			FROM ".Config::Get('db.table.ad_cron')."
			WHERE user_name=?
		";

        if($this->oDb->query($sql,$sUserLogin)) {
            return true;
        }
        return false;
    }

    public function  GetSyncUsers() {

        $sql = "
			SELECT user_name
			FROM ".Config::Get('db.table.ad_cron')."
			";

        if($aRows=$this->oDb->select($sql)) {
            return $aRows;
        }
        return false;
    }

    public function DeleteUserFromCron($sUserLogin) {
        $sql = "DELETE FROM ".Config::Get('db.table.ad_cron')."
			WHERE
				user_name = ?
		";
        if ($this->oDb->query($sql,$sUserLogin))
        {
            return true;
        }
        return false;
    }


    public function DelaySyncUser($sUserLogin) {

        $sql = "INSERT INTO " . Config::Get('db.table.ad_cron') . "
			(user_name)
			VALUES (?)
		";
        if ($this->oDb->query($sql, $sUserLogin)) {
            return true;
        }
        return false;
    }


    public function setAdmin($iUserId) {

        $sql = "INSERT INTO " . Config::Get('db.table.user_administrator') . "
			(user_id)
			VALUES (?)
		";
        if ($iId = $this->oDb->query($sql, $iUserId)) {
            return $iId;
        }
        return false;
    }

    public function delAdmin($iUserId) {
        $sql = "DELETE FROM " . Config::Get('db.table.user_administrator') . "
        WHERE
        user_id = ?d
        ";
        if ($this->oDb->query($sql, $iUserId)) {
            return true;
        }

        return false;
    }

    public function getAdUserById($iUserId) {

        $sql = "
			SELECT user_id
			FROM ".Config::Get('db.table.user_ad')."
			WHERE user_id=?
		";

        if($this->oDb->query($sql,$iUserId)) {
            return true;
        }
        return false;

    }

    public function addAdUser($iUserId) {

        $sql = "INSERT INTO " . Config::Get('db.table.user_ad') . "
			(user_id)
			VALUES (?)
		";
        if ($iId = $this->oDb->query($sql, $iUserId)) {
            return $iId;
        }
        return false;
    }

    public function delAdUser($iUserId) {
        $sql = "DELETE FROM " . Config::Get('db.table.user_ad') . "
        WHERE
        user_id = ?d
        ";
        if ($this->oDb->query($sql, $iUserId)) {
            return true;
        }

        return false;
    }


}