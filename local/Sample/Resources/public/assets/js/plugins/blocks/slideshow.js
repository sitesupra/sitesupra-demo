/**
 * Simple slideshow
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/helpers/responsive', 'plugins/helpers/throttle'], function ($) {
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
	
	//CSS transforms supported
	var TRANSFORM_SUPPORTED = (
		'webkitTransform' in document.body.style ||
		'mozTransform' in document.body.style ||
		'msTransform' in document.body.style ||
		'transform' in document.body.style
	);
	
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
		'thumbs-outer': {
			'dots': false,
			'thumbnails': true,
			'pages': false,
			'arrows': true,
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
				duration = this.swiping ? 'fast' : this.options.duration,
				pos = dir == 1 ? -width : width;
			
			from.node.css({
				//'left': 0
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
		}
	};
	
	var NAVIGATION = {
		'arrows': {
			'setup': function (options) {
				var nodes    = this.nodes    = $('<div class="nav-arrows"><a class="prev"></a><a class="next"></a></div>');
				var nodeNext = this.nodeNext = nodes.find('.next').on(EVENT_CLICK, $.proxy(this.next, this));
				var nodePrev = this.nodePrev = nodes.find('.prev').on(EVENT_CLICK, $.proxy(this.prev, this));
				
				this.host.container.append(nodes);
				this.updateNavPosition();
				
				/*
				if (!TOUCH_SUPPORTED) {
					this.show = $.proxy(this.show, this);
					this.hide = $.proxy(this.hide, this);
					this.host.container.hover(this.show, this.hide);
				} else {
					//If touch device, assument there is no mouse and show navigation arrows always
					this.nodeNext.css('opacity', 1);
					this.nodePrev.css('opacity', 1);
				}
				*/
			},
			'teardown': function () {
				this.nodes.remove();
				this.nodes = null;
				this.nodeNext = null;
				this.nodePrev = null;
				this.host.container.off('mouseenter', this.show);
				this.host.container.off('mouseleave', this.hide);
			},
			'update': function (current) {
				this.updateNavPosition(current);
			},
			'show': function () {
				this.updateNavPosition();
				this.nodeNext.stop().animate({'opacity': 1}, this.host.options.duration / 2);
				this.nodePrev.stop().animate({'opacity': 1}, this.host.options.duration / 2);
			},
			'hide': function () {
				this.nodeNext.stop().animate({'opacity': 0}, this.host.options.duration / 2);
				this.nodePrev.stop().animate({'opacity': 0}, this.host.options.duration / 2);
			},
			'next': function (e) {
				this.host.navigate(this.host.current.index + 1);
				
				if (e) {
					e.preventDefault();
				}
			},
			'prev': function (e) {
				this.host.navigate(this.host.current.index - 1);
				
				if (e) {
					e.preventDefault();
				}
			},
			'updateNavPosition': function (current) {
				var host = this.host,
					top = 0;
				
				current = current || this.host.current;
				top = ~~(host.itemHeight(current.index, true) / 2);
				
				this.nodeNext.css('top', top ? top + 'px' : '50%');
				this.nodePrev.css('top', top ? top + 'px' : '50%');
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
				
				inner.find('a').on(EVENT_CLICK, $.proxy(this.click, this));
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
				
				if (e) {
					e.preventDefault();
				}
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
				var node = this.node = $('<ul class="thumbs clearfix" />'),
					src = null,
					element = null,
					items = this.host.items,
					count = this.host.count,
					current = this.host.current.index,
					i = 0;
				
				for (; i<count; i++) {
					// Image
					element = items.eq(i).find('img');
					if (element.size()) {
						src = element.attr('src');
						node.append($('<li' + (i == current ? ' class="active"' : '') + '><div><img src="' + src + '" /></div></li>').css({'opacity': i == current ? 1 : 0.5}));
					} else {
					
						// Video
						element = items.eq(i).find('iframe');
						if (element.size()) {
							node.append($('<li class="icon-video ' + (i == current ? 'active' : '') + '"><div></div></li>').css({'opacity': i == current ? 1 : 0.5}));
						} else {
							// Text
							node.append($('<li class="icon-text ' + (i == current ? 'active' : '') + '"><div></div></li>').css({'opacity': i == current ? 1 : 0.5}));
						}
					}
				}
				
				node.find('li').on(EVENT_CLICK, $.proxy(this.click, this));
				
				if (!TOUCH_SUPPORTED) {
					node.find('li').hover(this.mouseenter, this.mouseleave);
				}
				
				this.host.container.append(node);
			},
			'teardown': function () {
				this.node.remove();
			},
			'apply': function (options) {},
			'update': function (current, previous) {
				var items = this.node.find('li');
				items.eq(previous.index).stop().animate({'opacity': 0.5}, 'fast').removeClass('active');
				items.eq(current.index).stop().animate({'opacity': 1}, 'fast').addClass('active');
			},
			'mouseenter': function (e) {
				$(this).stop().animate({'opacity': 1}, 'fast');
			},
			'mouseleave': function (e) {
				if (!$(this).hasClass('active')) {
					$(this).stop().animate({'opacity': 0.5}, 'fast');
				}
			},
			'click': function (e) {
				this.host.navigate($(e.target).closest('li').index());
				
				if (e) {
					e.preventDefault();
				}
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
					this.host.navigate(this.host.current.index + diffDir);
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
		this.ns = 'slideshow-' + (UID++);
		this.navigation = {};
		this.optionsOriginal = options || {};
		this.options = $.extend({}, DEFAULTS, options || {});
		
		this.render(element);
		this.apply(this.options);
		this.applyResize({}, $.responsive.size);
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
		 * Swipe in progress
		 * @type {Boolean}
		 * @private
		 */
		'swiping': false,
		
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
		 * @param {Number} index Image index to which navigate to
		 * @return True if navigation is performed, otherwise false
		 * @type {Boolean}
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
				item = items.eq(index).clone();
				
				this.current = to = {
					'index': index,
					'node': item,
					'inner': item.find('div')
				};
				
				//Re-initialize attachments
				this.view.append(to.node);
				if ($.app) {
					$.app.parse(item);
				}
				
				//Animate images
				TRANSITIONS[animation].call(this, from, to, dir);
				
				//Animate container
				this.itemHeightLoad(index).done(function (height) {
					this.view.animate({
						'height': height
					}, this.options.duration);
				});
				
				//Update navigation UI to match current state
				this.updateNavigation(to, from);
			} else {
				//Image is already opened
				this.animating = false;
			}
		},
		
		/**
		 * Update item height
		 */
		'syncHeight': function () {
			//Animate container
			this.itemHeightLoad(this.current.index).done(function (height) {
				this.view.css({
					'height': height
				});
			});
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
			
			item = items.eq(0).clone();
			current = this.current = {
				'index': 0,
				'node': item,
				'inner': item.find('div')
			};
			
			//Handle image load 
			list.hide();
			view.append(current.node);
			container.prepend(view);
			
			//Re-initialize attachments
			if ($.app) {
				$.app.parse(item);
			}
			
			//Resize view
			this.itemHeightLoad(0).done(function (height) {
				view.css('height', height);
			});
			
			//On browser resize update
			$.responsive.on('resize', $.proxy(this.applyResize, this));
			$(window).on('resize', $.throttle(this.syncHeight, this, 100, true));
		},
		
		/**
		 * Apply options
		 */
		'apply': function (options) {
			if (typeof options === 'object') {
				
				//Style
				if (options.style && options.style != this.options.style) {
					options = $.extend({}, STYLES[options.style] || {}, options);
					
					this.container.removeClass(this.options.style);
					this.container.addClass(options.style);
					
					//Update view height
					this.itemHeightLoad(this.current.index).done(function (height) {
						this.view.css('height', height);
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
		'applyResize': function (event, size) {
			var style = this.optionsOriginal.style;
			
			switch (size) {
				case SIZE_DESKTOP:
					this.apply({'style': style || 'dots', 'animation': this.optionsOriginal.animation || 'horizontal'});
					break;
				case SIZE_TABLET:
					this.apply({'style': style || 'dots', 'animation': this.optionsOriginal.animation || 'horizontal'});
					break;
				case SIZE_MOBILE_LANDSCAPE:
					this.apply({'style': 'touch'});
					break;
				case SIZE_MOBILE_PORTRAIT:
					this.apply({'style': 'touch'});
					break;
			}
			
			//Resize view
			this.itemHeightLoad(this.current.index || 0).done(function (height) {
				this.view.css('height', height);
			});
		},
		
		/**
		 * Apply navigation options
		 * 
		 * @param {String} name Navigation name
		 * @param {Object} options Falsy value to disable, any other to enable
		 * @private
		 */
		'applyNavigation': function (name, options) {
			if (!NAVIGATION[name] || this.count <= 1) return;
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
		'itemHeight': function (index, imageOnly) {
			var height = 0,
				node = this.items.eq(index);
			
			this.list.show();
			height = Math.max(!imageOnly ? node.outerHeight(true) : 0, node.find('img, .video').outerHeight(true));
			this.list.hide();
			
			return height;
		},
		
		/**
		 * Wait till image is loaded and calculate item height
		 * 
		 * @returns {Object} Deferred object to which is passed item height
		 */
		'itemHeightLoad': function (index, imageOnly) {
			var deferred = $.Deferred(),
				node = this.items.eq(index),
				img  = node.find('img'),
				height = 0;
			
			if (!img.size() || img.get(0).complete) {
				height = this.itemHeight(index, imageOnly);
				deferred.resolveWith(this, [height]);
			} else {
				img.on('load', $.proxy(function () {
					height = this.itemHeight(index, imageOnly);
					deferred.resolveWith(this, [height]);
				}, this));
				img.on('error', $.proxy(function () {
					deferred.rejectWith(this);
				}, this));
			}
			
			return deferred.promise();
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
		'navigation': NAVIGATION,
		'styles': STYLES
	};
	
	/*
	 * jQuery plugin
	 * Create widget or apply options or call a function
	 */
	$.fn.promo = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof Promo.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Promo (element, $.extend({}, element.data(), options || {}));
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
	if ($.refresh) {
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
					new_group = null,
					styles = STYLES;
				
				if (info.propertyName === 'design') {
					//Style exists?
					if (!styles[info.propertyValue]) return false;
					
					options['style'] = info.propertyValue;
					
				} else if (info.propertyName === 'animation') {
					options[info.propertyName] = info.propertyValue;
				} else if (info.propertyName === 'padding') {
					// Padding was changed and since items are positioned
					// absolutely we need to recalculate slideshow height
					// manually
					var slideshow = info.target.data('promo');
					if (slideshow) {
						setTimeout(function () {
							promo.syncHeight();
						}, 0);
					}
				} else {
					return;
				}
				
				promo.apply(options);
			}
		});
	}
	
	// requirejs
	return Promo;
	
}));