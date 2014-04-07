/**	
	author		HitkoDev
	copyright	Copyright (C) 2014 HitkoDev All Rights Reserved.
	@license	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
	Website		http://hitko.eu/software/videobox
*/

(function cfr(b){var c=b(window),a=!window.XMLHttpRequest,f=[];b(function i(){b("body").append(b([overlay=b('<div id="cfrOverlay" />').click(b.cfr_close)[0],center=b('<div id="cfrCenter" />')[0]]).css("display","none"));message=b('<div id="cfrContent" />').appendTo(center)[0];close=b('<div id="cfrClose" />').click(b.cfr_close).appendTo(center)[0]});b.cfr=function(k,j){options=b.extend({overlayOpacity:0.8,overlayFadeDuration:400,resizeDuration:400,resizeEasing:"swing",width:400},j);message.innerHTML=k;middle=c.scrollTop()+(c.height()/2);compatibleOverlay=a||(overlay.currentStyle&&(overlay.currentStyle.position!="fixed"));if(compatibleOverlay){overlay.style.position="absolute"}b(overlay).css("opacity",options.overlayOpacity).fadeIn(options.overlayFadeDuration);b(overlay).unbind("click").click(b.cfr_close);b(close).unbind("click").click(b.cfr_close);d();e()};b.cfr_close=function(){b(overlay).stop().fadeOut(options.overlayFadeDuration,h);g();return false};function h(j){if(j){b("object").add(a?"select":"embed").each(function(l,m){f[l]=[m,m.style.visibility];m.style.visibility="hidden"})}else{b.each(f,function(l,m){m[0].style.visibility=m[1]});f=[]}var k=j?"bind":"unbind";c[k]("scroll resize",d)}function d(){var k=c.scrollLeft(),j=c.width();b(center).css("left",k+(j/2));if(compatibleOverlay){b(overlay).css({left:k,top:c.scrollTop(),width:j,height:c.height()})}}function e(){b(center).show();centerWidth=b(center).width();if(centerWidth>c.width()){centerWidth=c.width()}if(centerWidth>options.width){centerWidth=options.width}centerHeight=b(center).height();var j=Math.max(0,middle-(centerHeight/2));b(center).animate({top:j,marginLeft:-centerWidth/2},options.resizeDuration,options.resizeEasing);if(centerWidth!=b(center).width()){b(center).css("max-width",centerWidth)}b(message).css("width",(centerWidth-20))}function g(){b(center).hide();b(message).css("width","")}})(jQuery);

/**
	jQuery.contextMenu - Show a custom context when right clicking something
	Jonas Arnklint, http://github.com/arnklint/jquery-contextMenu
	Released into the public domain
	Date: Jan 14, 2011
	@author Jonas Arnklint
	@version 1.7
 */
 
(function(a){jQuery.fn.contextMenu=function(c,h,m){var k=this,j=a(window),e=a('<ul id="'+c+'" class="context-menu"></ul>').hide().appendTo("body"),f=null,d=null,l=function(){a(".context-menu:visible").each(function(){a(this).trigger("closed");a(this).hide();a("body").unbind("click",l);e.unbind("closed")})},i={shiftDisable:false,disable_native_context_menu:false,leftClick:false},m=a.extend(i,m);a(document).bind("contextmenu",function(n){if(m.disable_native_context_menu){n.preventDefault()}l()});a.each(h,function(q,o){if(o.link){var p=o.link}else{var p='<a href="#">'+q+"</a>"}var n=a("<li>"+p+"</li>");if(o.klass){n.attr("class",o.klass)}n.appendTo(e).bind("click",function(r){o.click(f,d);r.preventDefault()})});if(m.leftClick){var b="click"}else{var b="contextmenu"}var g=function(p){if(m.shiftDisable&&p.shiftKey){return true}l();f=a(this);d=p;if(m.showMenu){m.showMenu.call(e,f)}if(m.hideMenu){e.bind("closed",function(){m.hideMenu.call(e,f)})}e.css({visibility:"hidden",position:"absolute",zIndex:1000});var n=e.outerWidth(true),r=e.outerHeight(true),q=((p.pageX-j.scrollLeft())+n<j.width())?p.pageX:p.pageX-n,o=((p.pageY-j.scrollTop())+r<j.height())?p.pageY:p.pageY-r;e.show(0,function(){a("body").bind("click",l)}).css({visibility:"visible",top:o+"px",left:q+"px",zIndex:1000});return false};if(m.delegateEventTo){return k.on(b,m.delegateEventTo,g)}else{return k.bind(b,g)}}})(jQuery);