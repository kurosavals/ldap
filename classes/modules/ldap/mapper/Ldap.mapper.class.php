<?php

class PluginLdap_ModuleLdap_MapperLdap extends Mapper {


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

    /*
     * DELETE FROM `ldap`.`prefix_user_administrator` WHERE `prefix_user_administrator`.`user_id` = 8
     */


}