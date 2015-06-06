(function($){
	"use strict";
	$(function(){
		var $gnb = $('.gnb');
		var $fnb = $('.fnb');
		var $hoverEl = $('.hover');
		var $searchEl = $('.click > a');
		var $searchForm = $('.search_area');

		// Gnb
		$gnb.find('>ul>li>a')
		.mouseover(function(){
			$gnb.find('>ul>li>ul:visible').hide().parent('li').removeClass('on');
			$(this).next('ul:hidden').stop().fadeIn(200).parent('li').addClass('on')
		})
		.focus(function(){
			$(this).mouseover();
		})
		.end()
		.mouseleave(function(){
			$gnb.find('>ul>li>ul').hide().parent().removeClass('on')
		});

		$gnb.find('>ul>li>ul>li>a')
		.mouseover(function(){
			$gnb.find('>ul>li>ul>li>ul:visible').hide().parent('li').removeClass('on');
			$(this).next('ul:hidden').stop().fadeIn(200).parent('li').addClass('on')
		})
		.focus(function(){
			$(this).mouseover();
		})
		.end()
		.mouseleave(function(){
			$gnb.find('>ul>li>ul>li>ul').hide().parent().removeClass('on')
		});

		// Fnb
		$fnb.find('>ul>li>a')
		.mouseover(function(){
			$fnb.find('>ul>li>ul:visible').hide().parent('li').removeClass('on');
			$(this).next('ul:hidden').stop().fadeIn(200).parent('li').addClass('on')
		})
		.focus(function(){
			$(this).mouseover();
		})
		.end()
		.mouseleave(function(){
			$fnb.find('>ul>li>ul').hide().parent().removeClass('on')
		});

		$fnb.find('>ul>li>ul>li>a')
		.mouseover(function(){
			$fnb.find('>ul>li>ul>li>ul:visible').hide().parent('li').removeClass('on');
			$(this).next('ul:hidden').stop().fadeIn(200).parent('li').addClass('on')
		})
		.focus(function(){
			$(this).mouseover();
		})
		.end()
		.mouseleave(function(){
			$fnb.find('>ul>li>ul>li>ul').hide().parent().removeClass('on')
		});

		// login popup
		$hoverEl.on('mouseenter mouseleave focusin focusout',function(e){
			e.preventDefault();
			if(e.type == 'mouseenter' || e.type == 'focusin'){
				$(this).addClass('on');
			} else {
				$(this).removeClass('on');
			}
		});

		// Search
		$searchEl.click(function(){
			if($searchForm.is(':hidden')){
				$searchForm.fadeIn().find('input').focus();
				$('.header').css('opacity',0)
			}
			return false;
		});
		$('.btn_close').click(function(){
			var $this = $(this);
			$this.parent().fadeOut().find('input').val('');
			$('.header').css('opacity',1)
			$searchEl.focus();
			return false;
		});

		// Scroll to top
		var scrollToTop = function() {
			var link = $('.btn_top');
			var windowW = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

			$(window).scroll(function() {
				if (($(this).scrollTop() > 150) && (windowW > 1000)) {
					link.fadeIn(100);
				} else {
					link.fadeOut(100);
				}
			});

			link.click(function() {
				$('html, body').animate({scrollTop: 0}, 400);
				return false;
			});
		};
		scrollToTop();
	})
})(jQuery);

(function($) {
	"use strict";
	var $window = $(window);
	var windowHeight = $window.height();

	$window.resize(function() {
		windowHeight = $window.height()
	});

	$.fn.parallax = function(xpos, speedFactor, outerHeight) {
		var $this = $(this);
		var getHeight;
		var firstTop;
		$this.each(function() {
			firstTop = $this.offset().top;
		});

		if (outerHeight) {
		  getHeight = function(object) {
			return object.outerHeight(true)
		  }
		} else {
		  getHeight = function(object) {
			return object.height()
		  }
		}
		if (arguments.length < 1 || xpos === null)
		  xpos = "50%";
		if (arguments.length < 2 || speedFactor === null)
		  speedFactor = 0.1;
		if (arguments.length < 3 || outerHeight === null)
		  outerHeight = true;
		function update() {
		  var pos = $window.scrollTop();
		  $this.each(function() {
			var $element = $(this);
			var top = $element.offset().top;
			var height = getHeight($element);

			if (top + height < pos || top > pos + windowHeight) {
			  return
			}
			$this.css('backgroundPosition', xpos + " " + Math.round((firstTop - pos) * speedFactor) + "px")
		  })
		}
		$window.bind('scroll', update).resize(update);
		update()
  }
})(jQuery);

