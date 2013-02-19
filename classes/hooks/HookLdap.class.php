<?php


class PluginLdap_HookLdap extends Hook {

    public function RegisterHook() {

        if ($oUserCurrent=$this->User_GetUserCurrent() and $oUserCurrent->isAdministrator()) {
            $this->AddHook('template_admin_action_item', 'LdapProfiles');
        }
    }

    /**
     * Выводим HTML
     *
     */
    public function LdapProfiles() {
        return $this->Viewer_Fetch(Plugin::GetTemplatePath(__CLASS__).'ldap.tpl');
    }
}
?>