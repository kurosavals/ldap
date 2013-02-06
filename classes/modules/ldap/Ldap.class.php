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

}

