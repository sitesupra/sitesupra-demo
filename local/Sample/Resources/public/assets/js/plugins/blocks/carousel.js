/**
 * Carousel block
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/refresh', 'plugins/helpers/responsive', 'plugins/helpers/touchdrag', 'plugins/helpers/throttle'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	//Sizes
	var SIZE_DESKTOP = 1,
		SIZE_TABLET = 2,
		SIZE_MOBILE_LANDSCAPE = 3,
		SIZE_MOBILE_PORTRAIT = 4;
	
	//Instance unique id
	var UID = 1;
	
	//Touch event support
	var TOUCH_SUPPORTED = ('ontouchstart' in document.documentElement);
	
	//Click event attachment
	var EVENT_CLICK = (TOUCH_SUPPORTED ? 'touchend' : 'click');
	
	//Scroll unit
	var SCROLL_UNIT = (function () {
		// On IOS and Android, all browsers round numbers down incorrectly causing problems
		// with percentage scroll position
		if (navigator.userAgent.match(/(iPad|iPhone|iPod|Android)/g)) {
			return 'px';
		}
		
		return '%';
	})();
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'carousel';
	
	//Styles:
	//simple, border, box
	var STYLES = {
		// Style group represents that these styles can be changed without HTML changes
		// on refresh event "update" we check if style can be changed
		
	};
	
	//Default syle
	var DEFAULT_STYLE = '';
	
	//Navigation:
	//arrows, arrows_pages, scrollbar, touch
	
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
		
		'timer': 0,
		
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
				pos = 0;
			
			if (SCROLL_UNIT == '%') {
				pos = -to * this.listWidthCache / this.pages;
				
				// Round to 6 digits for Safari
				pos = pos.toFixed(6) + '%';
			} else {
				// Sum width of all items before current
				this.items.slice(0, to * this.perView).each(function () {
					pos -= $(this).outerWidth(true);
				});
				
				// Round
				pos = Math.round(pos) + 'px';
			}
			
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
		//Fade
		'fade': function (from, to, dir, quick) {
			var items = this.items,
				item_from = items.eq(from),
				item_to = items.eq(to),
				duration = this.options.duration,
				width = 0;
			
			if (quick) {
				item_from.css('display', 'none');
				item_to.css('display', 'block');
				this.done();
			} else {
				this.list.css('position', 'relative');
				
				width = this.width();
				item_from.css({'position': 'relative', 'z-index': 0, 'opacity': 1});
				item_to.css({'position': 'absolute', 'display': 'block', 'z-index': 1, 'width': width, 'opacity': 0, 'left': 0, 'top': 0});
				
				item_from.animate({
					'opacity': 0
				}, duration);
				item_to.animate({
					'opacity': 1
				}, duration, $.proxy(function () {
					item_from.stop(true).css({'display': 'none'});
					item_to.css({'width': 'auto', 'position': 'relative'});
					this.done();
				}, this));
			}
		},
		//No animation
		'none': function () {
			this.done();
		}
	};
	
	var NAVIGATION = {
		'arrows': {
			'setup': function (options) {
				var node = this.node = $('<div class="nav nav-arrows"><a class="prev"></a><a class="next"></a></div>');
				var nodeNext = this.nodeNext = node.find('.next').on(EVENT_CLICK, $.proxy(this.next, this));
				var nodePrev = this.nodePrev = node.find('.prev').on(EVENT_CLICK, $.proxy(this.prev, this));
				
				this.host.container.append(node);
			},
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.nodeNext = null;
				this.nodePrev = null;
			},
			'next': function (e) {
				if (!this.nodeNext.hasClass('disabled')) {
					this.host.navigate(this.host.page + 1);
				}
				if (e) {
					e.preventDefault();
				}
			},
			'prev': function (e) {
				if (!this.nodePrev.hasClass('disabled')) {
					this.host.navigate(this.host.page - 1);
				}
				if (e) {
					e.preventDefault();
				}
			},
			'update': function (current, previous) {
				if (this.host.animation == 'horizontal') {
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
				} else {
					this.nodePrev.removeClass('disabled');
					this.nodeNext.removeClass('disabled');
				}
			}
		},
		
		'arrows_pages': {
			'setup': function (options) {
				var node = this.node = $('<div class="nav nav-arrows-pages"><div><span>' + (this.host.page + 1) + '</span> / <b>' + this.host.pages + '</b><a class="prev"></a><a class="next"></a></div></div>');
				var nodeNext = this.nodeNext = node.find('.next').on(EVENT_CLICK, $.proxy(this.next, this));
				var nodePrev = this.nodePrev = node.find('.prev').on(EVENT_CLICK, $.proxy(this.prev, this));
				
				this.host.container.append(node);
			},
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.nodeNext = null;
				this.nodePrev = null;
			},
			'next': function (e) {
				if (!this.nodeNext.hasClass('disabled')) {
					this.host.navigate(this.host.page + 1);
				}
				if (e) {
					e.preventDefault();
				}
			},
			'prev': function (e) {
				if (!this.nodePrev.hasClass('disabled')) {
					this.host.navigate(this.host.page - 1);
				}
				if (e) {
					e.preventDefault();
				}
			},
			'update': function (current, previous) {
				$(this.node).find('span').text(current + 1);
				$(this.node).find('b').text(this.host.pages);
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
			}
		},
		
		'scrollbar': {
			'setup': function (options) {
				var node = this.node = $('<div class="nav nav-scrollbar scrollbar scrollbar-x"><div class="scrollbar-t"><div class="scrollbar-b"></div></div><a class="scrollbar-dragable"></a></div>'),
					draggable = this.draggable = node.find('a');
				
				draggable.css('width', 100 / this.host.pages + '%'); /*.draggable({
					containment: 'parent',
					drag: $.proxy(this.drag, this)
				});*/
				
				draggable.touchdrag({
					touchDelay: 0,
					touchDistance: 0
				})
						 .on('drag-start', $.proxy(this.start, this))
						 .on('drag-move', $.proxy(this.move, this))
						 .on('drag-end', $.proxy(this.end,this));
				
				this.host.container.append(node);
			},
			
			'start': function (e) {
				var node = this.node,
					draggable = this.draggable,
					container = node.parent();
				
				// Style
				node.addClass('scrollbar-focus');
				
				// Save constrains
				e.memory.draggableWidth = draggable.width();
				e.memory.containerWidth = container.width();
				e.memory.offset = draggable.position().left;
				e.memory.contentWidth = (this.host.contentWidth() - this.host.width() - this.host.margin());
				
				if (!e.touch) {
					// If mouse event then we need to stop from selecting text
					e.preventDefault();
				}
			},
			'move': function (e) {
				var constrain = e.memory.containerWidth - e.memory.draggableWidth,
					percent = Math.min(constrain, Math.max(0, e.memory.offset + e.delta[0])) / constrain,
					draggable_offset = Math.round(percent * constrain),
					content_offset = -percent * e.memory.contentWidth;
				
				this.draggable.css({'left': draggable_offset});
				this.host.list.css({'left': content_offset});
			},
			'end': function (e) {
				// Style
				this.node.removeClass('scrollbar-focus');
			},
			
			'drag': function (e, ui) {
				var percent = ui.position.left / (this.node.width() - this.draggable.width()),
					offset = -percent * (this.host.contentWidth() - this.host.width() - this.host.margin());
				
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
				if (!TOUCH_SUPPORTED || this.host.options.scrollbar) return false;
				
				this.swipeStart = $.proxy(this.swipeStart, this);
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
				// Swipe should be ignore on thumbs and other navigation
				if ($(e.target).closest('.thumbs, .nav-arrows, .nav').size()) return;
				
				var input = this.host.getInputCoordinates(e);
				if (!input) return;
				
				this.swipeRequiredDiff = Math.min(200, this.host.width() / 5);
				
				this.swipeStartX = input[0];
				this.swipeStartY = input[1];
				
				$(document).on('touchend.' + this.ns + ' mouseup.' + this.ns, this.swipeEnd);
				$(document).on('touchmove.' + this.ns, this.swipeMove);
			},
			'swipeMove': function (e) {
				if (e.originalEvent.touches.length) {
					this.swipeEndX = e.originalEvent.touches[0].pageX;
					this.swipeEndY = e.originalEvent.touches[0].pageY;
				}
			},
			'swipeEnd': function (e) {
				var input = this.host.getInputCoordinates(e);
				
				var diffX = (input ? input[0] : this.swipeEndX) - this.swipeStartX,
					diffDir = diffX > 0 ? -1 : 1,
					diffY = Math.abs((input ? input[1] : this.swipeEndY) - this.swipeStartY);
				
				diffX = Math.abs(diffX);
				
				$(document).off('touchend.' + this.ns + ' mouseup.' + this.ns, this.swipeEnd);
				$(document).off('touchmove.' + this.ns, this.swipeEnd);
				
				if (diffX > diffY && diffX > this.swipeRequiredDiff) {
					this.host.navigate(this.host.page + diffDir);
					return false;
				}
			}
		},
		
		//Keyboard
		'keyboard': {
			'setup': function (options) {
				this.key = $.proxy(this.key, this);
				$(document).on('keydown.' + this.ns, this.key);
			},
			'teardown': function () {
				$(document).off('keydown.' + this.ns, this.key);
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
		this.ns = 'carousel-' + (UID++);
		this.navigation = {};
		this.optionsOriginal = options || {};
		this.options = $.extend({}, DEFAULTS, options || {});
		
		this.render(element);
		this.apply(this.options);
		this.applyResize({}, $.responsive.size);
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
		 * List width cache in %
		 * @type {Number}
		 * @private
		 */
		'listWidthCache': null,
		
		/**
		 * Content width cache
		 * @type {Number}
		 * @private
		 */
		'contentWidthCache': null,
		
		/**
		 * Timer timeout handle
		 * @type {Number}
		 * @private
		 */
		'timerHandle': null,
		
		/**
		 * Unique namespace ID for this instance of carousel
		 * Used when binding namspaced events
		 * @type {String}
		 * @private
		 */
		'ns': null,
		
		
		
		/**
		 * Navigate
		 * 
		 * @param {Number} index Page index to which navigate to
		 */
		'navigate': function (index, quick) {
			if (this.animating) return;
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
					'index': index,
					'node': item
				};
				
				//Update content height
				this.updateContentHeight(index, quick);
				
				//Animate pages
				if (TRANSITIONS[animation]) {
					TRANSITIONS[animation].call(this, from, to, dir, quick);
				} else {
					this.done();
				}
				
				//Update navigation UI to match current state
				this.updateNavigation(to, from);
				
				//Restart timer
				this.start(true);
			} else {
				//Page is already opened
				this.done();
			}
		},
		
		/**
		 * Navigate to next item
		 * 
		 * @private
		 */
		'navigateNext': function () {
			this.navigate(this.page + 1);
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
			
			if (this.options.classname) {
				list.addClass(this.options.classname + '-list');
			}
			
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
				this.updateContentHeight(this.page, true);
			}
			
			//On browser resize update
			$.responsive.on('resize', $.proxy(this.applyResize, this));
			$(window).on('resize', $.throttle(this.applyResizeWindow, this, 100, true));
		},
		
		/**
		 * Because of the multiple slides we need to change list width to fit
		 * everything
		 * 
		 * @private
		 */
		'fixSizes': function () {
			if (this.options.animation == 'horizontal') {
				// Reset custom styles
				var items = this.items,
					list  = this.list,
					count = this.count,
					
					size  = 0,
					sizes_sum_in_view = 0,
					i = 0;
				
				list.css('width', '');
				items.css('width', '');
				
				// Find sum of all item in first page
				var css        = list.css(['marginLeft', 'marginRight']),
					margins    = [parseInt(css.marginLeft, 10), parseInt(css.marginRight, 10)],
					list_width = list.outerWidth(true) - margins[0] - margins[1],
					per_view   = 0,
					page_count = 0,
					
					list_width_perc = 0,
					item_width_perc = 0,
					margin_perc     = 0;
				
				for (; i<count; i++) {
					size = items.eq(i).outerWidth(true);
					
					if (size + sizes_sum_in_view <= list_width) {
						sizes_sum_in_view += size;
						per_view++;
					} else {
						break;
					}
				}
				
				page_count = Math.ceil(count / per_view);
				
				if (page_count > 1) {
					margin_perc     = (-margins[0] - margins[1]) / list_width * 100;
					list_width_perc = page_count * 100 + margin_perc * page_count;
					item_width_perc = 100 / (page_count * per_view);
					
					this.listWidthCache = list_width_perc;
					
					// It seems that on Safari only 6 digits are taken into account
					// reset are cut off, so we round it manually
					list_width_perc = list_width_perc.toFixed(6);
					item_width_perc = item_width_perc.toFixed(6);
					
					list.css('width', list_width_perc + '%');
					items.css('width', item_width_perc + '%');
				} else {
					// Only one page, no need to resize anything
				}
			}

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
				
				// Animation
				this.animation = this.options.animation;
				
				if (!TRANSITIONS[this.animation]) {
					this.animation = 'fade';
				}
				
				if (this.animation == 'horizontal') {
					this.container.removeClass('nav-none');
					this.container.addClass('nav-horizontal');
				} else {
					this.container.removeClass('nav-horizontal');
					this.container.addClass('nav-none');
				}
				
				//Content height
				if (this.options.arrows || this.options.arrows_pages || this.options.scrollbar) {
					if (!this.hasNavigation) {
						this.hasNavigation = true;
						
						//Resize view height
						this.updateContentHeight(this.page, true);
					}
				} else {
					if (this.hasNavigation) {
						this.hasNavigation = false;
						
						//Update view height
						this.list.css({
							'height': 'auto'
						});
					}
				}
				
				//Navigation
				if (options.scrollbar) {
					options.keyboard = false;
				}
				
				for (var key in options) {
					if (key in NAVIGATION) {
						this.applyNavigation(key, options[key]);
					}
				}
				
				//Timer
				this.start(true);
			}
		},
		
		/**
		 * On window resize update everything
		 */
		'applyResizeWindow': function (event, size) {
			this.widthCache = null;
			this.updateContentHeight(this.page, true);
			
			if (SCROLL_UNIT == 'px') {
				// If % then CSS will position it correctly, with px we need to do
				// this manually
				this.navigate(this.page, true);
			}
		},
		
		/**
		 * On resize update options
		 */
		'applyResize': function (event, size) {
			//Reset cache
			this.contentWidthCache = null;
			this.widthCache = null;
			this.marginCache = null;
			
			//Check if height change is needed
			var changeHeight = !this.calculateItemsPerView(),
				hasPages = this.pages > 1,
				original = this.optionsOriginal;
			
			switch (size) {
				case SIZE_DESKTOP:
					this.apply({
						'arrows': hasPages && original.arrows,
						'arrows_pages': hasPages && original.arrows_pages,
						'scrollbar': hasPages && original.scrollbar
					});
					break;
				case SIZE_TABLET:
					this.apply({
						'arrows': hasPages && original.arrows,
						'arrows_pages': hasPages && original.arrows_pages,
						'scrollbar': hasPages && original.scrollbar
					});
					break;
				case SIZE_MOBILE_LANDSCAPE:
					var original_nav = original.arrows || original.arrows_pages || original.scrollbar;
					this.apply({'arrows': original_nav, 'arrows_pages': false, 'scrollbar': false});
					break;
				case SIZE_MOBILE_PORTRAIT:
					var original_nav = original.arrows || original.arrows_pages || original.scrollbar;
					this.apply({'arrows': original_nav, 'arrows_pages': false, 'scrollbar': false});
					break;
			}
			
			//Equal columns
			if ($.fn.equalHeight) {
				this.items.find('.outer').equalHeight('destroy');
				this.items.find('.inner-text').equalHeight('destroy');
				
				var perView = this.perView,
					nodes = null;
				
				if (this.options.scrollbar) {
					// All columns same size, not only which are in same view
					this.items.find('.outer').equalHeight({'auto_resize': false});
					this.items.find('.inner-text').equalHeight({'auto_resize': false});
				} else {
					if (perView > 1) {
						for (var i=0, ii=Math.ceil(this.items.length / perView); i<ii; i++) {
							nodes = this.items.slice(i * perView, (i + 1) * perView);
							
							nodes.find('.outer')
								 .equalHeight({'auto_resize': false});
							
							nodes.find('.inner-text')
								 .equalHeight({'auto_resize': false});
						}
					}
				}
			}
			
			//If height changed 
			if (changeHeight) {
				this.updateContentHeight(this.page, true);
			}
			
			// Update position
			this.navigate(this.current.index, true);
		},
		
		/**
		 * Apply navigation options
		 * 
		 * @param {String} name Navigation name
		 * @param {Object} options Falsy value to disable, any other to enable
		 * @private
		 */
		'applyNavigation': function (name, options) {
			if (!NAVIGATION[name] || !this.navigation) return;
			var nav = this.navigation[name];
			
			if (!nav && options) {
				nav = $.extend({'host': this}, NAVIGATION[name]);
				
				if (this.pages > 1 && nav.setup(options) !== false) {
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
			
			//Fix column sizes
			this.fixSizes();
			
			//Update items per view count
			var container_width = this.width(),
				item_width      = this.itemWidth(0),
				list_width		= this.container.width(),
				per_view        = Math.max(1, list_width / item_width),
				page			= this.page;
			
			// Because we are using percentage, we need can't just use Math.ceil
			// we need to take into account that list_width / item_width might not be
			// exact number, so we will tolerate some diviation
			if (per_view % 1 < 0.01) {
				per_view = Math.floor(per_view);
			} else {
				per_view = Math.ceil(per_view);
			}
			
			if (this.perView != per_view) {
				this.perView = per_view;
				this.pages = Math.ceil(this.count / per_view);
				
				page = Math.min(this.pages - 1, this.current.index);
				
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
			var margins = this.list.css(['marginLeft', 'marginRight']);
			return this.widthCache = this.container.width() - parseInt(margins.marginLeft, 10) - parseInt(margins.marginRight, 10); // 20px - margin
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
		 * Sum of left and right margins
		 * 
		 * @return Margin
		 * @type {Number}
		 */
		'margin': function () {
			if (this.marginCache) return this.marginCache;
			return this.marginCache = parseInt(this.items.eq(1).css('margin-left'), 10) + parseInt(this.items.eq(1).css('margin-right'), 10);
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
				deferred = $.Deferred(),
				self = this;
			
			(function calculateHeight () {
				var height = 0,
					k = i,
					kk = ii,
					img = null,
					remaining = 0;
				
				function next () {
					remaining--;
					if (remaining == 0) {
						calculateHeight();
					}
				}
				
				for(; k<kk; k++) {
					img = items.eq(k).find('img');
					if (img.size() && !img.get(0).complete) {
						img.on('load', next);
						remaining++;
					} else {
						height = Math.max(height, items.eq(k).outerHeight(true));
					}
				}
				
				if (!remaining) {
					// All images are loaded, resolve
					deferred.resolveWith(self, [height]);
				}
			})();
			
			return deferred.promise();
		},
		
		/**
		 * Update content height
		 * 
		 * @param {Number} index Page index
		 * @param {Boolean} quick Don't use animation
		 * @private
		 */
		'updateContentHeight': function (index, quick) {
			// Wait till all images are loaded
			this.pageHeight(index).done(function (height) {
				
				if (quick) {
					//Resize container
					this.list.css({
						'height': height
					});
				} else {
					//Animate container
					this.list.animate({
						'height': height
					}, {
						'queue': false,
						'duration': this.options.duration
					});
				}
				
			}, this);
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
		},
		
		/**
		 * Start timer
		 */
		'start': function (force) {
			var interval = this.options.timer,
				handle   = this.timerHandle;
			
			if (handle && force) {
				handle = this.stop();
			}
			if (interval && !handle) {
				this.timerHandle = setTimeout($.proxy(this.navigateNext, this), interval);
			}
		},
		
		/**
		 * Stop timer
		 */
		'stop': function () {
			var handle = this.timerHandle;
			if (handle) {
				clearTimeout(handle);
				this.timerHandle = null;
			}
			return 0;	
		}
		
	};
	
	/*
	 * jQuery namespace
	 */
	$.carousel = {
		'constructor': Carousel,
		'defaults': DEFAULTS,
		'transitions': TRANSITIONS,
		'navigation': NAVIGATION,
		'styles': STYLES
	};
	
	/*
	 * jQuery plugin
	 * Create widget or apply options or call a function
	 */
	$.fn.carousel = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof Carousel.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null;
		
		return this.each(function () {
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Carousel (element, $.extend({}, element.data(), options || {}));
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else {
				if (fn) {
					widget[fn].call(widget);
				} else if (options) {
					widget.apply(options);
					widget.applyResize();
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
		
		if (info.propertyName === 'design' || info.propertyName === 'link' || info.propertyName === 'layout') {
			//HTML is too different or image sizes has changed, instructign CMS to reload it
			return false;
		} else if (carousel && info.propertyName) {
			var options = {},
				old_group = null,
				new_group = null,
				styles = STYLES,
				navigation = NAVIGATION;
			
			if (info.propertyName === 'navigation') {
				options['arrows'] = false;
				options['arrows_pages'] = false;
				options['scrollbar'] = false;
				
				if (info.propertyValue in navigation) {
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
	
	
	// requirejs
	return Carousel;
	
}));