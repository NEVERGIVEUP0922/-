"use strict";define(["jquery"],function(n){Function.prototype.bind||(Function.prototype.bind=function(t){if("function"!=typeof this)throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");var o=Array.prototype.slice.call(arguments,1),n=this,e=function(){},i=function(){return n.apply(this instanceof e&&t?this:t,o.concat(Array.prototype.slice.call(arguments)))};return e.prototype=this.prototype,i.prototype=new e,i});var e={title:"您确定吗？",brief:"",type:"normal",clickClose:!0,cancelBtn:!1,isCenter:!1,maxHeight:"",confirmText:"确定",cancelText:"关闭",top:0,left:0,width:"365px",duration:!1,hiddenCloseIcon:!1,confirm:function(){},cancel:function(){},close:function(){}};n("body").append('<div style="display: none" class="jl-modal-container js-modal-container vertical-box"> <div class="jl-modal-mask"></div> <div class="jl-modal-box vertical-middle"> <div class="jl-modal-header vertical-box"> <div class="jl-modal-close-btn vertical-middle"><i class="jl-modal-close-icon"></i></div></div> <div class="jl-modal-body"> <div class="jl-modal-title"></div> <div class="jl-modal-brief"></div> </div> <div class="jl-modal-footer"> <button class="jl-modal-primary-btn">确定</button> <button class="jl-modal-secondary-btn">关闭</button> </div> </div> </div>');var i,l=n(".jl-modal-container.js-modal-container"),c=l.find(".jl-modal-box"),a=l.find(".jl-modal-title"),d=l.find(".jl-modal-brief"),r=l.find(".jl-modal-primary-btn"),s=l.find(".jl-modal-secondary-btn"),f=l.find(".jl-modal-close-btn"),m={},u=function(t,o){var n={normal:{show:l.show,hide:l.hide},fade:{show:l.fadeIn,hide:l.fadeOut},slide:{show:l.slideDown,hide:l.slideUp}};return t?n[o].show.bind(l):n[o].hide.bind(l)},p=function(t){var o;o=m=t,a.text(o.title),d.html(o.brief),r.text(o.confirmText),s.text(o.cancelText),c.css({bottom:o.top,right:o.left,width:o.width,"max-height":o.maxHeight,overflow:"auto"}),m.cancelBtn?s.show():s.hide(),m.isCenter?l.addClass("jl-modal-text-center"):l.removeClass("jl-modal-text-center"),m.hiddenCloseIcon?f.hide():f.show(),u(!0,t.type)();var n=parseFloat(m.duration);n&&(i=setTimeout(function(){h(m)},n))},h=function(t){u(!1,t.type)(),clearTimeout(i)};return n(".jl-modal-close-btn").on("click",function(){h(m),m.close&&m.close()}),r.on("click",function(){m.clickClose&&h(m),m.confirm&&m.confirm()}),s.on("click",function(){m.clickClose&&h(m),m.cancel&&m.cancel()}),{option:function(t){n.extend(e,t)},confirm:function(t){var o={};o=n.extend(o,e,t,{cancelBtn:!0}),p(o)},alert:function(t){var o={};n.extend(o,e,t,{cancelBtn:!1,isCenter:!0}),p(o)},open:p,close:function(t){var o={};n.extend(o,e,t),h(o)}}});