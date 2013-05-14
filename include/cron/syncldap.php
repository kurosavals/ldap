<?php

$sDirRoot=dirname(dirname(dirname(dirname(dirname(__FILE__)))));
set_include_path(get_include_path().PATH_SEPARATOR.$sDirRoot);
chdir($sDirRoot);

require_once($sDirRoot."/config/loader.php");
require_once($sDirRoot."/engine/classes/Cron.class.php");

class LdapCron extends Cron {

    public function Client(){
        $aUsers=$this->PluginLdap_Ldap_GetSyncUsers();

        if(empty($aUsers)){
            $this->Log('No Users for Sync');
        }


        foreach($aUsers as $aUserLogin){

            $ad = $this->PluginLdap_Ldap_InitializeConnect();
            if (!$aResult = $this->PluginLdap_Ldap_Synchronize($ad, $aUserLogin['user_name'])) {
                $this->Log("User: ".$aUserLogin['user_name']. " - System error!");
            }

            if ($aResult['status'] === 1) {
                $this->Log("User: ".$aUserLogin['user_name']. " - Import done!");
                $this->PluginLdap_Ldap_DeleteUserFromCron($aUserLogin['user_name']);
            } else {
                $this->Log("User: ".$aUserLogin['user_name']. " - Get Problem: ".$aResult['data']);

            }

        }
    }

}

$sLockFilePath=Config::Get('sys.cache.dir').'ldap.lock';
/**
 * Создаем объект крон-процесса,
 * передавая параметром путь к лок-файлу
 */
$app=new LdapCron($sLockFilePath);
print $app->Exec();