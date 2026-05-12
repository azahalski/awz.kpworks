!function(n){"use strict";function d(n,t){var r=(65535&n)+(65535&t);return(n>>16)+(t>>16)+(r>>16)<<16|65535&r}function f(n,t,r,e,o,u){return d((u=d(d(t,n),d(e,u)))<<o|u>>>32-o,r)}function l(n,t,r,e,o,u,c){return f(t&r|~t&e,n,t,o,u,c)}function g(n,t,r,e,o,u,c){return f(t&e|r&~e,n,t,o,u,c)}function v(n,t,r,e,o,u,c){return f(t^r^e,n,t,o,u,c)}function m(n,t,r,e,o,u,c){return f(r^(t|~e),n,t,o,u,c)}function c(n,t){var r,e,o,u;n[t>>5]|=128<<t%32,n[14+(t+64>>>9<<4)]=t;for(var c=1732584193,f=-271733879,i=-1732584194,a=271733878,h=0;h<n.length;h+=16)c=l(r=c,e=f,o=i,u=a,n[h],7,-680876936),a=l(a,c,f,i,n[h+1],12,-389564586),i=l(i,a,c,f,n[h+2],17,606105819),f=l(f,i,a,c,n[h+3],22,-1044525330),c=l(c,f,i,a,n[h+4],7,-176418897),a=l(a,c,f,i,n[h+5],12,1200080426),i=l(i,a,c,f,n[h+6],17,-1473231341),f=l(f,i,a,c,n[h+7],22,-45705983),c=l(c,f,i,a,n[h+8],7,1770035416),a=l(a,c,f,i,n[h+9],12,-1958414417),i=l(i,a,c,f,n[h+10],17,-42063),f=l(f,i,a,c,n[h+11],22,-1990404162),c=l(c,f,i,a,n[h+12],7,1804603682),a=l(a,c,f,i,n[h+13],12,-40341101),i=l(i,a,c,f,n[h+14],17,-1502002290),c=g(c,f=l(f,i,a,c,n[h+15],22,1236535329),i,a,n[h+1],5,-165796510),a=g(a,c,f,i,n[h+6],9,-1069501632),i=g(i,a,c,f,n[h+11],14,643717713),f=g(f,i,a,c,n[h],20,-373897302),c=g(c,f,i,a,n[h+5],5,-701558691),a=g(a,c,f,i,n[h+10],9,38016083),i=g(i,a,c,f,n[h+15],14,-660478335),f=g(f,i,a,c,n[h+4],20,-405537848),c=g(c,f,i,a,n[h+9],5,568446438),a=g(a,c,f,i,n[h+14],9,-1019803690),i=g(i,a,c,f,n[h+3],14,-187363961),f=g(f,i,a,c,n[h+8],20,1163531501),c=g(c,f,i,a,n[h+13],5,-1444681467),a=g(a,c,f,i,n[h+2],9,-51403784),i=g(i,a,c,f,n[h+7],14,1735328473),c=v(c,f=g(f,i,a,c,n[h+12],20,-1926607734),i,a,n[h+5],4,-378558),a=v(a,c,f,i,n[h+8],11,-2022574463),i=v(i,a,c,f,n[h+11],16,1839030562),f=v(f,i,a,c,n[h+14],23,-35309556),c=v(c,f,i,a,n[h+1],4,-1530992060),a=v(a,c,f,i,n[h+4],11,1272893353),i=v(i,a,c,f,n[h+7],16,-155497632),f=v(f,i,a,c,n[h+10],23,-1094730640),c=v(c,f,i,a,n[h+13],4,681279174),a=v(a,c,f,i,n[h],11,-358537222),i=v(i,a,c,f,n[h+3],16,-722521979),f=v(f,i,a,c,n[h+6],23,76029189),c=v(c,f,i,a,n[h+9],4,-640364487),a=v(a,c,f,i,n[h+12],11,-421815835),i=v(i,a,c,f,n[h+15],16,530742520),c=m(c,f=v(f,i,a,c,n[h+2],23,-995338651),i,a,n[h],6,-198630844),a=m(a,c,f,i,n[h+7],10,1126891415),i=m(i,a,c,f,n[h+14],15,-1416354905),f=m(f,i,a,c,n[h+5],21,-57434055),c=m(c,f,i,a,n[h+12],6,1700485571),a=m(a,c,f,i,n[h+3],10,-1894986606),i=m(i,a,c,f,n[h+10],15,-1051523),f=m(f,i,a,c,n[h+1],21,-2054922799),c=m(c,f,i,a,n[h+8],6,1873313359),a=m(a,c,f,i,n[h+15],10,-30611744),i=m(i,a,c,f,n[h+6],15,-1560198380),f=m(f,i,a,c,n[h+13],21,1309151649),c=m(c,f,i,a,n[h+4],6,-145523070),a=m(a,c,f,i,n[h+11],10,-1120210379),i=m(i,a,c,f,n[h+2],15,718787259),f=m(f,i,a,c,n[h+9],21,-343485551),c=d(c,r),f=d(f,e),i=d(i,o),a=d(a,u);return[c,f,i,a]}function i(n){for(var t="",r=32*n.length,e=0;e<r;e+=8)t+=String.fromCharCode(n[e>>5]>>>e%32&255);return t}function a(n){var t=[];for(t[(n.length>>2)-1]=void 0,e=0;e<t.length;e+=1)t[e]=0;for(var r=8*n.length,e=0;e<r;e+=8)t[e>>5]|=(255&n.charCodeAt(e/8))<<e%32;return t}function e(n){for(var t,r="0123456789abcdef",e="",o=0;o<n.length;o+=1)t=n.charCodeAt(o),e+=r.charAt(t>>>4&15)+r.charAt(15&t);return e}function r(n){return unescape(encodeURIComponent(n))}function o(n){return i(c(a(n=r(n)),8*n.length))}function u(n,t){return function(n,t){var r,e=a(n),o=[],u=[];for(o[15]=u[15]=void 0,16<e.length&&(e=c(e,8*n.length)),r=0;r<16;r+=1)o[r]=909522486^e[r],u[r]=1549556828^e[r];return t=c(o.concat(a(t)),512+8*t.length),i(c(u.concat(t),640))}(r(n),r(t))}function t(n,t,r){return t?r?u(t,n):e(u(t,n)):r?o(n):e(o(n))}"function"==typeof define&&define.amd?define(function(){return t}):"object"==typeof module&&module.exports?module.exports=t:n.md5=t}(this);
//# sourceMappingURL=md5.min.js.map
function AwzBx24PageManager(options){
    if(!options) options = {};
    if(typeof options !== 'object') {
        throw new Error('options is not object');
    }
    this.init(options);
};
AwzBx24PageManager.prototype = {
    init: function(options){
        this.initHandlers();
        window.AwzBx24PageManager_ob = this;
        return this;
    },
    hidePages: function(){
        $('.appWrap').hide();
        $('.result-block-messages').html('');
    },
    showPage: function(page){
        $('.appWrap').hide();
        var pageExists = false;
        $('.appWrap').each(function(){
            if($(this).attr('data-page') == page){
                $(this).show();
                pageExists = true;
            }
        });
        if(!pageExists){
            $('.appWrap').eq(0).show();
        }
        $('.result-block-messages').html('');
    },
    getPageEl: function(page){
        var el = null;
        $('.appWrap').each(function(){
            if($(this).attr('data-page') == page){
                el = $(this);
            }
        });
        return el;
    },
    scrollTop: function(){
        $('html').scrollTop(0);
        try{
            BX24.scrollParentWindow(0);
        }catch (e) {
        }
    },
    showMessage: function(msg, el){
        if(!el) el = $('.result-block-messages');
        el.html(msg);
        this.scrollTop();
    },
    initHandlers: function(){

        $(document).on('click', '.ui-block-title-actions-show-hide', function(e){
            e.preventDefault();
            var parent = $(this).parents('.ui-block-wrapper');
            if(parent.find('.ui-block-content').hasClass('active')){
                parent.find('.ui-block-content').removeClass('active');
                $(this).html('Развернуть');
            }else{
                parent.find('.ui-block-content').addClass('active');
                $(this).html('Свернуть');
            }
        });
        $(document).on('click','.awz-handler-slide',function(e){
            if(!!e) e.preventDefault();
            var data = $(this).data();
            var url = $(this).attr('href');
            //console.log(data);
            if($(this).attr('data-page')){
                url = '/bitrix/admin/awz_kpworks_'+$(this).attr('data-page')+'.php';
            }
            BX.SidePanel.Instance.open(
                url,
                {
                    requestMethod: 'post',
                    requestParams: data,
                    cacheable: false,
                    events: {
                        'onClose':function(){
                            window.AwzAppInstance.loadWorkRight();
                        }
                    }
                },

            );
            window.AwzBx24PageManager_ob.scrollTop();
        });
        $(document).on('click','.awz-handler-slide-content',function(e){
            if(!!e) e.preventDefault();
            window.AwzBx24PageManager_ob.query_data = $(this).data();
            window.AwzBx24PageManager_ob.query_data['html'] = 'Y';
            window.AwzBx24PageManager_ob.url = '/bitrix/modules/awz.kpworks/admin/'+$(this).attr('data-page')+'.php';
            BX.SidePanel.Instance.open(
                md5(window.AwzBx24PageManager_ob.url),
                {
                    contentCallback: function(slider){
                        return new Promise((resolve, reject) => {
                            $.ajax({
                                url: window.AwzBx24PageManager_ob.url,
                                data: window.AwzBx24PageManager_ob.query_data,
                                dataType : "html",
                                type: "POST",
                                CORS: false,
                                crossDomain: true,
                                timeout: 180000,
                                async: false,
                                success: function (data, textStatus){
                                    window.AwzBx24PageManager_ob.scrollTop();
                                    resolve({ html: '<div class="slide_side_slider"><div class="workarea">'+data+'</div></div>' });
                                },
                                error: function (err){
                                    window.AwzBx24PageManager_ob.scrollTop();
                                    resolve({
                                        html: '<div class="slide_side_slider"><div class="workarea">Ошибка загрузки страницы с сервиса приложения</div></div>',
                                        message: 'app error',
                                        type: 'error'
                                    });
                                }
                            });
                        });
                    },
                    cacheable: false
                }
            );
        });
    },
    loadLinks: function(moduleId){

        $('#help_links').html('');

        $.ajax({
            url: 'https://api.zahalski.dev/bitrix/services/main/ajax.php',
            data: {
                'action':'awz:bxorm.api.hook.call',
                'app':'1','key':'public',
                'method':'pub.help',
                'moduleId':moduleId
            },
            dataType : "json",
            type: "GET",
            CORS: false,
            crossDomain: true,
            timeout: 20,
            async: false,
            success: function (data, textStatus){
                let k;
                for(k in data['data']['result']){
                    let itm = data['data']['result'][k];
                    let ht = '<div class="col col-12 mb-2">' +
                        '<div class="row">' +
                        '<div class="col col-12"><a target="_blank" href="'+itm.link+'">'+itm.name+'</a>' +
                        '</div>'+
                        '</div>'+
                        '</div>';
                    $('#help_links').append(ht);
                }
            },
            error: function (err){
                alert('ошибка получения документации');
            }
        });

    }
};

(function() {
    'use strict';

    if (!!window.AwzApp) {
        return;
    }

    window.AwzApp = function(options) {
        if(typeof options !== 'object') {
            throw new Error('options is not object');
        }
        if(!options.hasOwnProperty('endpointUrl')) {
            throw new Error('options.endpointUrl is required');
        }
        this.init(options);
    };
    window.AwzApp.prototype = {
        init: function (options) {
            this.endpointUrl = options.endpointUrl;
            this.initHandlers(!!options.noHandlers);
            $(document).ready(function(){
                var pagesManager = new AwzBx24PageManager({});
            });
            this.loadStats();
        },
        initHandlers: function(noHandlers){
            var parent = this;
            if(!noHandlers) parent.checkHandlers();
            if(!noHandlers) parent.loadWorkRight();
            $(document).on('change', '#preset', function(e){
                e.preventDefault();
                if($(this).val()){
                    $(this).closest('.container').find('.row-no-preset').hide();
                }else{
                    $(this).closest('.container').find('.row-no-preset').show();
                }
            });
            $(document).on('click', '#external-events-add', function(e){
                e.preventDefault();
                var signed = $('#signed_add').val();

                $.ajax({
                    url: parent.endpointUrl+'addchandler',
                    data: {
                        signed: signed,
                        domain: $('#domain').val(),
                        app: $('#app').val()
                    },
                    dataType : "json",
                    type: "POST",
                    success: function (data, textStatus){
                        if(window.awz_helper.check_ok(data)){
                            parent.checkHandlers();
                        }else{
                            var msg = window.awz_helper.errors.get_text(data);
                            window.awz_helper.showMessage(msg);
                        }

                        window.awz_helper.remove_loader();

                    },
                    error: function (){
                        var msg = window.awz_helper.errors.get_text('внутренняя ошибка сервера');
                        window.awz_helper.showMessage(msg);
                        window.awz_helper.remove_loader();
                    }
                });

            });
            $(document).on('click', '#external-events-del', function(e){
                e.preventDefault();
                var signed = $('#signed_add').val();

                $.ajax({
                    url: parent.endpointUrl+'delchandler',
                    data: {
                        signed: signed,
                        domain: $('#domain').val(),
                        app: $('#app').val()
                    },
                    dataType : "json",
                    type: "POST",
                    success: function (data, textStatus){
                        if(window.awz_helper.check_ok(data)){
                            parent.checkHandlers();
                        }else{
                            var msg = window.awz_helper.errors.get_text(data);
                            window.awz_helper.showMessage(msg);
                        }

                        window.awz_helper.remove_loader();

                    },
                    error: function (){
                        var msg = window.awz_helper.errors.get_text('внутренняя ошибка сервера');
                        window.awz_helper.showMessage(msg);
                        window.awz_helper.remove_loader();
                    }
                });

            });
        },
        loadWorkRight: function(){
            //listhook
            var parent = this;
            var signed = $('#signed_add').val();
            $('#list_hook .awz-marsh-item').remove();
            let html_names = '<div class="row my-3 pb-3" style="border-bottom:1px solid rgba(82, 92, 105, 0.1);"><div class="col col-2"><b>Ид</b></div><div class="col col-2"><b>Активность</b></div><div class="col col-2"><b>Сортировка</b></div><div class="col col-4"><b>Название правила</b></div><div class="col col-2 text-right"></div></div>';
            $.ajax({
                url: parent.endpointUrl+'listhook',
                data: {
                    signed: signed,
                    domain: $('#domain').val(),
                    app: $('#app').val()
                },
                dataType : "json",
                type: "POST",
                success: function (data, textStatus){
                    if(window.awz_helper.check_ok(data)){
                        let k;
                        for(k in data['data']){
                            let item = data['data'][k];
                            let btn = $('#awz-handler-slide-add').clone();
                            btn.find('a').attr('data-id', item.ID);
                            btn.find('a').removeClass('ui-btn-success');
                            btn.find('a').removeClass('ui-btn-icon-add');
                            btn.find('a').addClass('ui-btn-icon-setting');
                            btn.find('a').html('');
                            let btn_log = btn.clone();
                            btn_log.find('a').attr('data-page', 'log-handler');
                            btn_log.find('a').attr('class', 'awz-handler-slide');
                            btn_log.find('a').html(item.CNT);
                            let html = '<div class="col col-lg-4 col-xl-3 my-2 awz-marsh-item awz-marsh-item-active-'+item.ACTIVE+'">' +
                                '<div class="row align-items-center border-style-1">' +
                                '<div class="col col-10"><b>'+item.NAME+'</b></div>' +
                                '<div class="col col-2 text-right">'+btn.html()+'</div>' +
                                '<div class="col col-3">ID: '+item.ID+'</div>' +
                                '<div class="col col-5 text-center awz-marsh-item-active"><span>'+(item.ACTIVE=='Y' ? 'активно' : 'отключено')+'</span>'+btn_log.html()+'</div>' +
                                '<div class="col col-4 text-right">sort: '+item.SORT+'</div>' +
                                '</div>' +
                                '</div>';
                            //if(html_names){
                            //    $('#list_hook').append(html_names);
                            //    html_names = '';
                            //}
                            $('#list_hook').append(html);
                        }
                    }else{
                        var msg = window.awz_helper.errors.get_text(data);
                        window.awz_helper.showMessage(msg);
                    }

                    window.awz_helper.remove_loader();

                },
                error: function (){
                    var msg = window.awz_helper.errors.get_text('внутренняя ошибка сервера');
                    window.awz_helper.showMessage(msg);
                    window.awz_helper.remove_loader();
                }
            });
        },
        checkHandlers: function(){
            var parent = this;
            var signed = $('#signed_add').val();
            $.ajax({
                url: parent.endpointUrl+'getchandler',
                data: {
                    signed: signed,
                    domain: $('#domain').val(),
                    app: $('#app').val()
                },
                dataType : "json",
                type: "POST",
                success: function (data, textStatus){
                    $('#external-events-add').show();
                    $('#external-events-del').hide();
                    if(window.awz_helper.check_ok(data)){
                        let k;
                        for(k in data['data']['result']){
                            let event = data['data']['result'][k];
                            if(event.event == 'ONCRMACTIVITYADD'){
                                $('#external-events-add').hide();
                                $('#external-events-del').show();
                            }
                        }
                        console.log(data);
                    }else{
                        var msg = window.awz_helper.errors.get_text(data);
                        window.awz_helper.showMessage(msg);
                    }

                    window.awz_helper.remove_loader();

                },
                error: function (){
                    $('#external-events-add').show();
                    $('#external-events-del').hide();
                    var msg = window.awz_helper.errors.get_text('внутренняя ошибка сервера');
                    window.awz_helper.showMessage(msg);
                    window.awz_helper.remove_loader();
                }
            });
        },
        loadStats: function(){}
    }

})();

window.awz_helper = {
    endpointUrl:'',
    arParams: {},
    loader_class: 'awz-main-preload',
    get_preload_html: function (loader_mess) {
        if (!loader_mess) loader_mess = 'загрузка...';
        var ht = '<div class="' + this.loader_class + '">' +
            '<div class="awz-main-load">' +
            '<span>' + loader_mess + '</span>' +
            '</div>' +
            '</div>';
        return ht;
    },
    add_loader: function (el, title) {
        el.append(this.get_preload_html(title));
    },
    remove_loader: function () {
        $('.' + this.loader_class).remove();
    },
    check_ok: function (data) {
        if (typeof (data) === 'object') {
            if (data && data.hasOwnProperty('status') && data.status == 'success') {
                return true;
            }
        }
        return false;
    },
    ok: {
        get_text: function (mess) {
            return '<div class="ui-alert ui-alert-success">' + mess + '</div>';
        }
    },
    errors: {
        get_text: function(data){
            var mess = [];
            if(typeof(data) === 'object'){
                if(data && data.hasOwnProperty('status') && data.hasOwnProperty('errors') && data.status == 'error'){
                    var k;
                    for(k in data.errors){
                        var item = data.errors[k];
                        if(typeof(item) == 'object'){
                            if(item.hasOwnProperty('code')){
                                mess.push(item.code+": "+item.message);
                            }
                        }else if(typeof(item) == 'string'){
                            mess.push(item);
                        }else{
                            mess.push('Ошибка');
                        }
                    }
                }else{
                    mess.push('Ошибка');
                }
            }else if(typeof (data) == "string"){
                mess.push(data);
            }
            return '<div class="ui-alert ui-alert-danger">'+mess.join('; ')+'</div>';
        }
    },
    scrollTop: function(){
        $('html').scrollTop(0);
        try{
            BX24.scrollParentWindow(0);
        }catch (e) {

        }
    },
    showMessage: function(msg, el){
        if(!el) el = $('.result-block-messages');
        el.html(msg);
        this.scrollTop();
    },
    showButtons: function(el, code, type){
        if(type === 'add'){
            el.find('.bp-load').remove();
            el.find('.bp-buttons').append('<div class="bp-load bp-load-hide-btn"><a class="add ui-btn ui-btn-primary ui-btn-icon-done" href="#">установить</a></div>');
        }
        if(type === 'del'){
            el.find('.bp-load').remove();
            el.find('.bp-buttons').append('<div class="bp-load"><a class="remove ui-btn ui-btn-danger-light ui-btn-icon-alert" href="#">удалить</a></div>');
        }
    }
}