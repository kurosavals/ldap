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

        $ad = $this->PluginLdap_Ldap_InitializeConnect();

		$sUserLogin = getRequest('login');

		/**
		 * Логин и пароль являются строками?
		 */
		if (!is_string($sUserLogin) or !is_string(getRequest('password'))) {
			$this->Message_AddErrorSingle($this->Lang_Get('system_error'));
			return;
		}
		if ($bUserAuth = $ad->authenticate($sUserLogin, getRequest('password'))) {
            if(!$aResult=$this->PluginLdap_Ldap_Synchronize($ad,$sUserLogin)){
                $this->Message_AddErrorSingle($this->Lang_Get('system_error'));
                return;
            }

            if($aResult['status']===0){
                $this->Message_AddErrorSingle($aResult['data']);

                return;


            }
            /**
             * Определяем редирект
             */
            $sUrl = Config::Get('module.user.redirect_after_login');
            if (getRequestStr('return-path')) {
                $sUrl = getRequestStr('return-path');
            }
            /**
             * Авторизуем
             */
            $bRemember = getRequest('remember', false) ? true : false;
            $this->User_Authorization($aResult['data'], $bRemember);
            $this->Viewer_AssignAjax('sUrlRedirect', $sUrl ? $sUrl : Config::Get('path.root.web'));
            return;


        } else {
           return parent::EventAjaxLogin();


        }













	}

}

?>