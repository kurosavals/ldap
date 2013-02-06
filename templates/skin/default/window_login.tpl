{if !$oUserCurrent}
<div class="modal modal-login" id="window_login_form">
    <header class="modal-header">
        <a href="#" class="close jqmClose"></a>
    </header>


    <script type="text/javascript">
        jQuery(function ($) {
            $('#popup-login-form').bind('submit', function () {
                ls.user.login('popup-login-form');
                return false;
            });
            $('#popup-login-form-submit').attr('disabled', false);
        });
    </script>

    <div class="modal-content">
        <ul class="nav nav-pills nav-pills-tabs">
            <li class="active js-block-popup-login-item" data-type="login"><a href="#">{$aLang.user_login_submit}</a>
            </li>
        </ul>


        <div class="tab-content js-block-popup-login-content" data-type="login">
            {hook run='login_popup_begin'}
            <form action="{router page='login'}" method="post" id="popup-login-form">
                {hook run='form_login_popup_begin'}

                <p><input type="text" name="login" id="popup-login" placeholder="{$aLang.user_login}"
                          class="input-text input-width-full"></p>

                <p><input type="password" name="password" id="popup-password" placeholder="{$aLang.user_password}"
                          class="input-text input-width-300" style="width: 322px">
                    <button type="submit" name="submit_login" class="button button-primary" id="popup-login-form-submit"
                            disabled="disabled">{$aLang.user_login_submit}</button>
                </p>

                <label class="remember-label"><input type="checkbox" name="remember" class="input-checkbox"
                                                     checked/> {$aLang.user_login_remember}</label>

                <small class="validate-error-hide validate-error-login"></small>
                {hook run='form_login_popup_end'}

                <input type="hidden" name="return-path" value="{$PATH_WEB_CURRENT|escape:'html'}">
            </form>
            {hook run='login_popup_end'}
        </div>


    </div>
</div>
{/if}