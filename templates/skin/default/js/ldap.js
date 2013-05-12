var ls = ls || {};

ls.ldap = (function ($) {

    this.import = function(obj,userLogin) {
        var url = aRouter['admin']+'ajaxldapimport/';
        var params = {userLogin: userLogin};
        ls.ajax(url,params,function(result) {
            if (result.bStateError) {
                ls.msg.error(null, result.sMsg);
            } else {
                obj = $(obj);
                ls.msg.notice(null, result.sMsg);
                obj.remove();
            }
        });
    };

    return this;
}).call(ls.profiler || {},jQuery);