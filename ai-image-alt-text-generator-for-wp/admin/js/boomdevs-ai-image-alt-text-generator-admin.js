(function( $ ) {
	'use strict';
	$.fn.ProgressBar = function(){
		let targetParent = $(this);
		targetParent.each(function(){

			//required variables
			let target = $(this).children();
			let offsetTop = $(this).offset().top;
			let winHeight = $(window).height();
			let data_width = target.attr("data-percent") + "%";
			let data_color = target.attr("data-color");
			target.css({
				backgroundColor: data_color,
			});
			target.animate({
				width: data_width,
			}, 1000);

		});

		return this;
	}
	$(document).ready(function(){
		$(".progress-bar").ProgressBar();
	});
})( jQuery );


