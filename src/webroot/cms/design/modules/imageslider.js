//Invoke strict mode
"use strict";
	
YUI.add("website.imageslider", function (Y) {
	
	
	/*
	 * Add support for additional key names
	 */
	var ARROW_LEFT  = Y.Node.DOM_EVENTS.key.eventDef.KEY_MAP.arrowleft = 37;
	var ARROW_RIGHT = Y.Node.DOM_EVENTS.key.eventDef.KEY_MAP.arrowright = 39;
	
	/*
	 * Template used to fill image list
	 */
	var TEMPLATE = Supra.Template.compile("\
						{% for key, image in images %}\
							<div class=\"item\">\
								<div></div>\
								<img src=\"{{ image.image }}\" alt=\"\" onload=\"Supra.ImageSlider.fixImage(this);\"/>\
								<p>{{ image.title }}</p>\
							</div>\
						{% endfor %}\
					");
	
	var CSS_PROPERTIES = [
		{"zIndex": 0},
		{"zIndex": 1},
		{"zIndex": 2},
		{"zIndex": 1},
		{"zIndex": 0}
	];
	
	var TRANSFORMATIONS = [
		{"transform": "scale(0.6) translate(-360px, 0)", "opacity": 0},
		{"transform": "scale(0.8) translate(-180px, 0)", "opacity": 1},
		{"transform": "scale(1.0) translate(0px, 0px)"},
		{"transform": "scale(0.8) translate(180px, 0)", "opacity": 1},
		{"transform": "scale(0.6) translate(360px, 0)", "opacity": 0}
	];
	
	function ImageSlider (config) {
		ImageSlider.superclass.constructor.apply(this, arguments);
		
		this.init.apply(this, arguments);
	}
	
	ImageSlider.NAME = "image-slider";
	ImageSlider.CSS_PREFIX = "su-" + ImageSlider.NAME;
	
	ImageSlider.ATTRS = {
		//Image list
		"images": {
			"value": [],
			"setter": "_setImages"
		},
		//Image index which is centered
		"image": {
			"value": 0
		},
		//Slider width
		"width": {
			"value": 0
		},
		//Slider height
		"height": {
			"value": 0
		},
		//Animation duration
		"duration": {
			"value": 0.5
		},
		//Disabled state
		"disabled": {
			"value": false,
			"setter": "_setDisabled"
		}
	};
	
	Y.extend(ImageSlider, Y.Widget, {
		
		/**
		 * Image nodes
		 * @type {Array}
		 * @private
		 */
		nodes: null,
		
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			
			this._onKeyProxy = Y.bind(this._onKey, this);
			this._setImages(this.get("images"));
			
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			
			
			
		},
		
		/**
		 * Sync attribute values with UI state
		 * 
		 * @private
		 */
		syncUI: function () {
			var width = this.get("width"),
				height = this.get("height");
			
			this.get("boundingBox").setStyles({
				"width": width ? width : "100%",
				"height": height ? height : "100%"
			});
			
		},
		
		
		
		/*
		 * ---------------------------------- PRIVATE ---------------------------------
		 */
		
		
		/**
		 * Render image list
		 * 
		 * @param {Array} image Images
		 * @private
		 */
		_renderImageList: function (images) {
			var contentBox = this.get("contentBox");
			
			//Remove all existing images
			if (this.nodes) {
				this.nodes.remove();
			}
			
			//Fill
			contentBox.set("innerHTML", TEMPLATE({"images": images}));
			
			this.nodes = contentBox.get("children");
			
			//Set initial styles
			var nodes = this.nodes,
				node  = null,
				i     = 0,
				ii    = nodes.size(),
				ind   = 0;
			
			for(; i<ii; i++) {
				ind = (i < 2 ? i : 2) + 2;
				node = nodes.item(i);
				
				node.setStyles(CSS_PROPERTIES[ind]);
				node.setStyles(TRANSFORMATIONS[ind]);
				
				//Box shadow
				if (i == 0) node.addClass('item-center');
				if (i == 1) node.addClass('item-side');
				
				//Overlay
				node.one('div').setStyle('opacity', i == 0 ? 0 : 0.27);
			}
			
			this.set("image", 0);
		},
		
		/**
		 * Slide images
		 * 
		 * @param {Number} direction Direction, 1 to slide to next image, -1 to slide to previous image
		 */
		_slide: function (direction) {
			var index = this.get("image") + parseInt(direction, 10),
				count = this.get("images").length,
				nodes = this.nodes,
				node  = null,
				duration = this.get("duration");
			
			//Validate index
			if (index < 0 || index >= count) return;
			
			if (direction == 1) {
				//Slide to next image
				var from  = Math.max(0, index - 2),
					to    = Math.min(count - 1, index + 1),
					i     = from,
					prop  = {},
					trans = {};
				
				for(; i<=to; i++) {
					prop = {};
					node = nodes.item(i);
					
					if (i == index - 2) {
						//From small into invisible
						trans = TRANSFORMATIONS[0];
						prop  = TRANSFORMATIONS[1];
					} else if (i < index) {
						//From center into small
						trans = TRANSFORMATIONS[1];
						prop  = TRANSFORMATIONS[2];
					} else if (i == index) {
						//From small into center
						trans = TRANSFORMATIONS[2];
						prop  = TRANSFORMATIONS[3];
					} else {
						//From invisible into small
						trans = TRANSFORMATIONS[3];
						prop  = TRANSFORMATIONS[4];
					}
					
					trans = Supra.mix({
						duration: duration,
						easing: "ease-in-out"
					}, trans);
					
					node.setStyles(prop);
					node.transition(trans);
				}
				
			} else {
				//Slide to previous image
				var from  = Math.max(0, index - 1),
					to    = Math.min(count - 1, index + 2),
					i     = from,
					prop  = {},
					trans = {},
					z_from = 0,
					z_to  = 0;
				
				for(; i<=to; i++) {
					node = nodes.item(i);
					classname = "";
					
					if (i == index - 1) {
						//From invisible into small
						trans = TRANSFORMATIONS[1];
						prop  = TRANSFORMATIONS[0];
						z_from = 0;
						z_to
					} else if (i == index) {
						//From small into center
						trans = TRANSFORMATIONS[2];
						prop  = TRANSFORMATIONS[1];
					} else if (i == index + 1) {
						//From center into small
						trans = TRANSFORMATIONS[3];
						prop  = TRANSFORMATIONS[2];
					} else {
						//From small into invisible
						trans = TRANSFORMATIONS[4];
						prop  = TRANSFORMATIONS[3];
					}
					
					trans = Supra.mix({
						duration: duration,
						easing: "ease-in-out"
					}, trans);
					
					node.setStyles(prop);
					node.transition(trans);
				}
			}
			
			//After half animation fix styles
			//Y.later(~~(duration / 2 * 1000), this, this._slideFixStyles, [[from, to, index]]);
			
			//It looks better if done immediatelly
			this._slideFixStyles([from, to, index]);
			
			//Update attributes
			this.set("image", index);
		},
		
		/**
		 * Fix slide styles
		 * 
		 * @param {Array} params Array of params: from, to, index
		 * @private
		 */
		_slideFixStyles: function (params) {
			var from  = params[0],
				to    = params[1],
				index = params[2],
				i     = from,
				nodes = this.nodes,
				node  = null,
				diff  = 0,
				
				zindex  = 0,
				opacity = 0,
				classname = '';
			
			for(; i<=to; i++) {
				diff = Math.abs(index - i);
				if (diff == 0) {
					zindex = 2;
					opacity = 0;
					classname = 'item-center';
				} else if (diff == 1) {
					zindex = 1;
					opacity = 0.27;
					classname = 'item-side';
				} else {
					zindex = 0;
					opacity = 0.27;
					classname = '';
				}
				
				//z-index & opacity
				node = nodes.item(i);
				node.setStyle('zIndex', zindex);
				node.one('div').setStyle('opacity', opacity);
				
				//Box shadow
				node.removeClass('item-center').removeClass('item-side');
				if (classname) node.addClass(classname);
			}
		},
		
		/**
		 * Handle key press
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onKey: function (e) {
			if (!e.target.test('input,textarea,select,button')) {
				if (e.keyCode == ARROW_LEFT) {
					this.previousImage();
				} else if (e.keyCode == ARROW_RIGHT) {
					this.nextImage();
				}
			}
		},
		
		/**
		 * _onKey function binded to current instance
		 * 
		 * @private
		 */
		_onKeyProxy: function () {},
		
		
		/*
		 * ---------------------------------- ATTRIBUTES ---------------------------------
		 */
		
		
		/**
		 * Images attribute setter
		 * 
		 * @param {Array} images Image list, each image is object with image and title properties
		 * @return Array of images
		 * @type {Array}
		 * @private
		 */
		_setImages: function (images) {
			if (!Y.Lang.isArray(images)) {
				images = [];
			}
			
			var out = [],
				i   = 0,
				ii  = images.length;
			
			for(; i<ii; i++) {
				if (Y.Lang.isObject(images[i]) && Y.Lang.isString(images[i].image)) {
					out.push(images[i]);
					
					if (typeof images[i].title != "string") images[i].title = "";
				}
			}
			
			this._renderImageList(out);
			
			return out;
		},
		
		/**
		 * Disabled attribute setter
		 * 
		 * @param {Boolean} value New disabled state value
		 * @return Disabled state value
		 * @type {Boolean}
		 * @private
		 */
		_setDisabled: function (disabled) {
			var prev = this.get('disabled');
			if (prev != disabled) {
				if (disabled) {
					Y.detach('key', this._onKeyProxy);
				} else {
					Y.on('key', this._onKeyProxy, document, 'arrowleft,arrowright');
				}
			}
			
			return disabled;
		},
		
		
		/*
		 * ---------------------------------- API ---------------------------------
		 */
		
		
		/**
		 * Slide to next image
		 * 
		 * @return Supra.ImageSlider instance for chaining
		 * @type {Object}
		 */
		nextImage: function () {
			this._slide(1);
			return this;
		},
		
		/**
		 * Slide to previous image
		 * 
		 * @return Supra.ImageSlider instance for chaining
		 * @type {Object}
		 */
		previousImage: function () {
			this._slide(-1);
			return this;
		}
		
	});
	
	
	/**
	 * Fixed image position
	 * Static function
	 * 
	 * @param {HTMLElement} image Image element
	 */
	ImageSlider.fixImage = function (image) {
		var node = new Y.Node(image),
			width = node.get("offsetWidth"),
			height = node.get("offsetHeight");
		
		//Center image
		node.ancestor().setStyles({
			'marginTop': ~~(-height / 2) + 'px',
			'marginLeft': ~~(-width / 2) + 'px'
		});
	};
	
	Supra.ImageSlider = ImageSlider;
	
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["widget", "transition"]});