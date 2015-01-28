/**
 * Simple slider for landing page intro
 */
define(['jquery', 'lib/jquery.easing'], function () {
	
	var DEFAULT_OPTIONS = {
		'index': 0
	};
	
	function Slider (_el, _options) {
		if (!(this instanceof Slider)) return new Slider(_el, _options);
		
		var el      = $(_el),
			options = $.extend({}, DEFAULT_OPTIONS, options),
			items   = el.find('li');
		
		el.find('.next').on('click toucstart', this.next.bind(this));
		el.find('.back').on('click toucstart', this.previous.bind(this));
		
		items.eq(options.index).addClass('active');
		
		this.el = el;
		this.list = el.find('ul');
		this.items = items;
		this.count = items.length;
		this.index = options.index;
		this.options = options;
	}
	
	Slider.prototype = {
		
		/**
		 * Show item by index
		 */
		show: function (index) {
			if (this.index === index) return;
			this.hide(this.index);
			
			var item = this.items.eq(index);
			
			item.find('h1, h2, h3, h4, h5, p')
				.stop(true)
				.css({
					'top': '-200px'
				})
				.animate({
					'top': '0px'
				}, {
					'duration': 700,
					'easing': 'easeInOutCubic'
				});
			
			item.stop(true)
				.animate({
					'opacity': 1
				}, {
					'duration': 700,
					'easing': 'easeInOutCubic'
				});
			
			item.addClass('active');
			
			this.index = index;
		},
		
		/**
		 * Hide item by index
		 */
		hide: function (index) {
			var item = this.items.eq(index);
			
			item.find('h1, h2, h3, h4, h5, p')
				.stop(true)
				.animate({
					'top': '200px'
				}, {
					'duration': 700,
					'easing': 'easeInOutCubic'
				});
			
			item.stop(true)
				.animate({
					'opacity': 0
				}, {
					'duration': 700,
					'easing': 'easeInOutCubic'
				});
			
			item.removeClass('active');
		},
		
		/**
		 * Show next item
		 */
		next: function () {
			var index = (this.index + 1) % this.count;
			this.show(index);
			return false;
		},
		
		/**
		 * Show previous item
		 */
		previous: function () {
			var count = this.count,
				index = (this.index - 1 + count) % count;
			
			this.show(index);
			return false;
		}
	};
	
	
	$.Slider = Slider;
	
	$.fn.slider = function (options) {
		return this.each(function () {
			var element = $(this),
				instance = element.data('slider');
			
			if (!instance) {
				instance = new Slider(element, options);
				element.data('slider', instance);
			}
		});
	};
	
});
