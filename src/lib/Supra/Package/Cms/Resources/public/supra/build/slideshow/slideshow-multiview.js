/**
 * Slideshow for Media Library app
 * (not used for MediaBar or LinkManager)
 */
YUI.add('supra.slideshow-multiview', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var DEFAULT_SLIDE_WIDTH = 290;
	
	/**
	 * Slideshow class 
	 * 
	 * @alias Supra.Slideshow
	 * @param {Object} config Configuration
	 */
	function Slideshow (config) {
		Slideshow.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this.render_queue = [];
		this.history = [];
		this.slides = {};
		this.remove_on_hide = {};
		this.anim = null;
	}
	
	Slideshow.NAME = 'slideshow-multiview';
	Slideshow.CSS_PREFIX = 'su-' + Slideshow.NAME;
	
	Slideshow.ATTRS = {
		'defaultSlideWidth': {
			'value': DEFAULT_SLIDE_WIDTH
		},
		'scrollable': {
			'value': true
		}
	};
	
	Slideshow.HTML_PARSER = {
		'slides': function (srcNode) {
			var slides = srcNode.get('children');
			
			//Reverse slide order for correct animation
			var parent = null;
			slides.each(function () {
				if (!parent) parent = this.ancestor();
				parent.prepend(this);
			});
			
			return slides;
		}
	};
	
	Y.extend(Slideshow, Supra.Slideshow, {
		
		/**
		 * Width of the slide container
		 * @type {Number}
		 */
		slide_width: 0,
		
		/**
		 * Slide list, keys are slide ids, values are bounding nodes
		 * @type {Object}
		 */
		slides: {},
		
		/**
		 * List of slides which will be removed when hidden
		 * @type {Object}
		 */
		remove_on_hide: {},
		
		/**
		 * Animation instance
		 * @type {Object}
		 */
		anim: null,
		
		/**
		 * Sliding animation
		 */
		slide_anim: null,
		
		/**
		 * Scroll animation
		 */
		scroll_anim_pos: 0,
		
		/**
		 * Opened slide list
		 * @type {Array}
		 */
		history: [],
		
		/**
		 * Children widgets which needs to be rendered
		 * on slideshow renderUI
		 * @type {Array}
		 */
		render_queue: [],
		
		/**
		 * Supra.Scrollable instance
		 * @type {Object}
		 */
		scrollable: null,
		
		
		
		/**
		 * Render UI
		 */
		renderUI: function () {
			var slides = this.get('slides'),
				newSlides = {};
			
			if (slides) {
				slides.each(function () {
					var id = this.get('id');
					var bound = Y.Node.create('<div class="su-multiview-slide"></div>');
					this.addClass('su-multiview-slide-content');
					this.insert(bound, 'before');
					bound.append(this);
					newSlides[id] = bound;
					
					if (this.hasClass('hidden')) {
						this.removeClass('hidden');
						bound.addClass('hidden');
					}
					
					//Add scrollbar
					if (this.getAttribute('data-scrollable') != 'false') {
						var scrollable = new Supra.Scrollable({
							'srcNode': this
						});
						
						scrollable.render();
						bound.setData('scrollable', scrollable);
					}
				});
				
				if (!this.get('slide')) {
					for(var i in newSlides) {
						newSlides[i].removeClass('hidden');
						this.history.push(i);
						this.set('slide', i, {'silent': true});
						break;
					}
				} else {
					this.history.push(this.get('slide'));
				}
				
				this.slides = newSlides;
			}
			
			this.anim = new Y.Anim({
				node: this.get('contentBox'),
				duration: this.get('animationDuration'),
				easing: Y.Easing.easeOutStrong
			});
			
			this.slide_anim = new Y.Anim({
				node: this.get('contentBox'),
				duration: this.get('animationDuration'),
				easing: Y.Easing.easeOutStrong
			});
			
			//On slide change scroll to it
			this.on('slideChange', function (e) {
				if (e.newVal != e.prevVal && !e.silent) {
					this.scrollTo(e.newVal);
				}
			}, this);
			
			//Render Supra.Scrollable widget on slideshow itself
			if (this.get('scrollable')) {
				this.scrollable = new Supra.Scrollable({
					'srcNode': this.get('contentBox'),
					'axis': 'x'
				});
				this.scrollable.render();
			}
			
			//Render Supra.Scrollable widgets
			var render_queue = this.render_queue;
			for(var i=0,ii=render_queue.length; i<ii; i++) {
				render_queue[i].render();
			}
			this.render_queue = [];
		},
		
		syncUI: function () {
			//Update scrollbar position
			if (this.scrollable) {
				this.scrollable.syncUI();
			}
		},
		
		/**
		 * Update scroll position
		 */
		syncUIScrollPosition: function (index) {			var position = 0,
				total = this.get('contentBox').get('offsetWidth');
			
			this.slide_width = null;
			this.slide_width = this._getWidth();
			
			position = (index != -1 ? - index * this.slide_width : 0) + total - this.slide_width;
			position = Math.min(position, 0);
			
			if (!this.get('noAnimations')) {
				if (this.scroll_anim_pos != position) {
					this.get('contentBox').transition({
						'marginLeft': position + 'px',
						'duration': 0.35
					}, Y.bind(this.syncUI, this));
				}
			} else {
				this.get('contentBox').setStyle('marginLeft', position + 'px');
				this.syncUI();
			}
			
			this.scroll_anim_pos = position;
		},
		
		/**
		 * Hide slide
		 * 
		 * @param {String} slideId
		 * @private
		 */
		hideSlide: function (slideId, silent) {
			if (slideId && slideId in this.slides) {
				if (slideId in this.remove_on_hide) {
					//Remove slide
					this.removeSlide(slideId);
				} else {
					//Hide slide
					this.slides[slideId].addClass('hidden').removeClass('su-multiview-slide-last');
				}
				
				if (!silent) {
					this.syncUIScrollPosition(0);
				}
			}
		},
		
		/**
		 * Scroll to slide
		 * 
		 * @param {Object} slideId Slide ID
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		scrollTo: function (slideId /* Slide ID */, callback /* Callback function */, callerId /* Caller slide ID */) {
			var oldSlideId = this.get('slide');
			if (slideId == oldSlideId || !this.anim || !(slideId in this.slides)) return slideId;
			
			//Stop previous animations, otherwise new content may not be shown
			this.anim.stop(true);
			this.slide_anim.stop(true);
			
			//Hide all slides after caller slide
			var callerIndex = (callerId ? Y.Array.indexOf(this.history, callerId) : -1);
			if (callerIndex != -1) {
				for(var i=this.history.length-1, ii=callerIndex; i>ii; i--) {
					this.hideSlide(this.history[i], true);
				}
				this.history = this.history.splice(0, callerIndex + 1);
				oldSlideId = callerId;
			}
			
			//
			var index = Y.Array.indexOf(this.history, slideId),
				oldIndex = Y.Array.indexOf(this.history, oldSlideId),
				slideWidth = this._getWidth(),
				to = - index * slideWidth,
				from = - (callerIndex != -1 ? callerIndex : oldIndex) * slideWidth;
			
			if (index == -1) {
				index = (callerIndex != -1 ? callerIndex + 1 : this.history.length);
				to = - index * slideWidth;
				this.history[index] = slideId;
			}
			
			if (index > 0) {
				this.slides[this.history[index - 1]].removeClass('su-multiview-slide-last');
			}
			
			//Remove all unneeded slides
			if (index < oldIndex) {
				for(var i=this.history.length-1, ii=index; i>ii; i--) {
					this.hideSlide(this.history[i]);
				}
				this.history.splice(index + 1);
				
				//Execute callback
				if (Y.Lang.isFunction(callback)) {
					callback(slideId);
				}
				
				//Save value
				this.set('slide', slideId, {silent: true});
				
				//Style
				this.slides[slideId].addClass('su-multiview-slide-last');
				
				//Update scroll position
				this.syncUIScrollPosition(index);
				
				return;
			} else {
				oldSlideId = null;
			}
			
			//Animate
			if (!this.get('noAnimations')) {
				//Show new slide
				this.slides[slideId].setStyle('left', (index - 1) * slideWidth + 'px');
				
				this.slide_anim.set('node', this.slides[slideId]);
				this.slide_anim.set('from', {'left': (index - 1) * slideWidth + 'px'});
				this.slide_anim.set('to', {'left': (index) * slideWidth + 'px'});
				this.slide_anim.run();
				
				//Show new slide
				this.slides[slideId]
						.removeClass('hidden')
						.addClass('su-multiview-slide-last');
				
				//Update scrollbars
				this.slides[slideId].one('.su-scrollable-content').fire('contentResize');
				
				//When animation ends hide old slide
				this.hideSlide(oldSlideId);
				
				//Execute callback
				if (Y.Lang.isFunction(callback)) {
					callback(slideId);
				}
				
				this.slide_anim.once('end', function () {
					this.slides[slideId].one('.su-scrollable-content').fire('contentResize');
					
					//Scrollbars
					this.syncUI();
				}, this);
				
				this.syncUIScrollPosition(index);
				
			} else {
				//Show new slide
				this.slides[slideId].setStyle('left', index * slideWidth + 'px')
					.removeClass('hidden')
					.addClass('su-multiview-slide-last');
				
				//Update scrollbars
				this.slides[slideId].one('.su-scrollable-content').fire('contentResize');
				
				//Hide old slide
				this.hideSlide(oldSlideId);
				
				//Execute callback
				if (Y.Lang.isFunction(callback)) {
					callback(slideId);
				}
				
				//Scrollbars
				this.syncUI();
			}
			
			this.set('slide', slideId, {silent: true});
			
			return slideId;
		},
		
		/**
		 * Adds slide to the slideshow
		 * 
		 * @param {Object} options Slide options
		 * @param {Boolean} remove_on_hide Remove slide when it's hidden
		 * @return Slide boundingBox node
		 * @type {Object}
		 */
		addSlide: function (options) {
			var options = Supra.mix({
				'id': null,
				'removeOnHide': false,
				'scrollable': true,
				'className': '',
				'width': this.get('defaultSlideWidth')
			}, Y.Lang.isObject(options) ? options : {'id': options});
			
			if (!options.id) return null;
			var slideId = options.id;
			
			if (options.removeOnHide) {
				this.remove_on_hide[slideId] = true;
			}
			
			if (!(slideId in this.slides)) {
				var classSlide = 'su-multiview-slide',
					classLast = 'su-multiview-slide-last',
					classContent = 'su-multiview-slide-content',
					slide = this.slides[slideId] = Y.Node.create('\
														<div class="hidden ' + classSlide + ' ' + classLast + ' ' + options.className + '">\
															<div id="' + slideId + '" class="' + classContent + '"></div>\
														</div>');
				
				slide.setStyle('width', options.width + 'px');
				slide.setData('width', options.width);
				
				this.slides[slideId] = slide;
				this.get('contentBox').prepend(slide);
				
				//Add scrollbar
				if (options.scrollable) {
					var slideContent = slide.one('.su-slide-content, .su-multiview-slide-content'),
						scrollable = new Supra.Scrollable({
							'srcNode': slideContent
						});
					
					slide.setData('scrollable', scrollable);
					
					if (this.get('rendered')) {
						scrollable.render();
					} else {
						this.render_queue.push(scrollable);
					}
				}
				
				//If there are no slides, then make this as main
				if (!this.get('slide')) {
					slide.removeClass('hidden');
					this.history.push(slideId);
					this.set('slide', slideId, {'silent': true});
				}
				
				return slide;
			}
			
			return this.slides[slideId];
		},
		
		/**
		 * Returns width of the slide container
		 * 
		 * @param {String} slideId Slide ID
		 * @return Width of the slide container
		 * @type {Number}
		 */
		_getWidth: function (slideId) {
			if (slideId && slideId in this.slides) {
				return this.slides[slideId].getData('width');
			}
			return this.get('defaultSlideWidth');
		}
		
	});
	
	
	Supra.SlideshowMultiView = Slideshow;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.slideshow']});