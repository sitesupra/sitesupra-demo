YUI.add('supra.slideshow', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Slideshow class 
	 * 
	 * @alias Supra.Slideshow
	 * @param {Object} config Configuration
	 */
	function Slideshow (config) {
		this.render_queue = [];
		this.history = [];
		this.slides = {};
		this.remove_on_hide = {};
		this.anim = null;
		
		Slideshow.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Slideshow.NAME = 'slideshow';
	Slideshow.CSS_PREFIX = 'su-' + Slideshow.NAME;
	
	Slideshow.ATTRS = {
		/**
		 * Slide list
		 * @type {Object}
		 */
		'slides': {
			value: null
		},
		
		/**
		 * Currently visible slide ID
		 * @type {String}
		 */
		'slide': {
			value: null
		},
		
		/**
		 * Don't use animations
		 */
		'noAnimations': {
			value: false
		},
		
		/**
		 * Animation duration 
		 */
		'animationDuration': {
			value: 0.5
		},
		
		/**
		 * Animation units, px or %
		 */
		'animationUnitType': {
			value: 'px'
		}
	};
	
	Slideshow.HTML_PARSER = {
		'slides': function (srcNode) {
			return srcNode.get('children');
		},
		'slide': function (srcNode) {
			var children = srcNode.get('children'),
				id = null;
			
			children.some(function (item) {
				if (!item.hasClass('hidden')) {
					id = item.getAttribute('id');
					return true;
				}
			});
			
			return id;
		}
	};
	
	Y.extend(Slideshow, Y.Widget, {
		
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
		 * Render UI
		 */
		renderUI: function () {
			var slides = this.get('slides'),
				newSlides = {};
			
			if (slides) {
				slides.each(function () {
					var id = this.get('id');
					var bound = Y.Node.create('<div class="su-slide"></div>');
					var data = this.getData();
					
					this.addClass('su-slide-content');
					this.insert(bound, 'before');
					bound.append(this);
					bound.setData(data);
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
					var slideId = this.get('slide');
					if (Y.Array.indexOf(this.history, slideId) == -1) {
						this.history.push(slideId);
					}
				}
				
				Supra.mix(this.slides, newSlides);
			}
			
			this.anim = new Y.Anim({
				node: this.get('contentBox'),
				duration: this.get('animationDuration'),
				easing: Y.Easing.easeOutStrong
			});
		},
		
		bindUI: function () {
			
			//On a[data-target] link click change slide
			this.get('contentBox').delegate('click', this._onSectionLinkClick, 'a[data-target]', this);
			this.get('contentBox').delegate('keyup', this._onSectionLinkKey, 'a[data-target]', this);
			
			//On slide change scroll to it
			this.on('slideChange', function (e) {
				if (e.newVal != e.prevVal && !e.silent) {
					this.scrollTo(e.newVal);
				}
			}, this);
			
			//Handle window resize
			var layout = this.get('contentBox').closest('.left-container, .right-container');
			if (layout) layout.after('contentResize', this.syncUI, this);
			
			//Render Supra.Scrollable widgets
			var render_queue = this.render_queue;
			for(var i=0,ii=render_queue.length; i<ii; i++) {
				render_queue[i].render();
			}
			this.render_queue = [];
			
		},
		
		syncUI: function () {
			var slideId = this.get('slide'),
				index = Y.Array.indexOf(this.history, slideId),
				unit = this.get('animationUnitType');
			
			this.slide_width = null;
			this.slide_width = this._getWidth();
			
			this.get('contentBox').setStyle('left', - index * this.slide_width + unit);
			
			//Update scrollbar position
			if (this.slides[slideId]) {
				var content = this.slides[slideId].one('.su-slide-content, .su-multiview-slide-content');
				if (content) {
					content.fire('contentResize');
				}
			}
		},
		
		/**
		 * If user clicks on section link change slide to it
		 * 
		 * @param {Event} event Event facade object
		 * @private
		 */
		_onSectionLinkClick: function (event) {
			var node = event.target.closest('a');
			if (!node.hasClass('disabled')) {
				var slide = node.getAttribute('data-target');
				if (this.slides[slide]) this.set('slide', slide);
			}
		},
		
		/**
		 * If user presses key on section link change slide to it
		 * 
		 * @param {Event} event Event facade object
		 * @private
		 */
		_onSectionLinkKey: function (event) {
			if (event.keyCode == 13 || event.keyCode == 39) { //Return key or arrow right
				var node = event.target.closest('a'),
					slide = node.getAttribute('data-target');
				
				if (this.slides[slide]) this.set('slide', slide);
			}
		},
		
		/**
		 * Scroll to slide
		 * 
		 * @param {Object} slideId Slide ID
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		scrollTo: function (slideId /* Slide ID */, callback /* Callback function */, context /* Context */) {
			var oldSlideId = this.get('slide');
			if (slideId == oldSlideId || !this.anim || !(slideId in this.slides)) return slideId;
			
			//Stop previous animation, otherwise new content may not be shown
			this.anim.stop();
			
			var index = Y.Array.indexOf(this.history, slideId),
				oldIndex = Y.Array.indexOf(this.history, oldSlideId),
				slideWidth = this._getWidth(),
				unit = this.get('animationUnitType'),
				to = - index * slideWidth + unit,
				from = - oldIndex * slideWidth + unit,
				boxNode = this.get('boundingBox');
			
			if (index == -1) {
				index = this.history.length;
				to = - index * slideWidth + unit;
				this.history[index] = slideId;
			}
			
			//Show and position new slide
			this.slides[slideId].setStyle('left', index * 100 + '%').removeClass('hidden');
			
			if (index < oldIndex) {
				this.history.splice(index + 1);
			}
			
			if (!this.get('noAnimations')) {
				//When animation ends hide old slide
				this.anim.once('end', function () {
					if (oldSlideId in this.slides) {
						if (oldSlideId in this.remove_on_hide) {
							//Remove slide
							this.removeSlide(oldSlideId);
						} else {
							//Hide slide
							this.slides[oldSlideId].addClass('hidden');
						}
						
						boxNode.setStyle('overflow', '');
					}
					
					//Execute callback
					if (Y.Lang.isFunction(callback)) {
						callback.call(context || this, slideId);
					}
				}, this);
				
				boxNode.setStyle('overflow', 'hidden');
				
				this.anim.stop();
				this.anim.set('from', {'left': from});
				this.anim.set('to', {'left': to});
				this.anim.run();
				
				//Update Supra.Scrollable
				Supra.immediate(this, function () {
					if (this.slides[slideId]) {
						var content = this.slides[slideId].one('.su-slide-content, .su-multiview-slide-content');
						if (content) {
							content.fire('contentResize');
						}
					}
				});
			} else {
				if (oldSlideId in this.slides) {
					if (oldSlideId in this.remove_on_hide) {
						//Remove slide
						this.removeSlide(oldSlideId);
					} else {
						//Hide slide
						this.slides[oldSlideId].addClass('hidden');
					}
				}
				this.get('contentBox').setStyle('left', to);
				
				//Make sure it's in correct position
				Supra.immediate(this, this.syncUI);
				
				//Execute callback
				if (Y.Lang.isFunction(callback)) {
					callback.call(context || this, slideId);
				}
			}
			
			this.set('slide', slideId, {silent: true});
			return slideId;
		},
		
		/**
		 * Scrolls to previous slide
		 * 
		 * @return New slide ID
		 * @type {String}
		 */
		scrollBack: function () {
			if (this.history.length > 1) {
				var slideId = this.history[this.history.length - 2];
				this.set('slide', slideId);
				return slideId;
			} else {
				return this.history.length ? this.history[0] : null;
			}
		},
		
		/**
		 * Scroll to root slide
		 * 
		 * @returns {String} New slide ID
		 */
		scrollRoot: function () {
			if (this.history.length > 1) {
				var slideId = this.history[0];
				this.set('slide', slideId);
				return slideId;
			} else {
				return this.history.length ? this.history[0] : null;
			}
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
				'title': '',
				'icon': ''
			}, Y.Lang.isObject(options) ? options : {'id': options});
			
			if (!options.id) return null;
			var slideId = options.id;
			
			if (options.removeOnHide) {
				this.remove_on_hide[slideId] = true;
			}
			
			if (!(slideId in this.slides)) {
				var slide = this.slides[slideId] = Y.Node.create('\
														<div class="hidden su-slide" data-icon="' + Y.Escape.html(options.icon) + '" data-title="' + Y.Escape.html(options.title) + '">\
															<div id="' + slideId + '" class="su-slide-content"></div>\
														</div>');
				
				this.slides[slideId] = slide;
				this.get('contentBox').append(slide);
				
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
					this.syncUI();
				}
				
				return slide;
			}
			
			return this.slides[slideId];
		},
		
		/**
		 * Remove slide
		 * 
		 * @param {String} slideId
		 */
		removeSlide: function (slideId) {
			if (slideId in this.slides) {
				//Remove slide
				this.slides[slideId].remove();
				delete(this.slides[slideId]);
				delete(this.remove_on_hide[slideId]);
			}
			
			return this;
		},
		
		/**
		 * Returns slide by ID
		 * 
		 * @return Slide boundingBox node
		 * @type {Object}
		 */
		getSlide: function (slideId) {
			if (slideId in this.slides) {
				return this.slides[slideId];
			} else {
				return null;
			}
		},
		
		/**
		 * Returns true if currently opened slide is first one
		 * or if slideId argument is passed checks if that slide is root
		 * 
		 * @param {String} slideId Optional, checks if given slide is root instead of current
		 * @return True if current slide is first one
		 * @type {Boolean}
		 */
		isRootSlide: function (slideId) {
			if (slideId) {
				return !this.history.length || this.history[0] == slideId;
			} else {
				return this.history.length <= 1;
			}
		},
		
		/**
		 * Returns if slide is in history
		 * 
		 * @param {String} slideId Slide ID
		 * @return True if slide is in history
		 * @type {Boolean}
		 */
		isInHistory: function (slideId /* Slide ID */) {
			return Y.Array.indexOf(this.history, slideId) !== -1;
		},
		
		/**
		 * Returns list of opened slides
		 * 
		 * @return List of slides
		 * @type {Array}
		 */
		getHistory: function () {
			return this.history;
		},
		
		/**
		 * Returns width of the slide container
		 * 
		 * @return Width of the slide container
		 * @type {Number}
		 */
		_getWidth: function () {
			if (!this.slide_width) {
				var unit = this.get('animationUnitType');
				if (unit == '%') {
					this.slide_width = 100; // 100%
				} else {
					this.slide_width = this.get('boundingBox').get('offsetWidth');
				}
			}
			return this.slide_width;
		}
		
	});
	
	
	Supra.Slideshow = Slideshow;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget', 'anim', 'supra.slideshow-input-button', 'supra.scrollable']});