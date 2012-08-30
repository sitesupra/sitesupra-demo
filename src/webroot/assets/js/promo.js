(function ($) {
	"use strict";
	
	//Sizes
	var SIZE_DESKTOP = 1,
		SIZE_TABLET = 2,
		SIZE_MOBILE_LANDSCAPE = 3,
		SIZE_MOBILE_PORTRAIT = 4;
	
	
	//Touch event support
	var TOUCH_SUPPORTED = ('ontouchstart' in document.documentElement);
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'promo';
	
	//Predefined styles
	var STYLES = {
		'dots': {
			'dots': true,
			'thumbnails': false,
			'pages': false,
			'arrows': false,
			'animation': 'horizontal'
		},
		'arrows': {
			'dots': false,
			'thumbnails': false,
			'pages': false,
			'arrows': true,
			'animation': 'horizontal'
		},
		'arrows-outer': {
			'dots': false,
			'thumbnails': false,
			'pages': false,
			'arrows': true,
			'animation': 'horizontal-fade'
		},
		'thumbs': {
			'dots': false,
			'thumbnails': true,
			'pages': false,
			'arrows': false,
			'animation': 'fade'
		},
		'touch': {
			'dots': false,
			'thumbnails': false,
			'pages': false,
			'arrows': true,
			
			'animation': 'horizontal'
		}
	};
	
	//Default widget options
	var DEFAULTS = {
		'arrows': false,
		'dots': false,
		'pages': false,
		'thumbnails': false,
		
		'touch': true,
		'keyboard': !($('html').hasClass('supra-cms')), // In CMS mode we are editing text
		
		'timer': 5000,
		
		'animation': 'horizontal',
		'duration': 400,
		
		'list': 'ul',
		'items': 'li',
		'classname': 'promo-slider',
		
		'style': null
	};
	
	var TRANSITIONS = {
		'horizontal': function (from, to, dir) {
			var width = this.width(),
				duration = this.options.duration,
				pos = dir == 1 ? -width : width;
			
			from.node.css({
				'left': 0
			}).animate({
				'left': pos
			}, duration, function () {
				from.node.remove();
			});
			
			to.node.css({
				'left': -pos
			}).animate({
				'left': 0
			}, duration, this.done);
			
			//Animate content
			if (this.options.style != 'arrows' && this.options.style != 'touch') {
				from.inner.animate({
					'margin-left': pos * 2
				}, duration);
				
				to.inner.css({
					'margin-left': -pos * 2
				}).animate({
					'margin-left': 0
				}, duration);
			}
			
			this.view.append(to.node);
		},
		'vertical': function (from, to, dir) {
			var height = this.height(),
				duration = this.options.duration,
				pos = dir == 1 ? -height : height;
			
			from.node.css({
				'top': 0
			}).animate({
				'top': pos
			}, duration, function () {
				from.node.remove();
			});
			
			to.node.css({
				'top': -pos
			}).animate({
				'top': 0
			}, duration, this.done);
			
			//Animate content
			if (this.options.style != 'arrows' && this.options.style != 'touch') {
				from.inner.animate({
					'margin-top': pos * 2
				}, duration);
				
				to.inner.css({
					'margin-top': -pos * 2
				}).animate({
					'margin-top': 0
				}, duration);
			}
			
			this.view.append(to.node);
		},
		'horizontal-fade': function (from, to, dir) {
			var self = this,
				width = this.width(),
				duration = this.options.duration,
				pos = dir == 1 ? -width : width;
			
			to.node.css({
				'opacity': 0
			}).animate({
				'opacity': 1
			}, duration, function () {
				from.node.remove();
				self.done();
			});
			
			//Animate content
			if (this.options.style != 'arrows' && this.options.style != 'touch') {
				from.inner.animate({
					'margin-left': pos * 2
				}, duration);
				
				to.inner.css({
					'margin-left': -pos * 2
				}).animate({
					'margin-left': 0
				}, duration);
			}
			
			this.view.append(to.node);
		},
		'fade': function (from, to, dir) {
			var self = this,
				duration = this.options.duration;
			
			to.node.css({
				'opacity': 0
			}).animate({
				'opacity': 1
			}, duration, function () {
				from.node.remove();
				self.done();
			});
			
			this.view.append(to.node);
		}
	};
	
	var NAVIGATION = {
		'arrows': {
			'setup': function (options) {
				var nodeNext = this.nodeNext = $('<a class="next"></a>').click($.proxy(this.next, this));
				var nodePrev = this.nodePrev = $('<a class="prev"></a>').click($.proxy(this.prev, this));
				
				this.host.container.append(nodeNext).append(nodePrev);
				
				if (!TOUCH_SUPPORTED) {
					this.show = $.proxy(this.show, this);
					this.hide = $.proxy(this.hide, this);
					this.host.container.hover(this.show, this.hide);
				} else {
					//If touch device, assument there is no mouse and show navigation arrows always
					this.nodeNext.css('opacity', 1);
					this.nodePrev.css('opacity', 1);
				}
			},
			'teardown': function () {
				this.nodeNext.remove();
				this.nodePrev.remove();
				this.nodeNext = null;
				this.nodePrev = null;
				this.host.container.off('mouseenter', this.show);
				this.host.container.off('mouseleave', this.hide);
			},
			'show': function () {
				this.nodeNext.stop().animate({'opacity': 1}, this.host.options.duration / 2);
				this.nodePrev.stop().animate({'opacity': 1}, this.host.options.duration / 2);
			},
			'hide': function () {
				this.nodeNext.stop().animate({'opacity': 0}, this.host.options.duration / 2);
				this.nodePrev.stop().animate({'opacity': 0}, this.host.options.duration / 2);
			},
			'next': function () {
				this.host.navigate(this.host.current.index + 1);
			},
			'prev': function () {
				this.host.navigate(this.host.current.index - 1);
			}
		},
		
		'dots': {
			'setup': function (options) {
				var node = this.node = $('<div class="nav"><div><div></div></div></div>'),
					inner = node.find('div div'),
					count = this.host.count,
					current = this.host.current.index,
					i = 0;
				
				for (; i<count; i++) {
					inner.append($('<a' + (i == current ? ' class="active"' : '') + '></a>'));
				}
				
				inner.find('a').click($.proxy(this.click, this));
				this.host.container.append(node);
				
				if (!TOUCH_SUPPORTED) {
					this.show = $.proxy(this.show, this);
					this.hide = $.proxy(this.hide, this);
					this.host.container.hover(this.show, this.hide);
				} else {
					this.node.css('opacity', 1);
				}
			},
			'teardown': function () {
				this.node.remove();
				this.host.container.off('mouseenter', this.show);
				this.host.container.off('mouseleave', this.hide);
			},
			'update': function (current) {
				this.node.find('a').removeClass('active').eq(current.index).addClass('active');
			},
			'show': function () {
				this.node.stop().animate({'opacity': 1}, this.host.options.duration / 2);
			},
			'hide': function () {
				this.node.stop().animate({'opacity': 0}, this.host.options.duration / 2);
			},
			'click': function (e) {
				this.host.navigate($(e.target).index());
			}
		},
		/*
		'pages': {
			'setup': function (options) {},
			'teardown': function () {},
			'apply': function (options) {},
			'update': function (current) {}
		},
		*/
		
		//
		'thumbnails': {
			'setup': function (options) {
				var node = this.node = $('<ul class="thumbs" />'),
					src = null,
					items = this.host.items,
					count = this.host.count,
					current = this.host.current.index,
					i = 0;
				
				for (; i<count; i++) {
					src = items.eq(i).find('img').attr('src');
					node.append($('<li><img src="' + src + '"</li>').css({'opacity': i == current ? 1 : 0.5}));
				}
				
				node.find('li').click($.proxy(this.click, this));
				node.find('li').hover(this.mouseenter, this.mouseleave);
				
				this.host.container.append(node);
			},
			'teardown': function () {
				this.node.remove();
			},
			'apply': function (options) {},
			'update': function (current, previous) {
				var items = this.node.find('li');
				items.eq(previous.index).stop().animate({'opacity': 0.5}).removeClass('active');
				items.eq(current.index).stop().animate({'opacity': 1}).addClass('active');
			},
			'mouseenter': function (e) {
				$(this).stop().animate({'opacity': 1});
			},
			'mouseleave': function (e) {
				if (!$(this).hasClass('active')) {
					$(this).stop().animate({'opacity': 0.5});
				}
			},
			'click': function (e) {
				this.host.navigate($(e.target).closest('li').index());
			}
		},
		
		//Touch events
		'touch': {
			'swipeStartX': 0,
			'swipeStartY': 0,
			'swipeEndX': 0,
			'swipeEndY': 0,
			'swipeRequiredDiff': 0,
			
			'setup': function (options) {
				if (!TOUCH_SUPPORTED) return false;
				
				this.swipeStart = $.proxy(this.swipeStart, this)
				this.swipeMove = $.proxy(this.swipeMove, this);
				this.swipeEnd = $.proxy(this.swipeEnd, this);
				this.host.container.on('touchstart', this.swipeStart);
			},
			'teardown': function () {
				this.host.container.off('touchstart', this.swipeStart);
			},
			'apply': function (options) {},
			'update': function (current) {},
			
			'swipeStart': function (e) {
				var input = this.getInputCoordinates(e);
				if (!input) return;
				
				this.swipeRequiredDiff = Math.min(200, this.host.width() / 3);
				this.swipeStartX = input[0];
				this.swipeStartY = input[1];
				
				$(document).on('touchend mouseup', this.swipeEnd);
				$(document).on('touchmove', this.swipeMove);
			},
			'swipeMove': function (e) {
				if (e.originalEvent.touches.length) {
					this.swipeEndX = e.originalEvent.touches[0].pageX;
					this.swipeEndY = e.originalEvent.touches[0].pageY;
				}
			},
			'swipeEnd': function (e) {
				var input = this.getInputCoordinates(e);
				
				var diffX = (input ? input[0] : this.swipeEndX) - this.swipeStartX,
					diffDir = diffX > 0 ? -1 : 1,
					diffY = Math.abs((input ? input[1] : this.swipeEndY) - this.swipeStartY);
				
				diffX = Math.abs(diffX);
				
				$(document).off('touchend mouseup', this.swipeEnd);
				$(document).off('touchmove', this.swipeEnd);
				
				if (diffX > diffY && diffX > this.swipeRequiredDiff) {
					this.host.navigate(this.host.current.index + diffDir);
					return false;
				}
			},
		},
		
		//Keyboard
		'keyboard': {
			'setup': function (options) {
				this.key = $.proxy(this.key, this);
				$(document).on('keydown', this.key);
			},
			'teardown': function () {
				$(document).off('keydown', this.key);
			},
			'key': function (e) {
				if (e.keyCode == 37) {
					this.host.navigate(this.host.current.index - 1);
				} else if (e.keyCode == 39) {
					this.host.navigate(this.host.current.index + 1);
				}
			}
		}
	};
	
	
	/**
	 * Promo image slider widget
	 * 
	 * @param {Object} element Container element, jQuery instance
	 * @
	 * @constructor
	 */
	var Promo = function (element, options) {
		this.navigation = {};
		this.optionsOriginal = options || {};
		this.options = $.extend({}, DEFAULTS, options || {});
		
		this.render(element);
		this.apply(this.options);
		this.handleResize();
	};
	
	Promo.prototype = {
		/**
		 * Promo container element, jQuery element
		 * @type {Object}
		 * @private
		 */
		'container': null,
		
		/**
		 * Item list element, jQuery element
		 * @type {Object}
		 * @private
		 */
		'list': null,
		
		/**
		 * Items, jQuery elements
		 * @type {Object}
		 * @private
		 */
		'items': null,
		
		/**
		 * Visible item element
		 * @type {Object}
		 * @private
		 */
		'view': null,
		
		
		/**
		 * All options
		 * @type {Object}
		 * @private
		 */
		'options': null,
		
		/**
		 * Original options
		 * @type {Object}
		 * @private
		 */
		'optionsOriginal': null,
		
		/**
		 * Animation name
		 * @type {String}
		 * @private
		 */
		'animation': null,
		
		/**
		 * Browser size
		 * @type {Number}
		 * @private
		 */
		'size': SIZE_DESKTOP,
		
		/**
		 * Navigation objects
		 * @type {Object}
		 * @private
		 */
		'navigation': null,
		
		/**
		 * Current image object: index and node
		 * @type {Object}
		 * @private
		 */
		'current': null,
		
		/**
		 * Image count
		 * @type {Number}
		 * @private
		 */
		'count': null,
		
		/**
		 * Animation in progress
		 * @type {Boolean}
		 * @private
		 */
		'animating': false,
		
		
		/**
		 * Navigate
		 * 
		 * @param {Number} index Image index to which navigate to
		 */
		'navigate': function (index) {
			if (this.animating) return;
			this.animating = true;
			
			var from = this.current,
				to   = null,
				
				count = this.count,
				items = this.items,
				item  = null,
				dir   = from.index < index ? 1 : -1,
				
				animation = this.animation;
			
			if (index < 0) index = count + index;
			if (index >= count) index = index - count;
			
			if (index != from.index) {
				item = items.eq(index).clone(true, true);
				
				this.current = to = {
					'index': index,
					'node': item,
					'inner': item.find('div')
				};
				
				//Animate images
				TRANSITIONS[animation].call(this, from, to, dir);
				
				//Animate container
				this.view.animate({
					//'width': items.eq(index).outerWidth(true),
					'height': this.itemHeight(index)
				}, this.options.duration);
				
				//Update navigation UI to match current state
				this.updateNavigation(to, from);
			}
		},
		
		/**
		 * Animation complete callback
		 * 
		 * @private
		 */
		'done': function () {
			this.animating = false;
		},
		
		/**
		 * Render gallery
		 */
		'render': function (element) {
			var container = null,
				list = null,
				items = null,
				current = null,
				view = null,
				item = null;
				
			//Proxy for convenience
			this.done = $.proxy(this.done, this);
			
			//Container and list elements
			if (element.is(this.options.list)) {
				this.list = list = element;
				this.container = container = $('<div class="' + this.options.classname + '" />');
				list.wrap(container);
			} else {
				this.container = container = element;
				this.list = list = container.find(this.options.list);
			}
			
			list.addClass(this.options.classname + '-list');
			
			//Style
			if (!this.options.style || !STYLES[this.options.style]) {
				var styles = STYLES,
					key = null;
				
				for (key in styles) {
					if (element.hasClass(key)) {
						this.options.style = key;
						break;
					}
				}
			}
			
			if (this.options.style) {
				$.extend(this.options, STYLES[this.options.style] || {}, this.optionsOriginal);
				container.addClass(this.options.style);
			}
			
			//View and items
			this.items = items = list.find(this.options.items).not('.clear');
			this.count = items.size();
			this.view  = view = $('<' + this.options.list + ' class="' + this.options.classname + '-view ' + this.options.classname + '-list" />');
			
			item = items.eq(0).clone(true, true);
			current = this.current = {
				'index': 0,
				'node': item,
				'inner': item.find('div')
			};
			
			list.hide();
			view.append(current.node);
			container.prepend(view);
			
			//Resize view
			view.css({
				//'width': items.eq(0).outerWidth(true),
				'height': this.itemHeight(0)
			});
			
			//On browser resize update
			this.handleResize = $.proxy(this.handleResize, this);
			$(window).resize($.throttle(this.handleResize));
		},
		
		/**
		 * Apply options
		 */
		'apply': function (options) {
			if (typeof options === 'object') {
				
				//Style
				if (options.style && options.style != this.options.style) {
					$.extend(options, STYLES[options.style] || {});
					
					this.container.removeClass(this.options.style);
					this.container.addClass(options.style);
					
					//Update view height
					this.view.css({
						//'width': items.eq(index).outerWidth(true),
						'height': this.itemHeight(this.current.index)
					});
				}
				
				//
				$.extend(this.options, options);
				
				//Animation
				this.animation = this.options.animation = this.options.animation || 'horizontal';
				
				//Navigation
				for (var key in options) {
					if (key in NAVIGATION) {
						this.applyNavigation(key, options[key]);
					}
				}
			}
		},
		
		/**
		 * On resize update options
		 */
		'applyResize': function (size) {
			switch (size) {
				case SIZE_DESKTOP:
					this.apply({'style': this.optionsOriginal.style || 'horizontal'});
					break;
				case SIZE_TABLET:
					this.apply({'style': this.optionsOriginal.style || 'horizontal'});
					break;
				case SIZE_MOBILE_LANDSCAPE:
					this.apply({'style': 'touch'});
					break;
				case SIZE_MOBILE_PORTRAIT:
					this.apply({'style': 'touch'});
					break;
			}
			
			//Resize view
			this.view.css({
				//'width': items.eq(0).outerWidth(true),
				'height': this.itemHeight(0)
			});
		},
		
		/**
		 * Handle browser resize
		 * 
		 * @private
		 */
		'handleResize': function () {
			var width = $(window).width(),
				size = null;
			
			if (width >= 960) {
				size = SIZE_DESKTOP;
			} else if (width >= 768 && width <= 959) {
				size = SIZE_TABLET;
			} else if (width >= 480 && width <= 767) {
				size = SIZE_MOBILE_LANDSCAPE;
			} else if (width <= 479) {
				size = SIZE_MOBILE_PORTRAIT;
			}
			
			if (size != this.size) {
				this.size = size;
				this.applyResize(size);
			}
		},
		
		/**
		 * Apply navigation options
		 * 
		 * @param {String} name Navigation name
		 * @param {Object} options Falsy value to disable, any other to enable
		 * @private
		 */
		'applyNavigation': function (name, options) {
			if (!NAVIGATION[name]) return;
			var nav = this.navigation[name];
			
			if (!nav && options) {
				nav = $.extend({'host': this}, NAVIGATION[name]);
				
				if (nav.setup(options) !== false) {
					this.navigation[name] = nav;
				}
			} else if (nav && (options === undefined || options === null || options === false)) {
				nav.teardown();
				delete(this.navigation[name]);
			} else if (nav && options) {
				if (nav.apply) {
					nav.apply(options);
				}
			}
		},
		
		/**
		 * Update all navigation types
		 * 
		 * @private
		 */
		'updateNavigation': function (to, from) {
			var navigation = this.navigation,
				key = null,
				current = this.current;
			
			for (key in navigation) {
				if (navigation[key].update) navigation[key].update(to, from);
			}
		},
		
		/**
		 * Returns view width
		 * 
		 * @return View width
		 * @type {Number}
		 */
		'width': function () {
			return this.view.width();
		},
		
		/**
		 * Returns view height
		 * 
		 * @return View height
		 * @type {Number}
		 */
		'height': function () {
			return this.view.height();
		},
		
		/**
		 * Calculates item height
		 * 
		 * @return Item height
		 * @type {Number}
		 */
		'itemHeight': function (index) {
			var height = 0;
			
			this.list.show();
			height = this.items.eq(index).outerHeight(true);
			this.list.hide();
			
			return height;
		},
		
		/**
		 * Normalizes input for touch and mouse
		 * 
		 * @param {Event} e
		 * @private
		 */
		'getInputCoordinates': function (e) {
			var x = e.clientX,
				y = e.clientY;
			
			if (!x && !y) {
				if (e.originalEvent.touches && e.originalEvent.touches.length == 1) {
					x = e.originalEvent.touches[0].pageX;
					y = e.originalEvent.touches[0].pageY;
				} else {
					//Swipe can be done only with one finger
					return null;
				}
			}
			
			return [x, y];
		},
		
		/**
		 * Destructor
		 */
		'destroy': function () {
			var navigation = this.navigation,
				key = null;
			
			for (key in navigation) {
				navigation[key].teardown();
			}
			
			this.navigation = null;
		}
		
	};
	
	/*
	 * jQuery namespace
	 */
	$.promo = {
		'constructor': Promo,
		'defaults': DEFAULTS,
		'transitions': TRANSITIONS,
		'navigation': NAVIGATION
	};
	
	/*
	 * jQuery plugin
	 * Create widget or apply options or call a function
	 */
	$.fn.promo = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'function' ? fn : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Promo (element, $.extend({}, element.data(), options || {}));
				window.w = widget;
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else {
				if (fn) {
					widget[fn].call(widget);
				} else if (options) {
					widget.apply(options);
				}
			}
		});
	};
	
	//$.refresh implementation
	$.refresh.on('refresh/promo', function (event, info) {
		info.target.promo(info.target.data());
	});
	
	$.refresh.on('cleanup/promo', function (event, info) {
		var promo = info.target.data(DATA_INSTANCE_PROPERTY);
		if (promo) {
			promo.destroy();
			info.target.data(DATA_INSTANCE_PROPERTY, null)
		}
	});
	
	$.refresh.on('update/promo', function (event, info) {
		var promo = info.target.data(DATA_INSTANCE_PROPERTY);
		if (promo && info.propertyName) {
			var options = {},
				old_group = null,
				new_group = null;
			
			if (info.propertyName === 'design') {
				//Style exists?
				if (!STYLES[info.propertyValue]) return false;
				
				options['style'] = info.propertyValue;
				
			} else if (info.propertyName === 'animation') {
				options[info.propertyName] = info.propertyValue;
			} else {
				return;
			}
			
			promo.apply(options);
		}
	});
	
	/**
	 * Throttle handles call frequency to callback, to avoid
	 * callback beeing called more often than 'threshold' milliseconds
	 * 
	 * @param {Function} callback
	 * @param {Number} threshold
	 * @return Throttled function
	 * @type {Function}
	 */
	$.throttle = function (callback, context, threshold) {
		if (typeof context === 'number') {
			threshold = context;
			context = null;
		}
		
		var threshold = threshold || 50;
		var last_time = 0;
		var timeout = null;
		var args = [];
		
		function call () {
			callback.apply(context || window, args);
			last_time = +new Date();
			clearTimeout(timeout);
			timeout = null;
		}
		
		return function () {
			//Save arguments
			args = [].slice.call(arguments, 0);
			
			if ((+new Date()) - last_time > threshold) {
				call();
			} else if (!timeout) {
				timeout = setTimeout(call, threshold);
			}
		};
		
	};
	
})(jQuery);
