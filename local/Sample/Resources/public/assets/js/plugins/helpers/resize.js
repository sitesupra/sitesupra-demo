/**
 * Responsive resize
 * Resize iframe, object or images
 * 
 * @version 1.0.1
 */
define(['jquery', 'plugins/helpers/responsive', 'plugins/helpers/debounce'], function ($) {
    "use strict";
	
	//Elements data property on which widget instance is set
	var DATA_INSTANCE_PROPERTY = 'resize';
	
	function Resize (element, options) {
		this._options = $.extend({}, Resize.defaultOptions, options || {});
		this._node = $(element);
		this._container = this._node.parent();
		this._type = (this._node.is('video, embed, object, iframe') ? 'video' : 'image');
		
		// Attach listeners
		this.update = $.proxy(this.update, this);
		
		if (this._type == 'video') {
			$(window).on('resize', $.debounce(this.update, this, 100, true));
		}  else {
			$.responsive.on('resize', this.update);
		}
		
		if (!this._options.maxWidth) {
			this._options.maxWidth = this._node.attr('width');
		}
		
		if (this.isLoaded()) {
			this._onNodeReady();
		} else {
			this._node.one('load', $.proxy(this._onNodeReady, this));
		}
	}
	
	Resize.defaultOptions = {
		'maxWidth': null,
		'maxHeight': null,
		'minWidth': null,
		'minHeight': null,
		
		'thumbnailMaxWidth': 120,
		'thumbnailMaxHeight': ~~(120 / 1.77777)
	};
	
	Resize.prototype = {
		
		/**
		 * Element node which will be resized
		 * @type {Object}
		 * @private
		 */
		'_node': null,
		
		/**
		 * Resizable element container element
		 * @type {Object}
		 * @private
		 */
		'_container': null,
		
		/**
		 * Proxy element used to fill the space while resizing
		 * @type {Object}
		 * @private
		 */
		'_proxy': null,
		
		/**
		 * Widget options
		 * @type {Object}
		 * @private
		 */
		'_options': null,
		
		/**
		 * Last known width
		 * @type {Number}
		 * @private
		 */
		'_width': 0,
		
		/**
		 * Last known height
		 * @type {Number}
		 * @private
		 */
		'_height': 0,
		
		/**
		 * Original width/height ratio
		 * @type {Number}
		 * @private
		 */
		'_ratio': 0,
		
		/**
		 * Original container width
		 * @type {Number}
		 * @private
		 */
		'_containerWidth': 0,
		
		
		/**
		 * Collect node data
		 * 
		 * @private
		 */
		'_collectNodeData': function () {
			var node = this._node;
			this._width = parseInt(node.attr('width'), 10) || node.width();
			this._height = parseInt(node.attr('height'), 10) || node.height();
			this._ratio = this._width ? (this._width / this._height) : 0;
		},
		
		/**
		 * When node is ready/loaded update it
		 * 
		 * @private
		 */
		'_onNodeReady': function () {
			this._collectNodeData();
			this.update();
		},
		
		/**
		 * Returns container width
		 * 
		 * @private
		 */
		'_getWidth': function () {
			var container = this._container,
				node      = this._node,
				proxy     = this._proxy,
				width     = 0,
				type      = this._type;
			
			if (!proxy) {
				proxy = $('<div />').css({'width': '1px', 'display': 'none'});
				proxy.insertBefore(node);
			}
			
			proxy.css({'display': 'block', 'height': this._height || 1});
			
			if (type != 'video') {
				node.css({
					'position': 'absolute',
					'visibility': 'hidden'
				});
			}
			
			while(container.size() && !container.is(':visible')) {
				container = container.parent();
			}
			
			var css_float = container.css('float');
			if (css_float && css_float !== 'none') {
				// We can't accuaratelly get width, so we use parent width - elements margin, padding and border
				width = container.parent().innerWidth() - (container.outerWidth(true) - container.innerWidth());
			} else {
				width = container.innerWidth();
			}
			
			proxy.css('display', 'none');
			
			if (type != 'video') {
				node.css({
					'position': 'static',
					'visibility': 'visible'
				}); // assume it wasn't relative for now?
			}
			
			return width;
		},
		
		/**
		 * Returns true if node is loaded, valid only for IMG element
		 * 
		 * @returns {Boolean} True if node is loaded, otherwise false
		 */
		'isLoaded': function () {
			var node = this._node,
				tag  = node.get(0).tagName.toUpperCase();
			
			if (tag === 'IMG') {
				return node.get(0).loaded;
			} else {
				return true;
			}
		},
		
		/**
		 * Update node
		 */
		'update': function () {
			var oldContainerWidth = this._containerWidth,
				newContainerWidth = this._getWidth();
			
			// Check if width has actually changed
			if (oldContainerWidth == newContainerWidth) return;
			
			var node    = this._node,
				tag     = node.get(0).tagName.toUpperCase(),
				ratio   = this._ratio,
				width   = newContainerWidth,
				height  = Math.round(width / ratio),
				options = this._options;
			
			// Validate size
			if (options.maxWidth && width > options.maxWidth) {
				width = options.maxWidth;
				height = Math.round(width / ratio);
			} else if (options.minWidth && width < options.minWidth) {
				width = options.minWidth;
				height = Math.round(width / ratio);
			}
			
			if (options.maxHeight && height > options.maxHeight) {
				height = options.maxHeight;
				width  = Math.round(height * ratio);
			} else if (options.minHeight && height < options.minHeight) {
				height = options.maxHeight;
				width  = Math.round(height * ratio);
			}
			
			if (this._width == width && this._height == height) return;
			
			// Update node
			if (tag === 'IMG') {
				node.attr('width', width + 'px').removeAttr('height');
			} else if (tag === 'IFRAME' || tag === 'EMBED' || tag === 'VIDEO') {
				node.attr('width', width + 'px').attr('height', height + 'px');
			} else if (tag === 'OBJECT') {
				node.attr('width', width + 'px').attr('height', height + 'px');
				node.find('EMBED').attr('width', width + 'px').attr('height', height + 'px');
			}
			
			this._width = width;
			this._height = height;
			this._containerWidth = newContainerWidth;
		}
	};
	
    /**
     * Plugin which automatically resizes images, video, iframes and embed
     * content to take container size, while preserving width/height ratio
     *
     * @param {Object} prop Options
     * @returns {Object} Element for chaining
     */
	$.fn.resize = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof Resize.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null,
			args = fn ? Array.prototype.slice.call(arguments, 1) : null;
		
		return this.each(function () {
			
			$(this).find('img, video, embed, object, iframe').each(function () {
				if ($(this).parents('video, embed, object, iframe').size()) return;
				
				var element = $(this),
					widget = element.data(DATA_INSTANCE_PROPERTY);
				
				if (!widget) {
					widget = new Resize (element, $.extend({}, element.data(), options || {}));
					element.data(DATA_INSTANCE_PROPERTY, widget);
				} else if (fn) {
					widget[fn].apply(widget, args);
				}
			
			});
			
		});
	};
	
	/**
     * Video plugin resizes iframes to take container size, while preserving
     * aspect ratio
     *
     * @param {Object} prop Options
     * @returns {Object} Element for chaining
     */
	$.fn.video = function (prop) {
		// A second before initializing
		var defer,
			now,
			test = function (node) {
				node = $(node);
				var iframe = node.is('iframe') ? node : node.find('iframe');
				return !iframe.size() || !iframe.data('src') || (iframe.attr('src') && iframe.attr('src') != 'about:blank');
			};
		
		defer = $(this).filter(function () {
			return !test(this);
		});
		now = $(this).filter(function () {
			return test(this);
		});
		
		// These iframes are ready, initialize immediatelly
		defer.resize(prop);
		
		// Delay init for rest
		setTimeout(function () {
			defer.filter(function () {
				if (!test(this)) {
					var node   = $(this),
						iframe = node.is('iframe') ? node : node.find('iframe');
					
					iframe.attr('src', iframe.data('src'));
					
					return true;
				} else {
					return false;
				}
			}).resize(prop);
		}, 1500);
		
		return $(this);
	};
	
	return Resize;
	
});
