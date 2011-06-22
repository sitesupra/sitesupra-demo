YUI.add('supra.slideshow', function (Y) {
	
	//Shortcut
	var getClass = Y.ClassNameManager.getClassName;
	
	/**
	 * Slideshow class 
	 * 
	 * @alias Supra.Slideshow
	 * @param {Object} config Configuration
	 */
	function Slideshow (config) {
		Slideshow.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this.history = [];
		this.slides = {};
		this.anim = null;
	}
	
	Slideshow.NAME = 'slideshow';
	
	Slideshow.ATTRS = {
		/**
		 * Slide list
		 * @type {Object}
		 */
		'slides': {},
		
		/**
		 * Currently visible slide ID
		 * @type {String}
		 */
		'slide': '',
		
		/**
		 * Don't use animations
		 */
		'noAnimations': false
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
		 * Render UI
		 */
		renderUI: function () {
			var slides = this.get('slides'),
				newSlides = {};
			
			if (slides) {
				slides.each(function () {
					var id = this.get('id');
					var bound = Y.Node.create('<div class="' + getClass(Slideshow.NAME, 'slide') + '"></div>');
					this.addClass(getClass(Slideshow.NAME, 'slide', 'content'));
					this.insert(bound, 'before');
					bound.append(this);
					newSlides[id] = bound;
					
					if (this.hasClass('hidden')) {
						this.removeClass('hidden');
						bound.addClass('hidden');
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
				duration: 0.5,
				easing: Y.Easing.easeOutStrong
			});
			
			//On slide change scroll to it
			this.on('slideChange', function (e) {
				if (e.newVal != e.prevVal && !e.silent) {
					this.scrollTo(e.newVal);
				}
			}, this);
		},
		
		syncUI: function () {
			var slideId = this.get('slide'),
				index = Y.Array.indexOf(this.history, slideId);
			
			this.slide_width = null;
			this.slide_width = this._getWidth();
			
			this.get('contentBox').set('scrollLeft', index * this.slide_width);
		},
		
		/**
		 * Scroll to slide
		 * 
		 * @param {Object} slideId
		 */
		scrollTo: function (slideId) {
			var oldSlideId = this.get('slide');
			if (slideId == oldSlideId || !this.anim || !(slideId in this.slides)) return slideId;
			
			//Stop previous animation, otherwise new content may not be shown
			this.anim.stop();
			
			var index = Y.Array.indexOf(this.history, slideId),
				oldIndex = Y.Array.indexOf(this.history, oldSlideId),
				slideWidth = this._getWidth(),
				to = index * slideWidth,
				from = oldIndex * slideWidth;
			
			if (index == -1) {
				index = this.history.length;
				to = index * slideWidth;
				this.history[index] = slideId;
			}
			
			//Show and position new slide
			this.slides[slideId].setStyle('left', index * 100 + '%')
				.removeClass('hidden');
			
			if (index < oldIndex) {
				this.history.splice(index + 1);
			}
			
			if (!this.get('noAnimations')) {
				//When animation ends hide old slide
				this.anim.once('end', function () {
					if (oldSlideId in this.slides) {
						this.slides[oldSlideId].addClass('hidden');
					}
				}, this);
				
				this.anim.set('from', {'scroll': [from, 0]});
				this.anim.set('to', {'scroll': [to, 0]});
				this.anim.run();
			} else {
				if (oldSlideId in this.slides) {
					this.slides[oldSlideId].addClass('hidden');
				}
				this.get('contentBox').set('scrollLeft', to);
			}
			
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
		 * Adds slide to the slideshow
		 * 
		 * @return Slide boundingBox node
		 * @type {Object}
		 */
		addSlide: function (slideId) {
			if (!slideId) return null;
			
			if (!(slideId in this.slides)) {
				var classSlide = getClass(Slideshow.NAME, 'slide'),
					classContent = getClass(Slideshow.NAME, 'slide', 'content'),
					slide = this.slides[slidesId] = Y.Node.create('<div class="hidden ' + classSlide + '"><div id="' + slideId + '" class="' + classContent + '"></div></div>');
				
				this.slides[slideId] = slide;
				this.get('contentBox').append(slide);
				return slide;
			}
			
			return this.slides[slidesId];
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
		 * Returns width of the slide container
		 * 
		 * @return Width of the slide container
		 * @type {Number}
		 */
		_getWidth: function () {
			if (!this.slide_width) {
				this.slide_width = this.get('boundingBox').get('offsetWidth');
			}
			return this.slide_width;
		}
		
	});
	
	
	Supra.Slideshow = Slideshow;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'anim', 'supra.slideshow-css']});