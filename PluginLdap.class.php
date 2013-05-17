<?php

/**
 * Запрещаем напрямую через браузер обращение к этому файлу.
 */
if (!class_exists('Plugin')) {
    die('Hacking attemp!');
}

class PluginLdap extends Plugin {

    protected $aInherits = array(
        'action' => array('ActionLogin','ActionAdmin'),
        'entity' => array('ModuleUser_EntityUser'),

    );
    /*
    protected $aDelegates = array(
        'template' => array('window_login.tpl')
    );
    */

    /**
     * Plugin Ldap activation
     */
    public function Activate() {
        //$this->ExportSQL(dirname(__FILE__) . '/db.sql');
        return true;
    }

    /**
     * Init plugin LDAP
     */
    public function Init() {

    }

}

?>
