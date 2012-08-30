(function ($) {
	"use strict";
	
	//Sizes
	var SIZE_DESKTOP = 1,
		SIZE_TABLET = 2,
		SIZE_MOBILE_LANDSCAPE = 3,
		SIZE_MOBILE_PORTRAIT = 4;
	
	
	//Touch event support
	var TOUCH_SUPPORTED = true; //('ontouchstart' in document.documentElement);
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'carousel';
	
	//Styles:
	//simple, border, box
	var STYLES = {
		// Style group represents that these styles can be changed without HTML changes
		// on refresh event "update" we check if style can be changed
		'simple':	{'style-group': 1},
		'border':	{'style-group': 1},
		'box': 		{'style-group': 2}
	};
	
	//Navigation:
	//arrows, arrows_pages, scrollbar
	
	//Animations:
	//- horizontal (when one of the navigation options are enabled)
	//- none (when all navigation options are disabled)
	
	//Default widget options
	var DEFAULTS = {
		'arrows': false,
		'arrows_pages': false,
		'scrollbar': false,
		
		'touch': true,
		'keyboard': !($('html').hasClass('supra-cms')), // In CMS mode we are editing text
		
		'timer': 5000,
		
		'animation': 'horizontal',
		'duration': 400,
		
		'list': 'ul',
		'items': 'li',
		'classname': 'products-slider',
		
		'style': null
	};
	
	var TRANSITIONS = {
		//Horizontal scroll
		'horizontal': function (from, to, dir, quick) {
			var width = this.width(),
				duration = this.options.duration,
				pos = - to * width;
			
			if (quick) {
				this.list.css({
					'left': pos
				});
				this.done();
			} else {
				this.list.animate({
					'left': pos
				}, duration, this.done);	
			}
		},
		//No animation
		'none': function () {
			this.done();
		}
	};
	
	//Default syle
	var DEFAULT_STYLE = 'simple';
	
	var NAVIGATION = {
		'arrows': {
			'setup': function (options) {
				var node = this.node = $('<div class="nav nav-arrows"><a class="prev"></a><a class="next"></a></div>');
				var nodeNext = this.nodeNext = node.find('.next').click($.proxy(this.next, this));
				var nodePrev = this.nodePrev = node.find('.prev').click($.proxy(this.prev, this));
				
				this.host.container.append(node);
			},
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.nodeNext = null;
				this.nodePrev = null;
			},
			'next': function () {
				this.host.navigate(this.host.page + 1);
			},
			'prev': function () {
				this.host.navigate(this.host.page - 1);
			}
		},
		
		'arrows_pages': {
			'setup': function (options) {
				var node = this.node = $('<div class="nav nav-arrows-pages"><div><a class="next"></a><a class="prev"></a>Page <span>' + (this.host.page + 1) + '</span> / <b>' + this.host.pages + '</div></div>');
				var nodeNext = this.nodeNext = node.find('.next').click($.proxy(this.next, this));
				var nodePrev = this.nodePrev = node.find('.prev').click($.proxy(this.prev, this));
				
				this.host.container.append(node);
			},
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.nodeNext = null;
				this.nodePrev = null;
			},
			'next': function () {
				if (!this.nodeNext.hasClass('disabled')) {
					this.host.navigate(this.host.page + 1);
				}
			},
			'prev': function () {
				if (!this.nodePrev.hasClass('disabled')) {
					this.host.navigate(this.host.page - 1);
				}
			},
			'update': function (current, previous) {
				if (current === 0) {
					this.nodePrev.addClass('disabled');
				} else {
					this.nodePrev.removeClass('disabled');
				}
				if (current === this.host.pages - 1) {
					this.nodeNext.addClass('disabled');
				} else {
					this.nodeNext.removeClass('disabled');
				}
				
				this.node.find('span').text(current + 1);
				this.node.find('b').text(this.host.pages);
			}
		},
		
		'scrollbar': {
			'setup': function (options) {
				var node = this.node = $('<div class="nav nav-scrollbar"><a></a></div>'),
					draggable = this.draggable = node.find('a');
				
				draggable.css('width', 100 / this.host.pages + '%').draggable({
					containment: 'parent',
					drag: $.proxy(this.drag, this)
				});
				
				this.host.container.append(node);
			},
			'drag': function (e, ui) {
				var percent = ui.position.left / (this.node.width() - this.draggable.width()),
					offset = -percent * (this.host.contentWidth() - this.host.width());
				
				this.host.list.css({'left': offset});
			},
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.dragable = null;
				this.host.list.css({'left': 0});
			},
			'update': function (current, previous) {
				var h_w = this.host.width(),
					h_c_w = this.host.contentWidth(),
					percent = current / this.host.pages,
					offset = -percent * (h_c_w - h_w);
				
				this.draggable.css({
					'left': (this.node.width() - this.draggable.width()) * percent + 'px',
					'width': (h_w / h_c_w * 100) + '%'
				});
				
				this.host.list.css({'left': offset});
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
					this.host.navigate(this.host.page + diffDir);
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
					this.host.navigate(this.host.page - 1);
				} else if (e.keyCode == 39) {
					this.host.navigate(this.host.page + 1);
				}
			}
		}
	};
	
	
	/**
	 * Carousel image slider widget
	 * 
	 * @param {Object} element Container element, jQuery instance
	 * @
	 * @constructor
	 */
	var Carousel = function (element, options) {
		this.navigation = {};
		this.optionsOriginal = options || {};
		this.options = $.extend({}, DEFAULTS, options || {});
		
		this.render(element);
		this.apply(this.options);
		this.handleResize();
	};
	
	Carousel.prototype = {
		/**
		 * Carousel container element, jQuery element
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
		 * All options
		 * @type {Object}
		 * @private
		 */
		'options': null,
		
		/**
		 * Original options, used after resize
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
		 * Current page
		 * @type {Number}
		 * @private
		 */
		'page': 0,
		
		/**
		 * Page count
		 * @type {Number}
		 * @private
		 */
		'pages': 0,
		
		/**
		 * Items per view
		 * @type {Number}
		 * @private
		 */
		'perView': null,
		
		/**
		 * Animation in progress
		 * @type {Boolean}
		 * @private
		 */
		'animating': false,
		
		/**
		 * If there are any active navigation options
		 * @type {Boolean}
		 * @private
		 */
		'hasNavigation': false,
		
		/**
		 * Width cache
		 * @type {Number}
		 * @private
		 */
		'widthCache': null,
		
		/**
		 * Content width cache
		 * @type {Number}
		 * @private
		 */
		'contentWidthCache': null,
		
		/**
		 * Navigate
		 * 
		 * @param {Number} index Image index to which navigate to
		 */
		'navigate': function (index, quick) {
			if (this.animating || !this.hasNavigation) return;
			this.animating = true;
			
			var from = this.page,
				to   = null,
				
				count = this.pages,
				items = this.items,
				item  = null,
				dir   = from.index < index ? 1 : -1,
				
				animation = this.animation;
			
			if (index < 0) index = count + index;
			if (index >= count) index = index - count;
			
			if (index != from || quick) {
				item = items.eq(index);
				
				this.page = to = index;
				this.current = {
					'index': index * this.perView,
					'node': item
				};
				
				if (quick) {
					//Resize container
					this.list.css({
						'height': this.pageHeight(index)
					});
				} else {
					//Animate container
					this.list.animate({
						'height': this.pageHeight(index)
					}, {
						'queue': false,
						'duration': this.options.duration
					});
				}
				
				//Animate images
				TRANSITIONS[animation].call(this, from, to, dir, quick);
				
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
			if (!this.options.style) {
				var styles = STYLES,
					key = null;
				
				for (key in styles) {
					if (element.hasClass('style-' + key)) {
						this.options.style = key;
						break;
					}
				}
				
				if (!this.options.style) {
					this.options.style = DEFAULT_STYLE;
				}
			}
			
			if (this.options.style) {
				container.addClass('style-' + this.options.style);
			}
			
			//View and items
			this.items = items = list.find(this.options.items).not('.clear');
			this.count = items.size();
			
			item = items.eq(0);
			this.current = {
				'index': 0,
				'node': item
			};
			
			this.calculateItemsPerView();
			
			this.page = 0;
			this.pages = Math.ceil(this.count / this.perView);
			
			if (this.hasNavigation) {
				//Resize view
				list.css({
					//'width': items.eq(0).outerWidth(true),
					'height': this.pageHeight(this.page)
				});
			}
			
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
					this.container.removeClass('style-' + this.options.style);
					this.container.addClass('style-' + options.style);
				}
				
				//
				$.extend(this.options, options);
				
				//Animation, depends on navigation
				if (this.options.arrows || this.options.arrows_pages || this.options.scrollbar) {
					if (!this.hasNavigation) {
						this.container.removeClass('animation-none');
						this.container.addClass('animation-horizontal');
						
						this.animation = 'horizontal';
						this.hasNavigation = true;
						
						//Update view height
						this.list.css({
							'height': this.pageHeight(this.page)
						});
					}
				} else {
					if (this.hasNavigation) {
						this.container.removeClass('animation-horizontal');
						this.container.addClass('animation-none');
						
						this.animation = 'none';
						this.hasNavigation = false;
						
						//Update view height
						this.list.css({
							'height': 'auto'
						});
					}
				}
				
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
			//Reset cache
			this.contentWidthCache = null;
			this.widthCache = null;
			
			switch (size) {
				case SIZE_DESKTOP:
					this.apply({
						'arrows': this.optionsOriginal.arrows,
						'arrows_pages': this.optionsOriginal.arrows_pages,
						'scrollbar': this.optionsOriginal.scrollbar
					});
					break;
				case SIZE_TABLET:
					this.apply({
						'arrows': this.optionsOriginal.arrows,
						'arrows_pages': this.optionsOriginal.arrows_pages,
						'scrollbar': this.optionsOriginal.scrollbar
					});
					break;
				case SIZE_MOBILE_LANDSCAPE:
					if (this.optionsOriginal.arrows_pages || this.optionsOriginal.scrollbar) {
						this.apply({'arrows': true, 'arrows_pages': false, 'scrollbar': false});
					}
					break;
				case SIZE_MOBILE_PORTRAIT:
					if (this.optionsOriginal.arrows_pages || this.optionsOriginal.scrollbar) {
						this.apply({'arrows': true, 'arrows_pages': false, 'scrollbar': false});
					}
					break;
			}
			
			if (this.hasNavigation) {
				if (!this.calculateItemsPerView()) {
					this.list.css({
						'height': this.pageHeight(this.page)
					});
				}
			}
			
			//
			if (this.options.style === 'box') {
				//this.items.find('a.outer')
			}
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
		 * Calculate items per view
		 * @private
		 */
		'calculateItemsPerView': function () {
			//Update items per view count
			var container_width = this.width(),
				item_width      = this.itemWidth(0),
				list_width		= this.container.width(),
				per_view        = ~~(list_width / item_width),
				page			= this.page;
			
			if (this.perView != per_view) {
				this.perView = per_view;
				this.pages = Math.ceil(this.count / per_view);
				
				page = Math.min(this.pages - 1, ~~(this.current.index / per_view));
				
				this.navigate(0, true);
				return true;
			}
			
			return false;
		},
		
		/**
		 * Update all navigation types
		 * 
		 * @private
		 */
		'updateNavigation': function (to, from) {
			var navigation = this.navigation,
				key = null;
			
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
			if (this.widthCache) return this.widthCache;
			return this.widthCache = this.container.width();
		},
		
		/**
		 * Returns view height
		 * 
		 * @return View height
		 * @type {Number}
		 */
		'height': function () {
			return this.container.height();
		},
		
		/**
		 * Calculates item height
		 * 
		 * @return Item height
		 * @type {Number}
		 */
		'itemHeight': function (index) {
			return this.items.eq(index).outerHeight(true);
		},
		
		/**
		 * Calculates item width
		 * 
		 * @return Item height
		 * @type {Number}
		 */
		'itemWidth': function (index) {
			return this.items.eq(index).outerWidth(true);
		},
		
		/**
		 * Calculates page height
		 * 
		 * @return Page height
		 * @type {Number}
		 */
		'pageHeight': function (index) {
			var i = index * this.perView,
				ii = (index + 1) * this.perView,
				items = this.items,
				height = 0;
			
			for(; i<ii; i++) {
				height = Math.max(height, items.eq(i).outerHeight(true));
			}
			
			return height;
		},
		
		/**
		 * Returns content width
		 * 
		 * @return Content width
		 * @type {Number}
		 */
		'contentWidth': function () {
			if (this.contentWidthCache) return this.contentWidthCache;
			
			var sum = 0,
				items = this.items,
				i = 0,
				ii = items.length;
			
			for (; i<ii; i++) {
				sum += items.eq(i).outerWidth(true);
			}
			
			this.contentWidthCache = sum;
			return sum; 
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
	$.carousel = {
		'constructor': Carousel,
		'defaults': DEFAULTS,
		'transitions': TRANSITIONS,
		'navigation': NAVIGATION
	};
	
	/*
	 * jQuery plugin
	 * Create widget or apply options or call a function
	 */
	$.fn.carousel = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'function' ? fn : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Carousel (element, $.extend({}, element.data(), options || {}));
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
	$.refresh.on('refresh/carousel', function (event, info) {
		info.target.carousel(info.target.data());
	});
	
	$.refresh.on('cleanup/carousel', function (event, info) {
		var carousel = info.target.data(DATA_INSTANCE_PROPERTY);
		if (carousel) {
			carousel.destroy();
			info.target.data(DATA_INSTANCE_PROPERTY, null)
		}
	});
	
	$.refresh.on('update/carousel', function (event, info) {
		var carousel = info.target.data(DATA_INSTANCE_PROPERTY);
		if (carousel && info.propertyName) {
			var options = {},
				old_group = null,
				new_group = null;
			
			if (info.propertyName === 'design') {
				//Style exists?
				if (!STYLES[info.propertyValue]) return false;
				
				//Same group?
				old_group = STYLES[carousel.options.style]['style-group'];
				new_group = STYLES[info.propertyValue]['style-group'];
				
				if (old_group != new_group) {
					//New and old style HTML is too different and style can't be changed
					return false;
				}
				
				options['style'] = info.propertyValue;
				
			} else if (info.propertyName === 'navigation') {
				options['arrows'] = false;
				options['arrows_pages'] = false;
				options['scrollbar'] = false;
				
				if (info.propertyValue in NAVIGATION) {
					options[info.propertyValue] = true;
				} else {
					//Navigation disabled
				}
			} else {
				//Unknown property
				return;
			}
			
			carousel.apply(options);
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
