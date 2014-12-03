/**
 * Responsive parallax slideshow
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/helpers/responsive', 'plugins/helpers/resize', 'plugins/helpers/video-api'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'slideshow-advanced',
		CMS_MODE = $('html').hasClass('supra-cms'),
		
		isCmsEditMode = function () {
			var body = $('body'),
				is_preview = body.hasClass('su-mode-preview');
			
			return CMS_MODE && !is_preview;
		};
	
	/**
	 * Responsive parallax slideshow
	 * 
	 * @param {Object} element Container element
	 * @param {Object} options Optional configuration options
	 * @constructor
	 */
	function Slideshow (element, options) {
		var container = this._container = $(element),
			options   = this._options = $.extend({}, Slideshow.defaultOptions, options, container.data()),
			wrapper   = this._wrapper   = container.find(options.wrapperSelector),
			inner     = this._inner = container.find(options.innerSelector);
		
		// List of CSS and animation styles
		this._styles = [];
		this._stylesFn = [];
		this._animations = [];
		
		// Slides
		this._initSlides(container.find(options.slideSelector));
		
		// Navigation
		this.setNavigation(this._getNavigationOptions());
		
		// Handle resize
		this._resizeNavigationChange = $.proxy(this._resizeNavigationChange, this);
		this._updateScale = $.proxy(this._updateScale, this);
		
		$.responsive.on('resize', this._resizeNavigationChange);
		$.responsive.on('resize', this._updateScale);
		
		// Window focus / blur
		this._initFocus();
		
		// Video play / pause
		this._initVideo();
		
		// Start timer if needed
		if (options.scrollTimer) {
			this.start();
		}
	}
	
	Slideshow.defaultOptions = {
		// CSS selector used to find inner element
		'innerSelector': '.as-inner',
		
		// CSS selector used to find wrapper element
		'wrapperSelector': '.as-container',
		
		// CSS selector used to find slide elements
		'slideSelector':   '.as-container > li',
		
		// Images, videos, texts are resized to fit container size
		'responsive': true,
		
		// Common options for all slides
		'slideOptions': {},
		
		// Automatically adjust height based on slide height
		'autoHeight': true,
		
		// Height animation duration
		'autoHeightDuration': 600,
		
		// Default width
		'initialWidth': 0,
		
		// Scroll timer
		'scrollTimer': 0,
		
		// Navigation options
		'navigation': {
			'arrows': false,
			'progress': false,
			'dots': false,
			'numbers': false
		}
	};
	
	Slideshow.prototype = {
		
		/**
		 * Container element
		 * @type {Object}
		 * @private
		 */
		'_container': null,
		
		/**
		 * Inner element, needed when slideshow is 'wide'
		 * @type {Object}
		 * @private
		 */
		'_inner': null,
		
		/**
		 * Wrapper element, usually inside container; slide ancestor element
		 * @type {Object}
		 * @private
		 */
		'_wrapper': null,
		
		/**
		 * Slide instances
		 * @type {Array}
		 * @private
		 */
		'_slides': null,
		
		/**
		 * Currently opened slide index
		 * @type {Number}
		 * @private
		 */
		'_index': 0,
		
		/**
		 * Total number of slides
		 * @type {Number}
		 * @private
		 */
		'_count': 0,
		
		/**
		 * List of css styles to apply to elements
		 * We don't apply immediatelly, because setting and then getting css will trigger reflows
		 * @type {Array}
		 * @private
		 */
		'_styles': null,
		
		/**
		 * List of callback to call after styles has been applied
		 * @type {Array}
		 * @private
		 */
		'_stylesFn': null,
		
		/**
		 * List of css animations to apply to elements
		 * We don't apply immediatelly, because setting css and then getting css will trigger reflows
		 */
		'_animations': null,
		
		/**
		 * Initial wrapper width
		 * @type {Number}
		 * @private
		 */
		'_initialWidth': 0,
		
		/**
		 * Last known wrapper width
		 * @type {Number}
		 * @private
		 */
		'_lastWidth': 0,
		
		/**
		 * Last known wrapper height
		 * @type {Number}
		 * @private
		 */
		'_lastHeight': 0,
		
		/**
		 * Last know scale coefficient
		 * @type {Number}
		 * @private
		 */
		'_lastScaleCoefficient': 0,
		
		/**
		 * Scroll timer setTimeout instance
		 * @type {Number}
		 * @private
		 */
		'_scrollTimer': null,
		
		/**
		 * Navigation objects
		 * @type {Object}
		 * @private
		 */
		'_navigation': null,
		
		/**
		 * Last $.responsive.size
		 * @type {Number}
		 * @private
		 */
		'_lastSize': null,
		
		/**
		 * Animation in progress
		 * @type {Boolean}
		 * @private
		 */
		'_animating': false,
		
		
		/**
		 * Initialize all slides
		 * 
		 * @param {Object} nodes Slide nodes
		 * @private
		 */
		'_initSlides': function (nodes) {
			// During prepare slideshow size is 940px wide 
			this._container.removeClass('waiting');
			this._container.addClass('prepearing');
			
			// Find width of the slideshow, will work correctly only in desktop mode, not mobile
			this._initialWidth = this._options.initialWidth || this._container.outerWidth();
			
			var i = 0,
				count = nodes.length,
				slides = [],
				slide = null,
				options = this._options.slideOptions;
			
			for (; i<count; i++) {
				slide = new Slide(nodes.eq(i), $.extend({
					'slideshow': this,
					'index': i
				}, options));
				
				slides.push(slide);
			}
			
			this._count = count;
			this._slides = slides;
			
			this._updateScale();
			
			// Restore correct size
			this._container.removeClass('prepearing');
			this._container.addClass('ready');
			this._updateScale();
		},
		
		/**
		 * Destroy slides, data, etc.
		 */
		'destroy': function () {
			this.stop();
			
			if (this._slides) {
				var slides = this._slides,
					i = 0,
					ii = slides.length;
				
				for (; i<ii; i++) {
					slides[i].destroy();
				}
			}
			
			this._options = null;
			this._slides = null;
			this._count = 0;
			this._index = 0;
			this._animations = null;
			this._stylesFn = null;
			this._styles = null;
			this._container = null;
			this._wrapper = null;
			this._inner = null;
			
			$.responsive.off('resize', this._resizeNavigationChange);
			$.responsive.off('resize', this._updateScale);
		},
		
		/**
		 * Returns slideshow container element
		 * 
		 * @returns {Object} Slideshow container element
		 */
		'container': function () {
			return this._container;
		},
		
		/**
		 * Returns slideshow inner element
		 * 
		 * @returns {Object} Slideshow inner element
		 */
		'inner': function () {
			return this._inner;
		},
		
		/* --------------- FOCUS / BLUR --------------- */
		
		/**
		 * When window is blured (user switches to another tab or minizes window)
		 * stop timer and resume on focus
		 * 
		 * @private
		 */
		'_initFocus': function () {
			var focus = $.proxy(this._resumeTimer, this),
				blur  = $.proxy(this._pauseTimer, this);
			
			$(window).on('focus', focus);
			$(window).on('blur',  blur);
		},
		
		/* --------------- VIDEO --------------- */
		
		/**
		 * Video is playing
		 * @type {Boolean}
		 * @private
		 */
		'_video_playing': false,
		
		/**
		 * 
		 */
		'_initVideo': function () {
			var onplay  = $.proxy(this._onVideoPlay, this),
				onpause = $.proxy(this._onVideoPause, this);
			
			this.container().find('iframe').videoAPI(function (state) {
				if (state == 'play') {
					onplay();
				} else {
					onpause();
				}
			});
		},
		
		'_onVideoPlay': function () {
			this._video_playing = true;
			this._pauseTimer();
		},
		
		'_onVideoPause': function () {
			this._video_playing = false;
			this._resumeTimer();
		},
		
		/* --------------- TIMER --------------- */
		
		/**
		 * Start scroll timer
		 */
		'start': function () {
			if (this._scrollTimer) {
				clearTimeout(this._scrollTimer);
				this._scrollTimer = null;
			}
			
			// If only 1 slide, then no need
			if (this.count() > 1) {
				var interval = this._options.scrollTimer * 1000;
				if (interval && typeof interval === 'number') {
					this._scrollTimer = setTimeout($.proxy(function () {
						if (!isCmsEditMode()) {
							// Not CMS mode
							this.next();
						} else {
							this._updateNavigation(this._index, this._index);
							this.start();
						}
					}, this), interval);
				}
			}
		},
		
		/**
		 * Stop scroll timer
		 */
		'stop': function () {
			if (this._scrollTimer) {
				clearTimeout(this._scrollTimer);
				this._scrollTimer = null;
			}
		},
		
		/**
		 * Papuse timer
		 * 
		 * @private
		 */
		'_pauseTimer': function () {
			if (this._scrollTimer) {
				var timer = this._scrollTimer;
				this.stop();
				this._scrollTimer = timer;
			}
		},
		
		/**
		 * Resume timer
		 * 
		 * @private
		 */
		'_resumeTimer': function () {
			if (this._scrollTimer && !this._video_playing) {
				this.start();
			}
		},
		
		/* --------------- NAVIGATION --------------- */
		
		/**
		 * Initialize navigation
		 * 
		 * @param {Object} Navigation options
		 */
		'setNavigation': function (options) {
			if (!this._navigation) {
				this._navigation = {};
			}
			
			if (typeof options === 'string') {
				var tmp = {};
				tmp[options] = true;
				options = tmp;
			}
			
			var key       = null,
				nav       = this._navigation,
				container = this._container;
			
			// Remove old ones
			for (key in nav) {
				if (nav[key] && (!(key in options) || options[key] === false)) {
					if (nav[key].teardown) {
						nav[key].teardown();
					}
					nav[key] = false;
					container.removeClass('as-nav-' + key);
				}
			}
			
			// Set up new ones if there is more than 1 slide
			if (this.count() > 1) {
				for (key in options) {
					if (Slideshow.navigation[key] && options[key] && (!(key in nav) || nav[key] === false)) {
						nav[key] = $.extend({
							'host': this
						}, Slideshow.navigation[key]);
						
						if (nav[key].setup) {
							nav[key].setup(options[key]);
						}
						
						container.addClass('as-nav-' + key);
					}
				}
			}
		},
		
		/**
		 * Update navigation
		 * 
		 * @private
		 */
		'_updateNavigation': function (current, previous) {
			var nav = this._navigation,
				key = null;
			
			for (key in nav) {
				if (nav[key] && nav[key].update) {
					nav[key].update(current, previous);
				}
			}
		},
		
		/**
		 * On resize change navigation type if neccessary
		 * 
		 * @private
		 */
		'_resizeNavigationChange': function () {
			var nav = this._getNavigationOptions();
			
			if (nav) {
				this.setNavigation(nav);
			}
		},
		
		/**
		 * Returns navigation options
		 * 
		 * @param {Number} size_new New viewport responsive size
		 * @param {Number} size_old Old viewport responsive size
		 * @param {Boolean} change_only Return options only if anything changed
		 */
		'_getNavigationOptions': function () {
			var nav        = this._navigation,
				options    = this._options.navigation || {},
				is_mobile  = false,
				was_mobile = false,
				changed    = false,
				
				size_old   = this._lastSize,
				size_new   = $.responsive.size,
				size_ml    = $.responsive.SIZE_MOBILE_LANDSCAPE,
				size_mp    = $.responsive.SIZE_MOBILE_PORTRAIT;
			
			// Options can also be a string
			if (typeof options === 'string') {
				var tmp = {};
				tmp[options] = true;
				options = tmp;
			}
			
			options = $.extend({}, options || {});
			
			if (size_new == size_ml || size_new == size_mp) {
				is_mobile = true;
			}
			if (size_old == size_ml || size_old == size_mp) {
				was_mobile = true;
			}
			
			// If number navigation, then change it to arrows on mobile
			if (is_mobile && !was_mobile && options.numbers) {
				options.numbers = false;
				options.arrows = true;
				changed = true;
			} else if (!is_mobile && was_mobile && options.numbers) {
				changed = true;
			}
			
			// Save last known size
			this._lastSize = size_new;
			
			// If there was size_old then this is not first request
			// and returns options only if anything changed
			if (size_old && !changed) {
				return null;
			}
			
			return options;
		},
		
		
		/* --------------- RESIZE --------------- */
		
		/**
		 * In response to resize rescale all slides and layers
		 * which needs it
		 * 
		 * @private
		 */
		'_updateScale': function () {
			// While resizing add special class to remove transitions and
			// maybe set special styles
			this._container.addClass('as-resizing');
			
			var new_width = this._container.width(),
				initial_width = this._initialWidth,
				old_coef  = this._lastScaleCoefficient,
				new_coef  = Math.round(new_width / initial_width * 100) / 100;
			
			if (new_coef == old_coef) {
				// Restore state
				this._container.removeClass('as-resizing');
				return;
			}
			
			var slides = this._slides,
				i = 0,
				ii = slides.length,
				amount;
			
			this._lastWidth = new_width;
			this._lastScaleCoefficient = new_coef;
			
			for (; i<ii; i++) {
				slides[i].scale(new_coef);
			}
			
			// Update slideshow height after style change
			this.afterSetStyles($.proxy(this._updateHeight, this));
			
			this.applyStyles();
			
			// Restore state
			this._container.removeClass('as-resizing');
		},
		
		/**
		 * Update slideshow height to match slide
		 * 
		 * @private
		 */
		'_updateHeight': function () {
			if (!this._count) return;
			
			var slide  = this._slides[this._index],
				height = slide.height();
			
			this._lastHeight = height;
			this._wrapper.css('height', height);
			this._container.css('height', height);
		},
		
		/**
		 * Animate slideshow height to match new slide height
		 * 
		 * @private
		 */
		'_animateHeight': function (slide) {
			var height = slide.height(),
				duration = this._options.autoHeightDuration;
			
			this.queueNodeAnimation(this._wrapper, {'height': height}, {'duration': duration});
			this.queueNodeAnimation(this._container, {'height': height}, {'duration': duration});
			
			this._lastHeight = height;
		},
		
		/**
		 * Returns last known scale coefficient
		 * 
		 * @returns {Number} Scale coefficient
		 */
		'scale': function () {
			return this._lastScaleCoefficient || 1;
		},
		
		/**
		 * Update slideshow after content resize
		 */
		'update': function () {
			console.log('UPDATE');
			this._initialWidth = this._container.outerWidth();
			this._updateHeight();
		},
		
		
		/* --------------- ATTRIBUTES --------------- */
		
		/**
		 * Open a slide
		 * 
		 * @param {Number} index Slide index to open, optional
		 * @returns {Number} Opened slide index
		 */
		'index': function (index) {
			if (!this._animating && typeof index === 'number' && index != this._index && index >=0 && index < this._count) {
				var slides     = this._slides,
					slide_from = slides[this._index],
					slide_to   = slides[index],
					timer      = 0;

				slide_from.bringDown();
				slide_to.bringUp();
				
				timer = Math.max(0, slide_from.transitionOut(), slide_to.transitionIn());
				
				if (this._options.autoHeight) {
					this._animateHeight(slide_to);
				}
				
				this.applyStyles();
				this.startNodeAnimations();
				
				this._animating = true;
				this._updateNavigation(index, this._index);
				this._index = index;
				
				// Wait till end
				setTimeout($.proxy(function () {
					this._animating = false;
				}, this), timer);
				
				// Timer
				if (this._scrollTimer) {
					this.start();
				}
			}
			
			return this._index;
		},
		
		/**
		 * Open next slide
		 */
		'next': function () {
			var count = this._slides.length,
				index = this._index;
			
			if (count > 1) {
				index = (index + 1) % count;
				this.index(index);
			}
			
			// If used as event handler, prevent default
			return false;
		},
		
		/**
		 * Open previous slide
		 */
		'previous': function () {
			var count = this._slides.length,
				index = this._index;
			
			if (count > 1) {
				index = (index - 1 + count) % count;
				this.index(index);
			}
			
			// If used as event handler, prevent default
			return false;
		},
		
		/**
		 * Returns slide count
		 * 
		 * @returns {Number} Slide count
		 */
		'count': function () {
			return this._count;
		},
		
		/**
		 * Returns slide by index
		 * 
		 * @returns {Object} Slide
		 */
		'slide': function (index) {
			return this._slides[index] || null;
		},
		
		/**
		 * Returns slideshow width
		 * 
		 * @returns {Number} Slideshow width
		 */
		'width': function () {
			return this._lastWidth || this._initialWidth;
		},
		
		/**
		 * Returns slideshow height
		 * 
		 * @returns {Number} Slideshow height
		 */
		'height': function () {
			return this._lastHeight;
		},
		
		/* --------------- STYLES --------------- */
		
		/**
		 * Add styles to the element
		 * 
		 * @param {Object} node Node to apply styles to
		 * @param {Object} styles List of styles to apply
		 */
		'setStyles': function (node, styles) {
			this._styles.push([node, styles]);
		},
		
		/**
		 * Call function after styles has been applied
		 * 
		 * @param {Function} fn Callback function
		 */
		'afterSetStyles': function (fn) {
			this._stylesFn.push(fn);
		},
		
		/**
		 * Apply styles
		 */
		'applyStyles': function () {
			var styles = this._styles,
				i = 0,
				ii = styles.length,
				
				styles_fn = this._stylesFn,
				k = 0,
				kk = styles_fn.length;
			
			for (; i<ii; i++) {
				styles[i][0].css(styles[i][1]);
			}
			
			for (; k<kk; k++) {
				styles_fn[k]();
			}
			
			this._styles = [];
			this._stylesFn = [];
		},
		
		/**
		 * Add styles to the element
		 * 
		 * @param {Object} node Node to apply styles to
		 * @param {Object} properties List of CSS properties
		 * @param {Object} options Optional list of animation options
		 */
		'queueNodeAnimation': function (node, properties, options) {
			this._animations.push([node, properties, options]);
		},
		
		/**
		 * Apply animations
		 */
		'startNodeAnimations': function () {
			var animations = this._animations,
				i = 0,
				ii = animations.length;
			
			for (; i<ii; i++) {
				animations[i][0].animate(animations[i][1], animations[i][2]);
			}
			
			this._animations = [];
		}
	};
	
	
	/* ------------------------------ NAVIGATION ------------------------------ */
	
	
	Slideshow.navigation = {
		
		'arrows': {
			/**
			 * Set up arrow navigation
			 */
			'setup': function (options) {
				var node = this.node = $('<div class="nav nav-arrows"><a class="prev"></a><a class="next"></a></div>');
				this.nodeNext = node.find('.next').click($.proxy(this.next, this));
				this.nodePrev = node.find('.prev').click($.proxy(this.previous, this));
				
				this.host.inner().append(node);
			},
			/**
			 * Tear down arrow navigation
			 */
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.nodeNext = null;
				this.nodePrev = null;
			},
			
			'next': function (e) {
				this.host.stop();
				this.host.next();
				
				if (e) {
					e.preventDefault();
				}
			},
			
			'previous': function (e) {
				this.host.stop();
				this.host.previous();
				
				if (e) {
					e.preventDefault();
				}
			}
			
		},
		
		'dots': {
			/**
			 * Set up dot navigation
			 */
			'setup': function (options) {
				var count = this.host.count(),
					current = this.host.index(),
					i = 0,
					node = this.node = $('<div class="nav nav-dots"></div>');
				
				for (; i<count; i++) {
					node.append('<a data-index="' + i + '" class="' + (i == current ? 'active' : '') + '"><span>' + (i + 1) + '</span></a>');
				}
				
				this.host.inner().append(node);
				this.node.on('click', 'a', $.proxy(this.navigate, this));
				this.items = this.node.find('a');
			},
			
			/**
			 * Tear down arrow navigation
			 */
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.items = null;
			},
			
			/**
			 * Update navigation
			 */
			'update': function (next, previous) {
				this.items.removeClass('active').eq(next).addClass('active');
			},
			
			'navigate': function (e) {
				this.host.stop();
				this.host.index($(e.target).closest('a').data('index'));
				
				if (e) {
					e.preventDefault();
				}
			}
		},
		
		'numbers': {
			/**
			 * Set up number navigation / pagination
			 */
			'setup': function (options) {
				var count = this.host.count(),
					current = this.host.index(),
					i = 0,
					node = this.node = $('<div class="nav nav-numbers"></div>');
				
				for (; i<count; i++) {
					node.append('<a data-index="' + i + '" class="' + (i == current ? 'active' : '') + '"><span>' + (i + 1) + '</span></a>');
				}
				
				this.host.inner().append(node);
				this.node.on('click', 'a', $.proxy(this.navigate, this));
				this.items = this.node.find('a');
			},
			
			/**
			 * Tear down arrow navigation
			 */
			'teardown': function () {
				this.node.remove();
				this.node = null;
				this.items = null;
			},
			
			/**
			 * Update navigation
			 */
			'update': function (next, previous) {
				this.items.removeClass('active').eq(next).addClass('active');
			},
			
			'navigate': function (e) {
				this.host.stop();
				this.host.index($(e.target).closest('a').data('index'));
				
				if (e) {
					e.preventDefault();
				}
			}
		},
		
		'progress': {
			
			/**
			 * Set up progress bar navigation
			 */
			'setup': function (options) {
				if (this.host._options.scrollTimer && this.host.count() > 1) {
					var count = this.host.count(),
						sep_count = count - 1,
						width = 100,
						size = (width - sep_count) / count,
						node = this.node = $('<div class="nav nav-progress"><div class="nav-progress-background"><ul></ul></div></div>'),
						node_bg = node.find('.nav-progress-background ul'),
						item = null,
						check = size.toFixed(2) * count + sep_count;

					this.host._itemSelector = 'li.nav-progress-item';

					check = (check == width) ? true : false;

					if (!check) {
						size = parseFloat(size.toFixed(1)) - 0.1;
					} else {
						size = size.toFixed(2);
					}

					for (var i = 0; i < count; i++) {
						if (!check && i == sep_count) {
							size = width - sep_count - size * sep_count;
						}
						if (i > 0) {
							node_bg.append('<li style="width:1%"></li>');
						}
						item = $('<li class="nav-progress-item" data-w="'+ size +'%" style="width:'+ size +'%"></li>');
						node_bg.append(item);
					}
					node.find('.nav-progress-background ' + this.host._itemSelector).eq(0).append('<div class="nav-progress-active"></div>');

					this.nodeInner = this.node.find('div.nav-progress-active');
					this.host.inner().append(node);
					this.host.container().hover($.proxy(this.stopTimer, this), $.proxy(this.startTimer, this));
					this.node.on('click', this.host._itemSelector, $.proxy(this.navigate, this));
					this.startAnimation();
				}
			},
			
			/**
			 * Tear down arrow navigation
			 */
			'teardown': function () {
				if (this.node) {
					this.node.remove();
					this.node = null;
				}
			},
			
			/**
			 * Update navigation
			 */
			'update': function (next, previous) {
				var container = $(this.host._container.get(0)).find(this.host._itemSelector).eq(next);

				this.nodeInner.remove();
				this.nodeInner.css({'width': 0});
				container.append(this.nodeInner);

				this.startAnimation(next);
			},
			
			'navigate': function (e) {
				var target = $(e.target),
					item = $(this.host._container.get(0)).find(target.prop('tagName') + '.' + target.attr('class')),
					index = item.index(target);
				this.host.index(index);
				this.host.start();
				
				return false;
			},
			
			/**
			 * Reset
			 */
			'resetAnimation': function () {
				this.nodeInner.stop().animate({'width': 0}, {'duration': 'fast', 'easing': 'linear'});
			},
			
			/**
			 * Start animation
			 */
			'startAnimation': function () {
				var duration = this.host._options.scrollTimer * 1000;

				this.nodeInner.stop().animate({'width': '100%'}, {'duration': duration, 'easing': 'linear'});
			},
			
			/**
			 * Stop timer
			 */
			'stopTimer': function () {
				this.host.stop();
				this.resetAnimation();
			},
			
			/**
			 * Start timer
			 */
			'startTimer': function () {
				this.host.start();
				this.startAnimation();
			}
			
		}
		
	};
	
	
	/* ------------------------------ SLIDE ------------------------------ */
	
	
	/**
	 * Slideshow slide
	 * 
	 * @param {Object} element Slide element
	 * @param {Object} options Optional configuration options
	 * @constructor
	 */
	function Slide (element, options) {
		var container = this._container = $(element),
			options   = this._options = $.extend({}, Slide.defaultOptions, options, container.data()),
			visible   = this._visible = (this._options.index === options.slideshow.index()),
			styles    = {};
		
		container.css({
			'position': 'absolute',
			'left': '0px',
			'top': visible ? '0px' : '-9000px',
			'width': '100%'
		}).removeClass('hidden');
		
		if (visible) {
			this._initLayers(true);
		}
	}
	
	Slide.defaultOptions = {
		// Slideshow object
		'slideshow': null,
		
		// Slide index
		'index': 0,
		
		// Common options for all layers
		'layerOptions': {},
		
		// Predefined height
		'height': null
	};
	
	Slide.prototype = {
		
		/**
		 * Container element
		 * @type {Object}
		 * @private
		 */
		'_container': null,
		
		/**
		 * List of all layers on the slide
		 * @type {Array}
		 * @private
		 */
		'_layers': null,
		
		/**
		 * Slide height
		 * @type {Number}
		 * @private
		 */
		'_height': 0,
		
		/**
		 * Slide is visible
		 * @type {Boolean}
		 * @private
		 */
		'_visible': false,
		
		/**
		 * Layers are initialized
		 * @type {Boolean}
		 * @private
		 */
		'_layersInitialized': false,
		
		
		/**
		 * Move slide obove other slides
		 */
		'bringUp': function () {
			this._container.css('z-index', 1);
		},
		
		/**
		 * Move slide below other slides
		 */
		'bringDown': function () {
			this._container.css('z-index', 0);
		},
		
		/**
		 * Initialize layers inside this slide
		 * 
		 * @private
		 */
		'_initLayers': function (visible) {
			if (this._layersInitialized) return;
			this._layersInitialized = true;
			
			var container = this._container,
				nodes     = container.find('*'),
				count     = nodes.length,
				i         = 0,
				layers    = [],
				layer     = null,
				options   = this._options.layerOptions,
				slideshow = this._options.slideshow,
				minheight = 0;
			
			for (; i<count; i++) {
				if (nodes.eq(i).is('.as-wrapper, .as-center')) {
					minheight = nodes.eq(i).css('minHeight');
					if (minheight && minheight != '0px') {
						layers.push(
							new Layer(nodes.eq(i), $.extend({
								'slideshow': slideshow,
								'slide': this,
								'visible': visible,
								'responsiveMinHeight': true,
								'margin': false
							}, options))
						);
					}
				} else {
					layers.push(
						new Layer(nodes.eq(i), $.extend({
							'slideshow': slideshow,
							'slide': this,
							'visible': visible
						}, options))
					);
				}
			}
			
			// Make sure layers with 'cover' responsizeSizeType are resized last
			// to obtain correct slide height
			layers.sort(function (a, b) {
				if (a._options.responsizeSizeType == 'cover') {
					return 1;
				} else if (a._options.responsizeSizeType == 'cover') {
					return -1;
				} else {
					return 0;
				}
			});
			
			this._layers = layers;
		},
		
		/**
		 * Destructor
		 */
		'destroy': function () {
			if (this._layers) {
				var layers = this._layers,
					i = 0,
					ii = layers.length;
				
				for (; i<ii; i++) {
					layers[i].destroy();
				}
			}
			
			this._container = null;
			this._layers = null;
			this._visible = false;
			this._layersInitialized = false;
		},
		
		/* --------------- SCALE --------------- */
		
		'scale': function (coefficient) {
			if (this._layers) {
				var layers = this._layers,
					i = 0,
					ii = layers.length,
					
					slideshow = this._options.slideshow,
					container = this._container,
					height = this._height;
				
				for (; i<ii; i++) {
					layers[i].scale(coefficient);
				}
				
				// Resize slide
				/*
				if (!height) {
					// First call, get initial height
					slideshow.afterSetStyles($.proxy(function () {
						this._height = container.innerHeight();
					}, this));
				} else {
					slideshow.setStyles(container, {
						'height': height * coefficient
					});
				}
				*/
			}
		},
		
		/**
		 * Rescale all layers
		 * 
		 * @private
		 */
		'_rescaleLayers': function () {
			var slideshow = this._options.slideshow;
			this.scale(slideshow.scale());
			slideshow.applyStyles();
		},
		
		/* --------------- ATTRIBUTES --------------- */
		
		/**
		 * Transition setter, getter
		 * 
		 * @param {String} name Transition name
		 * @returns {String} Transition name
		 */
		'transition': function (name) {
			if (typeof name === 'string') {
				this._options.transition = name;
			}
			return this._options.transition;
		},
		
		/**
		 * Slideshow getter
		 * 
		 * @returns {Object} Slideshow object
		 */
		'slideshow': function () {
			return this._options.slideshow;
		},
		
		/**
		 * Slide height
		 * 
		 * @returns {Number} Slide height
		 */
		'height': function () {
			if (this._options.height) {
				return this._options.height;
			}
			
			if (!this._layersInitialized) {
				this._initLayers();
				this._rescaleLayers();
			}
			
			return this._container.height();
		},
		
		/**
		 * Slide width
		 * 
		 * @returns {Number} Slide width
		 */
		'width': function () {
			return this._options.slideshow.width();
		},
		
		/* --------------- ANIMATION --------------- */
		
		/**
		 * Animate slide out of view
		 * 
		 * @param {String} transition Transition name which is used to
		 * animate opossite slide in
		 */
		'transitionOut': function () {
			// Stop all videos
			this._container.find('iframe').videoAPI('pause');
			
			// Animate layers
			return this.transitionOutLayers();
		},
		
		/**
		 * Transition out all layers
		 * 
		 * @returns {Number} Number of milliseconds when transition will end
		 */
		'transitionOutLayers': function () {
			var layers = this._layers,
				count  = layers.length,
				i      = 0,
				time   = 0;
			
			for (; i<count; i++) {
				time = Math.max(time, layers[i].transitionOut());
			}
			
			return time;
		},
		
		/**
		 * Animate slide into view
		 * 
		 * @returns {Number} Number of milliseconds when transition will end
		 */
		'transitionIn': function () {
			if (!this._layers) {
				this._initLayers();
				this._rescaleLayers();
			}
			
			this._container.css('top', '0px');
			return this.transitionInLayers();
		},
		
		/**
		 * Transition out all layers
		 * 
		 * @returns {Number} Number of milliseconds when transition will end
		 */
		'transitionInLayers': function () {
			var layers = this._layers,
				count  = layers.length,
				i      = 0,
				time   = 0;
			
			for (; i<count; i++) {
				time = Math.max(time, layers[i].transitionIn());
			}
			
			// $.resize plugin
			if ($.fn.resize) {
				this._container.find('[data-attach="$.fn.resize"]').resize('update');
			}
			
			return time;
		}
		
	};
	
	
	/* ------------------------------ LAYER ------------------------------ */
	
	
	/**
	 * Slide layer
	 * 
	 * @param {Object} element Layer element
	 * @param {Object} options Optional configuration options
	 * @constructor
	 */
	function Layer (element, options) {
		var container = this._container = $(element),
			options   = this._options = $.extend({}, Layer.defaultOptions, options, container.data());
		
		if (options.slideshow._options.responsive) {
			this._checkResponsiveOptions();
		} else {
			options.reponsiveSize = false;
			options.responsivePosition = false;
			options.responsiveText = false;
		}
		
		this._checkTransitionOptions();
	}
	
	Layer.defaultOptions = {
		'responsiveSize': null,
		'responsizeSizeType': 'proportional',
		'responsivePosition': null,
		'responsiveText': null,
		'responsiveClass': null,
		'responsiveMinHeight': null,
		
		// x and y positions on the slide
		'x': null,
		'y': null,
		
		// element size
		'minHeight': null,
		'width': null,
		'height': null,
		'ratio': null,
		
		// text size
		'fontSize': null,
		'lineHeight': null,
		'padding': null,
		'margin': null,
		'border': null,
		
		'minFontSize': 12,
		'minLineHeight': 15,
		 
		// additionally to moving also fade-in and fade-out
		'fade': false,
		
		// 'in' animation options
		'directionIn': '',
		'durationIn': 0,
		'easingIn': 'swing',
		'delayIn': 0,
		'directionDistanceIn': 250,
		
		// 'out' animation options
		'directionOut': '',
		'durationOut': 0,
		'easingOut': 'swing',
		'delayOut': 0,
		'directionDistanceOut': 250,
		
		// ms before automatically transitioning out, 0 - never
		'showUntil': 0
	};
	
	Layer.prototype = {
		
		/**
		 * Regular expression to split CSS value into value and unit
		 * @type {RegExp}
		 * @private
		 */
		'_regexStyleSplit': /(-?[\d\.]+)(%|[a-z]+)/i,
		
		/**
		 * Layer element
		 * @type {Object}
		 * @private
		 */
		'_container': null,
		
		/**
		 * Last known scale
		 * @type {Number}
		 * @private
		 */
		'_scale': 1,
		
		/**
		 * Layer is visible
		 * @type {Boolean}
		 * @private
		 */
		'_visible': false,
		
		/**
		 * Timer used to hide layer after predefined time
		 * @type {Number}
		 * @private
		 */
		'_showUntilTimer': null,
		
		
		/**
		 * Destructor
		 */
		'destroy': function () {
			if (this._showUntilTimer) {
				clearTimeout(this._showUntilTimer);
			}
			
			this._container = null;
			this._visible = false;
			this._scale = 1;
			this._visible = false;
			this._showUntilTimer = null;
		},
		
		
		/* --------------- TRANSITION --------------- */
		
		/**
		 * Check which transition options layer has and adjust styles
		 * 
		 * @private
		 */
		'_checkTransitionOptions': function () {
			var container = this._container,
				options   = this._options,
				styles     = {},
				visible   = options.visible;
			
			if (options.directionIn || options.directionOut) {
				var position = container.css('position');
				if (!position || position === 'static') {
					styles.position = 'relative';
				}
				
				if (options.directionDistanceIn) {
					options.directionDistanceIn = this._parseStyle(options.directionDistanceIn);
				}
				if (options.directionDistanceOut) {
					options.directionDistanceOut = this._parseStyle(options.directionDistanceOut);
				}
				
				if (options.directionIn) {
					if (visible) {
						this._applyTransitionPosition(styles, options.directionIn, options.directionDistanceIn, 0);
					} else {
						this._applyTransitionPosition(styles, options.directionIn, options.directionDistanceIn, 1);
					}
				} else {
					if (visible) {
						this._applyTransitionPosition(styles, options.directionOut, options.directionDistanceOut, 0);
					} else {
						this._applyTransitionPosition(styles, options.directionOut, options.directionDistanceOut, 1);
					}
				}
			}
			
			if (visible == false && options.fade) {
				styles.opacity = 0;
			}
			
			this._visible = visible;
			options.slideshow.setStyles(container, styles);
		},
		
		/**
		 * Transition layer into view
		 */
		'transitionIn': function () {
			if (this._visible) return;
			
			var container  = this._container,
				options    = this._options,
				scale      = this._scale,
				styles     = {},
				transition = {},
				has_styles = false,
				has_trans  = false;
			
			if (options.fade) {
				styles.opacity = 0;
				transition.opacity = 1;
				has_styles = true;
				has_trans = true;
			}
			
			if (options.directionIn) {
				has_styles = true;
				has_trans = true;
				this._applyTransitionPosition(transition, options.directionIn, options.directionDistanceIn, 0);
				this._applyTransitionPosition(styles, options.directionIn, options.directionDistanceIn, 1);
			} else if (options.directionOut) {
				has_styles = true;
				this._applyTransitionPosition(styles, options.directionOut, options.directionDistanceOut, 0);
			}
			
			if (has_styles) {
				options.slideshow.setStyles(container, styles);
			}
			
			container.css('visibility', 'visible');
			
			if (has_trans) {
				if (options.delayIn) {
					container.delay(options.delayIn);
				}
				
				options.slideshow.queueNodeAnimation(container, transition, {
					'duration': options.durationIn,
					'easing': options.easingIn
				});
			}
			
			if (options.showUntil) {
				this._showUntilTimer = setTimeout($.proxy(this.transitionOut, this), options.showUntil);
			}
			
			this._visible = true;
			
			return (options.delayIn || 0) + (options.durationIn || 0);
		},
		
		/**
		 * Transition layer out of view
		 */
		'transitionOut': function () {
			if (!this._visible) return 0;
			
			var container  = this._container,
				options    = this._options,
				scale      = this._scale,
				styles     = {},
				transition = {},
				has_styles = false,
				has_trans  = false;
			
			if (options.fade) {
				styles.opacity = 1;
				transition.opacity = 0;
				has_styles = true;
				has_trans = true;
			}
			
			if (options.directionOut) {
				has_styles = true;
				has_trans = true;
				this._applyTransitionPosition(transition, options.directionOut, options.directionDistanceOut, 1);
				this._applyTransitionPosition(styles, options.directionOut, options.directionDistanceOut, 0);
			} else if (options.directionIn) {
				has_styles = true;
				this._applyTransitionPosition(styles, options.directionIn, options.directionDistanceIn, 1);
			}
			
			if (has_styles) {
				options.slideshow.setStyles(container, styles);
			}
			
			if (has_trans) {
				if (options.delayOut) {
					container.delay(options.delayOut);
				}
				
				options.slideshow.queueNodeAnimation(container, transition, {
					'duration': options.durationOut,
					'easing': options.easingOut,
					'complete': function () {
						container.css('visibility', 'hidden');
					}
				});
			}
			
			if (this._showUntilTimer) {
				clearTimeout(this._showUntilTimer);
				this._showUntilTimer = null;
			}
			
			this._visible = false;
			
			return (options.delayOut || 0) + (options.durationOut || 0);
		},
		
		/**
		 * Set direction position on object
		 */
		'_applyTransitionPosition': function (object, direction, distance, position) {
			if (direction === 'top' || direction === 'bottom') {
				if (distance[1] === '%') {
					var height = position ? this._options.slideshow.height() : this._options.slide.height(),
						size   = this._container.height();
					
					distance = [Math.max(height, size) * distance[0] / 100, 'px'];
				}
			} else if (direction === 'left' || direction === 'right') {
				if (distance[1] === '%') {
					var width = position ? this._options.slideshow.width() : this._options.slide.width(),
						size  = this._container.width();
					
					distance = [Math.max(width, size) * distance[0] / 100, 'px'];
				}
			}
			
			var options = this._options;
			switch (direction) {
				case '':
					break;
				case 'top':
					// @TODO If x or y unit is not px then this will be broken
					object.left = (options.x ? options.x[0] : 0) + 'px';
					object.top = (options.y ? options.y[0] : 0) - (position ? distance[0] : 0) + 'px';
					break;
				case 'right':
					object.top = (options.y ? options.y[0] : 0) + 'px';
					object.left = (options.x ? options.x[0] : 0) + (position ? distance[0] : 0) + 'px';
					break;
				case 'bottom':
					object.left = (options.x ? options.x[0] : 0) + 'px';
					object.top = (options.y ? options.y[0] : 0) + (position ? distance[0] : 0) + 'px';
					break;
				case 'left':
					object.top = (options.y ? options.y[0] : 0) + 'px';
					object.left = (options.x ? options.x[0] : 0) - (position ? distance[0] : 0) + 'px';
					break;
			}
		},
		
		/* --------------- RESPONSIVE --------------- */
		
		/**
		 * Check what responsive properties this layer has
		 * 
		 * @private
		 */
		'_checkResponsiveOptions': function () {
			var container = this._container,
				options   = this._options,
				styles    = {},
				has       = false; // has styles
			
			// Position
			if (options.responsivePosition === null) {
				options.responsivePosition = (options.x !== null || options.y !== null);
			}
			if (options.responsivePosition) {
				// Retrieve left and top properties if we don't have them in x and y
				if (options.x === null) {
					options.x = this._getElementStyle(container, 'left') || null;
				}
				if (options.y === null) {
					options.y = this._getElementStyle(container, 'left') || null;
				}
				
				if (typeof options.x === 'number') {
					options.x = [options.x, 'px'];
				}
				if (typeof options.y === 'number') {
					options.y = [options.y, 'px'];
				}
				
				if (options.x || options.y) {
					// CSS 'position' must be 'relative' or 'absolute' to set left and top
					var position = container.css('position');
					if (!position || position === 'static') {
						styles.position = 'relative';
						has = true;
					}
				} else {
					// It's not responsive after all
					options.responsivePosition = false;
				}
			}
			
			// Size
			if (options.responsiveSize !== false) {
				if (container.is('img, video, object, iframe')) {
					// Width, height and ratio
					if (options.width === null) {
						options.width = parseInt(container.attr('width'), 10) || container.width();
						if (!options.width && options.height && options.ratio) {
							options.width = Math.round(options.height * options.ratio);
						}
					}
					if (options.height === null) {
						options.height = parseInt(container.attr('height'), 10) || container.height();
						if (!options.height && options.width && options.ratio) {
							options.height = Math.round(options.width / options.ratio);
						}
					}
					
					if (options.width && options.height) {
						options.ratio = options.width / options.height;
						options.responsiveSize = true;
					} else {
						options.responsiveSize = false;
					}
				} else {
					if (options.width) {
						styles.width = options.width;
						options.responsiveSize = true;
						has = true;
					} else if (options.responsiveSize === true) {
						options.width = container.width();
					}
				}
			}
			
			// Min height
			if (options.responsiveMinHeight === true) {
				if (!options.minHeight) {
					options.minHeight = this._getElementStyle(container, 'minHeight');
				}
			}
			
			// Text
			if (!CMS_MODE && options.responsiveText !== false && container.is('div, p, h1, h2, h3, h4, h5, table, ul, ol') && $.trim(container.text())) {
				if (options.fontSize === null) {
					options.fontSize = this._getElementStyle(container, 'fontSize');
				}
				if (options.lineHeight === null) {
					options.lineHeight = this._getElementStyle(container, 'lineHeight');
				}
				
				options.responsiveText = true;
			} else {
				options.responsiveText = false;
			}
			
			// Size and text needs padding, margin and border adjusted
			if (options.responsiveText === true || options.responsiveSize === true) {
				if (options.padding === null) {
					options.padding = [
						(options.paddingTop ? this._parseStyle(options.paddingTop) : this._getElementStyle(container, 'paddingTop')) || [0, 'px'],
						(options.paddingRight ? this._parseStyle(options.paddingRight) : this._getElementStyle(container, 'paddingRight')) || [0, 'px'],
						(options.paddingBottom ? this._parseStyle(options.paddingBottom) : this._getElementStyle(container, 'paddingBottom')) || [0, 'px'],
						(options.paddingLeft ? this._parseStyle(options.paddingLeft) : this._getElementStyle(container, 'paddingLeft')) || [0, 'px']
					];
				}
				if (options.margin === null) {
					options.margin = [
						(options.marginTop ? this._parseStyle(options.marginTop) : this._getElementStyle(container, 'marginTop')) || [0, 'px'],
						(options.marginRight ? this._parseStyle(options.marginRight) : this._getElementStyle(container, 'marginRight')) || [0, 'px'],
						(options.marginBottom ? this._parseStyle(options.marginBottom) : this._getElementStyle(container, 'marginBottom')) || [0, 'px'],
						(options.marginLeft ? this._parseStyle(options.marginLeft) : this._getElementStyle(container, 'marginLeft')) || [0, 'px']
					];
				}
				if (options.border === null) {
					options.border = [
						(options.borderTop ? this._parseStyle(options.borderTop) : this._getElementStyle(container, 'borderTop')) || [0, 'px'],
						(options.borderRight ? this._parseStyle(options.borderRight) : this._getElementStyle(container, 'borderRight')) || [0, 'px'],
						(options.borderBottom ? this._parseStyle(options.borderBottom) : this._getElementStyle(container, 'borderBottom')) || [0, 'px'],
						(options.borderLeft ? this._parseStyle(options.borderLeft) : this._getElementStyle(container, 'borderLeft')) || [0, 'px']
					];
				}
			}
			
			if (has) {
				options.slideshow.setStyles(container, styles);
			}
		},
		
		/**
		 * Returns elment css style split into value and unit
		 * 
		 * @param {Object} node Element
		 * @param {String} property CSS property name
		 * @returns {Array} Array with first item as value and second as unit
		 * @private
		 */
		'_getElementStyle': function (node, property) {
			var value = node.css(property),
				match = value ? value.match(this._regexStyleSplit) : null;
			
			return match ? [parseFloat(match[1]), match[2]] : null;
		},
		
		/**
		 * Parse CSS style
		 * 
		 * @param {String} value Style value
		 * @returns {Array} Array with first item as value and second as unit
		 */
		'_parseStyle': function (value) {
			var type = typeof value;
			if (type === 'string') {
				var match = value ? value.match(this._regexStyleSplit) : null;
				return match ? [parseFloat(match[1]), match[2]] : null;
			} else if (type === 'number') {
				return [value, 'px'];
			} else {
				return null;
			}
		},
		
		/**
		 * Rescale text, position and size
		 * 
		 * @param {Number} coefficient Scale coefficient comparing to original
		 * @
		 */
		'scale': function (coefficient) {
			var styles = {},
				options = this._options,
				container = this._container,
				t, t2,
				value_reset = '';
			
			// Position
			if (options.responsivePosition) {
				if (options.x) {
					styles.left = Math.round(options.x[0] * coefficient) + options.x[1];
				}
				if (options.y) {
					styles.top = Math.round(options.y[0] * coefficient) + options.y[1];
				}
			}
			
			// Size
			if (options.responsiveSize) {
				if (options.width) {
					styles.width = Math.round(options.width * coefficient) + 'px';
				}
				if (options.height) {
					styles.height = Math.round(options.height * coefficient) + 'px';
				}
				
				// If responsize size type is 'cover' then make sure layers covers all slide height
				if (options.width && options.height && options.responsizeSizeType == 'cover') {
					t = options.slide.height();
					t2 = Math.round(options.height * coefficient);
					
					if (t2 < t) {
						styles.height = t + 'px';
						styles.width  = Math.round(t * options.ratio) + 'px';
					}
				}
			}
			
			// Min height
			if (options.responsiveMinHeight) {
				if (options.minHeight) {
					styles.minHeight = Math.max(0, options.minHeight[0] * coefficient) + options.minHeight[1];
				}
			}
			
			// Text
			if (options.responsiveText) {
				if (options.fontSize) {
					if (coefficient === 1) {
						styles.fontSize = value_reset;
					} else {
						styles.fontSize = Math.max(options.minFontSize, Math.round(options.fontSize[0] * coefficient)) + options.fontSize[1];
					}
				}
				if (options.lineHeight) {
					if (coefficient === 1) {
						styles.lineHeight = value_reset;
					} else {
						styles.lineHeight = Math.max(options.minLineHeight, Math.round(options.lineHeight[0] * coefficient)) + options.lineHeight[1];
					}
				}
			}
			
			// Size or Text
			if (options.responsiveText || options.responsiveSize) {
				if (t = options.padding) {
					styles.padding = Math.round(t[0][0] * coefficient) + t[0][1] + ' ' +
									 Math.round(t[1][0] * coefficient) + t[1][1] + ' ' +
									 Math.round(t[2][0] * coefficient) + t[2][1] + ' ' +
									 Math.round(t[3][0] * coefficient) + t[3][1];
				}
				if (t = options.margin) {
					styles.margin  = Math.round(t[0][0] * coefficient) + t[0][1] + ' ' +
									 Math.round(t[1][0] * coefficient) + t[1][1] + ' ' +
									 Math.round(t[2][0] * coefficient) + t[2][1] + ' ' +
									 Math.round(t[3][0] * coefficient) + t[3][1];
				}
				if (t = options.border) {
					styles.border  = Math.round(t[0][0] * coefficient) + t[0][1] + ' ' +
									 Math.round(t[1][0] * coefficient) + t[1][1] + ' ' +
									 Math.round(t[2][0] * coefficient) + t[2][1] + ' ' +
									 Math.round(t[3][0] * coefficient) + t[3][1];
				}
			}
			
			if (options.responsiveClass) {
				var classnames = options.responsiveClass,
					i = 0,
					ii = classnames.length,
					match = coefficient > 0.9 ? 0 : (
						    coefficient > 0.6 ? 1 : (
						    coefficient > 0.4 ? 2 : 3));
				
				for (; i<ii; i++) {
					if (classnames[i]) {
						if (i == match) {
							container.addClass(classnames[i]);
						} else {
							container.removeClass(classnames[i]);
						}
					}
				}
			}
			
			this._scale = coefficient;
			options.slideshow.setStyles(container, styles);
		},
		
		/* --------------- ATTRIBUTES --------------- */
		
		/**
		 * Slideshow getter
		 * 
		 * @returns {Object} Slideshow object
		 */
		'slideshow': function () {
			return this._options.slideshow;
		},
		
		/**
		 * Slide getter
		 * 
		 * @returns {Object} Slide object
		 */
		'slide': function () {
			return this._options.slide;
		}
		
	};
	
	
	/* ------------------------------ PLUGIN ------------------------------ */
	
	
	/*
	 * jQuery plugin
	 * Create widget or call a function
	 */
	$.fn.slideshowAdvanced = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof Slideshow.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null,
			args = fn ? Array.prototype.slice.call(arguments, 1) : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new Slideshow(element, options);
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else if (fn) {
				widget[fn].apply(widget, args);
			}
		});
	};
	
	/*
	 * jQuery plugin for slideshowAdvanced with predefined effects
	 * Create widget or call a function
	 */
	$.fn.slideshowAdvancedPredefined = function (prop) {
		
		this.each(function () {
			
			var element = $(this),
				animation = element.data('animation') || 'horizontal',
				slides  = element.find('.as-container > li'),
				slide   = null,
				count   = slides.size(),
				i       = 0;
			
			for (; i<count; i++) {
				slide = slides.eq(i);
				
				// Background
				if (animation == 'horizontal') {
					slide.find('div.as-background, div.as-background-color, div.as-mask').data({
						'directionIn': 'right',
						'durationIn': 550,
						'directionDistanceIn': '100%',
						'directionOut': 'left',
						'durationOut': 550,
						'directionDistanceOut': '100%'
					});
				} else if (animation == 'vertical') {
					slide.find('div.as-background, div.as-background-color, div.as-mask').data({
						'directionIn': 'bottom',
						'durationIn': 550,
						'directionDistanceIn': '100%',
						'directionOut': 'top',
						'durationOut': 550,
						'directionDistanceOut': '100%'
					});	
				} else if (animation === 'fade') {
					slide.find('div.as-background, div.as-background-color, div.as-mask').data({
						'durationIn': 550,
						'durationOut': 550,
						'fade': true
					});
				}
				
				// Text
				slide.find('h1').data({
					'directionIn': 'left',
					'durationIn': 550,
					'delayIn': 650,
					'directionOut': 'bottom',
					'durationOut': 550,
					'directionDistanceOut': 400,
					'fade': true
				});
				slide.find('.as-layer-center h1, .as-layer-full h1').data({
					'directionIn': 'top',
					'durationIn': 350
				});
				
				slide.find('h2').data({
					'directionIn': 'top',
					'durationIn': 450,
					'delayIn': 950,
					'directionOut': 'bottom',
					'durationOut': 550,
					'directionDistanceOut': 400,
					'fade': true
				});
				slide.find('h3').data({
					'directionIn': 'top',
					'durationIn': 350,
					'delayIn': 1250,
					'directionOut': 'bottom',
					'durationOut': 550,
					'directionDistanceOut': 400,
					'fade': true
				});
				slide.find('p').data({
					'directionIn': '',
					'durationIn': 650,
					'delayIn': 1550,
					'directionOut': 'bottom',
					'durationOut': 550,
					'directionDistanceOut': 400,
					'fade': true
				});
				slide.find('a.button').data({
					'directionIn': 'bottom',
					'durationIn': 1200,
					'directionDistanceIn': 75,
					'delayIn': 1150,
					'directionOut': 'bottom',
					'durationOut': 550,
					'directionDistanceOut': 400,
					'fade': true,
					'responsiveClass': ['', '', 'small', 'tiny']
				});
				
				slide.find('.as-layer-left-small, .as-layer-right-small, .as-layer-center').data({
					'durationIn': 550,
					'durationOut': 550,
					'fade': true
				});
				
				// Large image or video
				slide.find('.as-layer-right-large, .as-layer-left-large, .as-layer-center, .as-layer-full').find('img').data({
					'directionIn': 'top',
					'durationIn': 550,
					'directionDistanceIn': 800,
					'delayIn': 400,
					'directionOut': 'bottom',
					'durationOut': 550,
					'directionDistanceOut': 800,
					'responsiveSize': false
				});
				slide.find('.as-layer-right-large, .as-layer-left-large, .as-layer-center, .as-layer-full').find('.video').data({
					'directionIn': 'bottom',
					'durationIn': 550,
					'directionDistanceIn': 800,
					'delayIn': 400,
					'directionOut': 'bottom',
					'durationOut': 550,
					'directionDistanceOut': 800
				});
				
			}
		});
		
		return this.slideshowAdvanced(prop);
		
	};
	
	//$.refresh implementation
	if ($.refresh) {
		$.refresh.on('refresh/slideshowAdvanced', function (event, info) {
			// Initialize plugin
			info.target.slideshowAdvancedPredefined();
		});
		
		$.refresh.on('cleanup/slideshowAdvanced', function (event, info) {
			// Destroy plugin, clean up
			var object = info.target.data(DATA_INSTANCE_PROPERTY);
			if (object) {
				object.destroy();
				info.target.data(DATA_INSTANCE_PROPERTY, null)
			}
		});
		
		$.refresh.on('update/slideshowAdvanced', function (event, info) {
			// When CMS settigns change reload slideshow block	
			switch (info.propertyName) {
				case "animation":
					//This will tell CMS to reload block content
					return false;
				case "navigation":
					var object = info.target.data(DATA_INSTANCE_PROPERTY),
						options = {};
					
					options[info.propertyValue] = true;
					object.setNavigation(options);
					
					return;
			}
		});
		
		$.refresh.on('resize/slideshowAdvanced', function (event, info) {
			
			// Update tabs style
			info.target.slideshowAdvanced('update');
			
		});
	}
	
	// requirejs
	return Slideshow;
	
}));