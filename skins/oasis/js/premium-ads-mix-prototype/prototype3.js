var admixMode = location.search.split('admix=')[1];

if(admixMode === '3') {
	$(function () {
		var ad2 = $('.prototype3-ad2');
		var recirc = $('.prototype3-recirc');

		function resetInlineStyles() {
			recirc.css({
				position: '',
				top: ''
			});
		}

		function apply1() {
			resetInlineStyles();
			recirc.removeClass('fixed');
			recirc.removeClass('hidden');
			ad2.removeClass('fixed');
		}

		function apply2() {
			resetInlineStyles();
			recirc.addClass('fixed');
			recirc.removeClass('hidden');
			ad2.removeClass('fixed');
		}

		function apply3() {
			resetInlineStyles();
			recirc.addClass('hidden');
			recirc.removeClass('fixed');
			ad2.addClass('fixed');
		}

		function apply4() {
			resetInlineStyles();
			recirc.removeClass('hidden');
			recirc.addClass('fixed');
			ad2.removeClass('fixed');
		}

		function apply5() {
			recirc.css({
				position: 'absolute',
				bottom: '20px'
			});
			recirc.removeClass('hidden');
			recirc.removeClass('fixed');
			ad2.removeClass('fixed');
		}

		$(window).scroll(function () {
			var recircOffsetTop = recirc.offset().top;
			
			var point1 = recircOffsetTop - 60;
			var point2 = recircOffsetTop + 700 - 60;
			var point3 = recircOffsetTop + 1400 - 60;
			var point4 = $('.WikiaPageContentWrapper').height() - (383);

			var scrollTop = $(this).scrollTop();

			if(scrollTop > point1 && scrollTop < point2) {
				apply2();
			} else if(scrollTop < point1) {
				apply1();
			} else if(scrollTop > point2 && scrollTop < point3) {
				apply3();
			} else if(scrollTop > point3 && scrollTop < point4) {
				apply4();
			} else if(scrollTop > point4) {
				apply5();
			}
		});
	});
}
