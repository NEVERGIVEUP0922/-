require(['jquery', 'jl-modal'], function($, modal) {
        require(['Account/js/common_register']);
        //动态添加css
        var links = document.createElement("link");
        links.id="login_id";
        links.setAttribute("rel","stylesheet");
        links.href="/Public/Home/Index/css/login-message.css";
        links.setAttribute("media","all");
        $($("head")[0]).append(links);
        var cookes= document.cookie;
        var cookData=cookes.split(";");

        var dataObj={};
        $.each(cookData,function(ind,val){
            var arrays=val.split("=");
            dataObj[arrays[0].trim()] = arrays[1];
        });
        if(dataObj.user){
            $("#jl-login-adm").val(dataObj.user);
            $("#jl-login-pw").val("");
        }else{
            $("#jl-login-adm").val('');
            $("#jl-login-pw").val('');
        }
        var login_page={};
        login_page.login_init=function(isPoint){
            var $user_input = $('#jl-login-adm');
            var $password_input=$('#jl-login-pw');
            var $user_adm=$('#jl-lo-adm');
            var $password_paw=$('#jl-login-paw');
            $user_input.blur(function () {
                if($user_input.val()===''){
                    $user_adm.css('visibility','visible');
                    $('#jl-lo-adm').children('span').html('用户名不能为空');
                }
                else {
                    $user_adm.css('visibility','hidden');
                    $('#jl-lo-adm').children('span').html();
                }
//                    var user_name = $user_input.val().trim();
//                    $.post("{:U('Home/Account/ajaxCheckUserName')}",{user_name:user_name}, function (res) {
//                        if(status===0){
//                            $user_adm.css('visibility','hidden');
//                            $('#jl-lo-adm').children('span').html();
//                        }
//                        else {
//                            $user_adm.css('visibility','visible');
//                            $('#jl-lo-adm').children('span').html(res.content);
//                        }
//                    })
            });
            $password_input.blur(function () {
                //判断是否为空
                if($password_input.val()===''){
                    $password_paw.css('visibility','visible');
                    $('#jl-login-paw').children('span').html('密码不能为空');
                }
                else {
                    $password_paw.css('visibility','hidden');
                    $('#jl-login-paw').children('span').html();
                }
            });
            $('.js-submit-input').on('keydown',function (e) {
                if(e.keyCode===13){
                    $('#js-login-submit').trigger('click');
                }
            });
            //用户登录
            $('#js-login-submit').on('click',function () {
                var index=layer.load();
                var user = $.trim($user_input.val());
                var password = $password_input.val();
                if($user_input.val()===''&&$password_input.val()===''){
                    $user_adm.css('visibility','visible');
                    $('#jl-lo-adm').children('span').html('用户名不能为空');
                    $password_paw.css('visibility','visible');
                    $('#jl-login-paw').children('span').html('密码不能为空');
                }
                else {
                    var data = {
                        user_name:user,
                        user_pass:password,
                    };
                    var isremenber = $(".jl-forget-pw").find("input[type=checkbox]:checked").val();
                    $.post("/Home/Account/login",data, function (res) {
                        debugger
                        if(res.status===0){
                            if(isPoint){login_fn();return;}
                            let name_show=res.data['user_type']==="1"?(res.data['nick_name']||res.data['company_name']):(res.data['company_name']||res.data['nick_name']);
                            $(".jl-head-left .js-register").remove();
                            $(".jl-head-left .js-login").remove();
                            $(".jl-head-left").append('<a class="jl-user-name" href="/Home/User/index">'+name_show+'</a><a class="js-login" href="/Home/Account/Logout">退出</a>');
                            layer.closeAll();
                            layer.msg('登陆成功',{icon:6});
                            if(isremenber){
                                var times =new Date();
                                times.setTime(times.getTime()+30*24*3600*1000);
                                var str="user="+user+";Expires="+times.toGMTString();
                                document.cookie=str;
                                //document.cookie="password="+res.data.password+";Expires="+times.toGMTString();
                            };
                            /*if(res.data){
                                $("#js-login-submit").css("display","none");
                                $(".js-company,.js-person").css("display","block");
                                $(".js-company").attr("data-id",res.data['company']['user_id']);
                                $(".js-person").attr("data-id",res.data['personal']['user_id']);
                                layer.msg('登陆成功',{icon:6});
                            }
                            else{

                            }*/
                        }else {
                            layer.close(index);
                            if(res.status===1200 ||res.status===3000|| res.data===1102){
                                $password_paw.css('visibility','visible');
                                $('#jl-login-paw').children('span').html(res.content);
                            }
                            else {
                                $password_paw.css('visibility','visible');
                                $('#jl-login-paw').children('span').html('用户名或密码错误');
                            }
                        }
                    })
                }
            });
            //选择账号
            $(".js-company,.js-person").on("click",function(){
                var user = $.trim($user_input.val());
                var password = $password_input.val();
                var user_id=$(this).attr("data-id");
                var ischeck = $(".jl-forget-pw").find("input:checked").val();
                if($user_input.val()===''&&$password_input.val()===''){
                    $user_adm.css('visibility','visible');
                    $('#jl-lo-adm').children('span').html('用户名不能为空');
                    $password_paw.css('visibility','visible');
                    $('#jl-login-paw').children('span').html('密码不能为空');
                }
                else {
                    var data = {
                        user_name:user,
                        user_pass:password,
                        user_id:user_id
                    };
                    $.post("{:U('Home/Account/login')}",data, function (res) {
                        if(res.status===0){
                            /*if(ischeck){
                                var nows = new Date();
                                nows.setDate(nows.getTime()+30*24*3600*1000);
                                document.cookie='user_name='+user+';expires='+now.toUTCString;
                            };*/
                            location.href='{:U("Home/Default/index")}';
                        }
                        else {
                            if(res.status===1200){
                                $password_paw.css('visibility','visible');
                                $('#jl-login-paw').children('span').html(res.content);
                            }
                            else if(res.status===3000){
                                $password_paw.css('visibility','visible');
                                $('#jl-login-paw').children('span').html(res.content);
                            }
                            else {
                                $password_paw.css('visibility','visible');
                                $('#jl-login-paw').children('span').html('用户名或密码错误');
                            }
                        }
                    })
                }
            });
        };
        //登录框html
        login_page.login_html='<div class="jl-login-wrap" style="background-color:#091C3D">\n' +
            '        <div class="jl-login-item" style="width:100%">\n' +
            '            <!--loginmsg-->\n' +
            '            <div class="jl-login-msg" style="position:static">\n' +
            '                <div class="jl-login-de jl-cle">\n' +
            '                    <h2 class="jl-login-tit">密码登录</h2>\n' +
            '                    <form method="post">\n' +
            '                        <div class="jl-login-item">\n' +
            '                            <label for="jl-login-adm" class="jl-lab jl-bg01"></label>\n' +
            '                            <input type="text" id="jl-login-adm" class="jl-login-user js-next-input" tabindex="1" placeholder="请输入用户名或手机号">\n' +
            '                        </div>\n' +
            '                        <div class="jl-login-check" id="jl-lo-adm">\n' +
            '                            <i class="jl-lo-bg"></i>\n' +
            '                            <span></span>\n' +
            '                        </div>\n' +
            '                        <div class="jl-login-item">\n' +
            '                            <label for="jl-login-pw" class="jl-lab jl-bg02"></label>\n' +
            '                            <input type="password" id="jl-login-pw" class="jl-login-user js-next-input js-submit-input" tabindex="2" placeholder="请输入密码">\n' +
            '                        </div>\n' +
            '                        <div class="jl-login-check" id="jl-login-paw">\n' +
            '                            <i class="jl-lo-bg"></i>\n' +
            '                            <span></span>\n' +
            '                        </div>\n' +
            '                        <div class="jl-login-item jl-login-for">\n' +
            '                            <span class="jl-forget-pw" style="float:left"><s ><input type="checkbox" value="1" checked/><s>记住账号</s></s></span>\n' +
            '                            <span class="jl-forget-pw"><a href="{:U(\'Home/Account/passForget\')}">忘记密码</a></span>\n' +
            '                        </div>\n' +
            '                        <!--<div class="jl-login-item jl-login-btn">-->\n' +
            '                            <input id="js-login-submit" class="js-next-input" type="button" value="登 录">\n' +
            '                        <!--</div>-->\n' +
            '                    </form>\n' +
            '                    <button class="js-company">企业账号登录</button>\n' +
            '                    <button class="js-person">个人账号登录</button>\n' +
            '                </div>\n' +
            '                <div class="jl-choose">\n' +
            '                    <ul class="jl-choose-de jl-cle">\n' +
            '                        <li>\n' +
            '                            <a href="/Home/Account/login?type=qq" class="jl-way">\n' +
            '                                <b class="jl-choose-qq-bg"></b>\n' +
            '                            </a>\n' +
            '                        </li>\n' +
            '                        <li>\n' +
            '                            <a href="/Home/Account/login?type=wechat" class="jl-way">\n' +
            '                                <b class="jl-choose-weiXin"></b>\n' +
            '                            </a>\n' +
            '                        </li>\n' +
            '                        <li>\n' +
            '                            <a href="/Home/Account/login?type=sina" class="jl-way">\n' +
            '                                <b class="jl-choose-weiBo"></b>\n' +
            '                            </a>\n' +
            '                        </li>\n' +
            '                        <li class="jl-last-choose">\n' +
            '                            <a href="/Home/Account/register" class="jl-run-reg">\n' +
            '                                <b></b>\n' +
            '                                <span>立即注册</span>\n' +
            '                            </a>\n' +
            '                        </li>\n' +
            '                    </ul>\n' +
            '                </div>\n' +
            '            </div>\n' +
            '        </div>\n' +
            '    </div>';
        window.login_page=login_page;
    });
