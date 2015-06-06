// Sticky Note
(function($) {
    $.fn.hasScrollBar = function() {
        return this.get(0).scrollHeight > this.innerHeight();
    }
})(jQuery);

jQuery(function($){
	var iColors = -1;
	var p = $('div.wgt_sticky_note');
	var o = p.find('div.wgt_sticky_note_itm');
	var scrollbarWidth = getScrollbarWidth();
	var dragging = false;

	p.parent().parent().addClass('wgt_sticky_note_wrp');

	function getScrollbarWidth() 
	{
	    var div = $('<div style="width:50px;height:50px;overflow:hidden;position:absolute;top:-200px;left:-200px;"><div style="height:100px;"></div></div>'); 
	    $('body').append(div); 
	    var w1 = $('div', div).innerWidth(); 
	    div.css('overflow-y', 'auto'); 
	    var w2 = $('div', div).innerWidth(); 
	    $(div).remove(); 
	    return (w1 - w2);
	}

	function imgProportion($targetWidth, $targetHeight, cntWidth)
	{
		var a1 = cntWidth * $targetHeight;
		var newHeight = (a1 / $targetWidth);
		var rtnSize = new Array(cntWidth, newHeight); 	//리사이징 된 이미지 크기 리턴

		return rtnSize;
	}

	function resizeImg(oCnt, cntWidth) {
		$("img", oCnt).each(function()	//각 이미지에 대해
		{
			var $this 		= $(this); //선택자를 지정.
			var $thisWidth  = parseInt($this.attr("originalWidth")); 	//선택된 이미지의 저장된 너비
			var $thisHeight = parseInt($this.attr("originalHeight")); 	//선택된 이미지의 저장된 높이

			if (isNaN($thisWidth)) // 저장된 크기가 없다면 CSS에서 크기를 받아서 저장
			{
				$thisWidth  = parseInt($this.css("width")); 	//선택된 이미지의 너비
				$thisHeight = parseInt($this.css("height")); 	//선택된 이미지의 높이
				$this.attr({  //선택된 이미지의 원 크기를 저장
					"originalWidth"  : $thisWidth,
					"originalHeight" : $thisHeight
				});
			}
			if($thisWidth > cntWidth) //이미지 가로가 길다면..
			{
				var rtn = imgProportion($thisWidth, $thisHeight, cntWidth);
				var newWidth  = rtn[0];
				var newHeight = rtn[1];
	
				$this.css({  //선택된 이미지에 CSS를 변경.
					"width"  : newWidth,
					"height" : newHeight
				});
			}
			else {
				$this.css({  // 이미지의 원래 크기로 변경.
					"width"  : $thisWidth,
					"height" : $thisHeight
				});
			}
		});
	}

	function resizeMed(oCnt, cntWidth) {
		var $allMedia = oCnt.find("iframe");
		$.merge($allMedia, oCnt.find("embed"));
		$.merge($allMedia, oCnt.find("object"));
		$.merge($allMedia, oCnt.find("video"));
		$.merge($allMedia, oCnt.find("audio"));

		$allMedia.each(function(){
			var $this 		= $(this); //선택자를 지정.
			var $thisWidth  = parseInt($this.attr("originalWidth")); 	//선택된 미디어의 저장된 너비
			var $thisHeight = parseInt($this.attr("originalHeight")); 	//선택된 미디어의 저장된 높이

			if (isNaN($thisWidth)) // 저장된 크기가 없다면 CSS에서 크기를 받아서 저장
			{
				$thisWidth  = parseInt($this.attr("width")); 	//선택된 미디어의 너비
				$thisHeight = parseInt($this.attr("height")); 	//선택된 미디어의 높이
				if (isNaN($thisWidth)) {
					$thisWidth  = parseInt($this.css("width")); 	//선택된 미디어의 너비
					$thisHeight = parseInt($this.css("height")); 	//선택된 미디어의 높이
				}
				if (!isNaN($thisWidth)) {
					$this.attr({  //선택된 미디어의 원 크기를 저장
						"originalWidth"  : $thisWidth,
						"originalHeight" : $thisHeight
					});
				}
			}
			if($thisWidth > cntWidth) //미디어 가로가 길다면..
			{
				var rtn = imgProportion($thisWidth, $thisHeight, cntWidth);
				var newWidth  = rtn[0];
				var newHeight = rtn[1];

				$this.attr({  //선택된 미디어의 크기를 변경
					"width"  : newWidth,
					"height" : newHeight
				});
				$this.css({  //선택된 미디어의 크기를 변경
					"width"  : newWidth,
					"height" : newHeight
				});
			}
			else {
				$this.attr({  // 미디어의 원래 크기로 변경.
					"width"  : $thisWidth,
					"height" : $thisHeight
				});
				$this.css({  // 미디어의 원래 크기로 변경.
					"width"  : $thisWidth,
					"height" : $thisHeight
				});

			}
		});
	}
	
    function cloud(isInit){
		o.each(function(){
			var t = $(this);
			var m = Math.floor(Math.random()*40-20);
			var iMinW = parseInt(t.css('min-width'));
			var iMaxW = parseInt(t.css('max-width'));
			var iMinH = parseInt(t.css('min-height'));
			var iWidth = iMinW+Math.floor(Math.random()*(iMaxW-iMinW));
			var iHeight = iMinH+Math.floor(Math.random()*(parseInt(t.css('max-height'))-iMinH));
			var iLeft = Math.floor(Math.random()*(p.width()-250));
			var oDoc = t.find('div.doc');
			var oExp = oDoc.find('div.expiredays');
			var oBtn = oExp.find('img.closeButton');
			if (isPad && iLeft+iWidth > screen.width-100) {
				iLeft = Math.floor(Math.random()*(screen.width/2-100));
			}
			t.css({
				visibility:'visible',
				display:'inline-block',
				top:Math.floor(Math.random()*(p.height()-300)),
				left:iLeft,
				width:iWidth,
				height:iHeight,
				'-webkit-transform':'rotate('+m+'deg)',
				'-moz-transform':'rotate('+m+'deg)',
				'-o-transform':'rotate('+m+'deg)',
				'-ms-transform':'rotate('+m+'deg)',
				'transform':'rotate('+m+'deg)'
			});

			function resizeNote() {
				var oTitle = t.find('h3.title');
				var oDate = oDoc.find('span.date');
				var oCnt = oDoc.find('div.content');

				if (t.css('background-color') == 'transparent') {
					// CSS 파일에 정의된 색상을 넘어가는 경우 처리
					var iID = t.attr('class').match(/[0-9]+/);
					if (iColors == -1) iColors = iID;
					var sThis = 'div.wgt_sticky_note_itm.color'+iID;
					var sOrg = 'div.wgt_sticky_note_itm.color'+iID%iColors;
					var bgColor = p.find(sOrg).css('background-color');

					p.find(sThis).css({'background-color':bgColor,
						background:'-webkit-linear-gradient(top,'+bgColor+','+bgColor+')',
						background:'-moz-linear-gradient(top,'+bgColor+','+bgColor+')',
						background:'-ms-linear-gradient(top,'+bgColor+','+bgColor+')',
						background:'-o-linear-gradient(top,'+bgColor+','+bgColor+')',
						background:'-webkit-gradient(linear, left top, left bottom, from('+bgColor+'), to('+bgColor+'))',
						background:'linear-gradient(to bottom,'+bgColor+','+bgColor+')',
						'color':p.find(sOrg).css('color')});
					p.find(sThis+' a').css({'color':p.find(sOrg+' a').css('color')});
					p.find(sThis+' button').css({'color':p.find(sOrg+' button').css('color')});
				}
				
				oTitle.css({width:iWidth});
				oDoc.css({width:iWidth});
				oDate.css({width:iWidth});

				iCHeight = iHeight-parseInt(oCnt.position().top)-(oExp ? oExp.height() : 0)+t.find('span.deco').height();
				oCnt.css({width:iWidth,height:iCHeight});

				var cntWidth = oCnt.hasScrollBar() ?  oCnt.innerWidth()-scrollbarWidth : oCnt.innerWidth();
				if (resizeImages==true) resizeImg(oCnt, cntWidth);
				if (resizeVideos==true) resizeMed(oCnt, cntWidth);
			}
			if (isInit == true)
			{
				var timer = setInterval(function (){
					clearInterval(timer);
					resizeNote();
				}, 500);
			}
			else {
				resizeNote()
			}
		});
    };

    function setCloseButton(){
		o.each(function(){
			var t = $(this);
			var oDoc = t.find('div.doc');
			var oExp = oDoc.find('div.expiredays');
			var oBtn = oExp.find('img.closeButton');
			
			if (oBtn) {
				if ((getCookie(oBtn.attr('baseUrl')+'_'+oBtn.attr('srl')) == 'closed')) {
					t.remove();
					return true;
				}
				// 닫기 버튼에 이벤트 설정
				oBtn.click(
					{ noteId:oBtn.attr('noteId'),
					  srl:oBtn.attr('srl'),
					  expireDays:oBtn.attr('expiredays'),
					  baseUrl:oBtn.attr('baseUrl'),
					  oChk:oExp.find('Input.expireCheck') },
					function(e){
						if (e.data.oChk.is(':checked')) {
						    if (document.all && location.host != '') {
								setCookie(e.data.baseUrl+'_'+e.data.srl, 'closed', parseInt(e.data.expireDays), '/', location.host );
						    } else if (document.domain != '') {
								setCookie(e.data.baseUrl+'_'+e.data.srl, 'closed', parseInt(e.data.expireDays), '/', document.domain );
							} else {
								setCookie(e.data.baseUrl+'_'+e.data.srl, 'closed', parseInt(e.data.expireDays), '/' );
							}
						}
						$('div.wgt_sticky_note').find('div.wgt_sticky_note_itm.color' + e.data.noteId).remove();
					}
				);
			}
		});
	}
	
	function resizeMobile(){
		o.each(function(){
			var oCnt = $(this).find('div.doc').find('div.content');

			if (resizeImages==true) resizeImg(oCnt, $(window).width()-40);
			if (resizeVideos==true) resizeMed(oCnt, $(window).width()-40);
		});
	}

	o.draggable({
		containment:'document',
		start: function(){
			dragging = true;
		},
		stop: function(){
			dragging = false;
		}
	});

	o.hover(function(){
		var zidx = parseInt($(this).css('z-index'));
		var zi   = parseInt($(this).attr('zi'));
		if (!dragging && zi == zidx) {
			$(this).attr('state', $(this).css('transform'));
			$(this).css({'z-index':zi+10000,
				'filter':'alpha(opacity=100)',
				'opacity':1,
				'-o-transform':'scale(1.2) rotate(0deg)',
				'-ms-transform':'scale(1.2) rotate(0deg)',
				'-moz-transform':'scale(1.2) rotate(0deg)',
				'-webkit-transform':'scale(1.2) rotate(0deg)',
				'transform':'scale(1.2) rotate(0deg)'});
		}
	},function(){
		if (!dragging) {
			var state = $(this).attr('state');
			$(this).css({'z-index':$(this).attr('zi'),
				'filter':'alpha(opacity=90)',
				'opacity':.7,
				'-o-transform':state,
				'-ms-transform':state,
				'-moz-transform':state,
				'-webkit-transform':state,
				'transform':state});
		}
	});

	o.click(function(){
		var oThis = $(this);
		var topZI = parseInt(Math.max(0, Math.max.apply(null, $.map(o,function(v){return parseInt($(v).attr('zi')) || null;}))));
		if (parseInt(oThis.attr('zi')) != topZI) {
			o.each(function(){
				var zi = parseInt($(this).attr('zi'));
				if (zi > parseInt(oThis.attr('zi'))) {
					$(this).attr('zi', zi-1);
					$(this).css('z-index', zi-1);
				}
			});
			oThis.attr('zi', topZI);
			oThis.css('z-index', topZI);
		}
	});

	setCloseButton();

	if (!isMobile) cloud(true);
		
	o.fadeIn(200);
	p.find('button.wgt_sticky_note_btn').click(cloud);
	if (!isMobile && !isPad){
		$(window).resize(cloud);
	}
	else if (isMobile){
		if (resizeImages==true || resizeVideos==true) {
			resizeMobile();
			$( window ).on( 'orientationchange', resizeMobile);
		}
	}
});
