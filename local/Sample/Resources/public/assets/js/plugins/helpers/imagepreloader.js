/**
 * jQuery plugin to preload images
 * 
 * @version 1.0.2
 * @description
 * $.preload preloads one or more images and returns $.Deferred promise
 * If multiple images are being preloaded then promise is rejected if one of them fails
 * <code>
 *   $.preload('px.gif').done(function (url) {
 * 	   alert('Done preloading ' + url);
 *   });
 * </code>
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
		
	var Preloader = {
		
		/**
		 * List of image IDs which needs to be preloaded
		 * @type {Array}
		 * @private
		 */
		_queue: [],
		
		/**
		 * List of images
		 * @type {Object}
		 * @private
		 */
		_images: {},
		
		/**
		 * Active concurrent connection count
		 * @type {Number}
		 * @private
		 */
		_concurrent_count: 0,
		
		/**
		 * Preload next image
		 * 
		 * @private
		 */
		_next: function () {
			if (this._concurrent_count >= $.preload.CONCURRENT_CONNECTIONS || !this._queue.length) return;
			
			var image = this._images[this._queue.shift()],
				self = this;
			
			function ready () {
				image.element.remove();
				image.deferred.resolve(image.url);
				
				self._concurrent_count--;
				self._next();
			}
			function reject () {
				image.element.remove();
				image.deferred.reject(image.url);
				
				self._concurrent_count--;
				self._next();
			}
			
			image.element = $('<img />');
			image.element.attr({'alt': '', 'src': image.url});
			image.element.css({'position': 'absolute', 'left': '-9000px', 'top': '-9000px'});
			image.element.appendTo(document.body);
			this._concurrent_count++;
			
			//Add listener
			if (image.element.get(0).complete) {
				ready();
			} else {
				image.element.bind('load', ready);
				image.element.bind('error', reject);
			}
			
			// Start loading next image if concurrent connection count is not max
			this._next();
		},
		
		/**
		 * Returns image element by URL
		 * 
		 * @param {String} url
		 * @returns {Object|Null} Image element, jQuery instance
		 */
		getElement: function (url) {
			if (url in this._images) {
				return this._images[url].element;
			} else {
				return null;
			}
		},
		
		/**
		 * Preload image
		 * 
		 * @param {String|Array} url Image url or array of image urls
		 * @returns {Object} jQuery.Deferred objects promise
		 */
		preload: function (url) {
			var deferred = null;
			
			// Argument is array of items
			if ($.isArray(url)) {
				var i = 0,
					count = url.length,
					deferreds = [];
				
				for (; i<count; i++) {
					deferreds.push($.preload(url[i]));
				}
				
				return $.when.apply($, deferreds);
			}
			
			// Argument is single url
			if (url in this._images) {
				var image = this._images[url];
				deferred = image.deferred;
			} else {
				deferred = $.Deferred();
				var image = this._images[url] = {
					'url': url,
					'element': null,
					'deferred': deferred
				};
				
				this._queue.push(url);
				this._next();
			}
			
			return deferred.promise();
		}
	};
	
	/**
	 * jQuery namespace for preloading images
	 */
	$.preload = $.proxy(Preloader.preload, Preloader);
	
	/**
	 * jQuery namespace for function to get image element from url when preloading is done
	 */
	$.preload.element = $.proxy(Preloader.getElement, Preloader);
	
	/**
	 * jQuery plugin for preloading images by taking image data-src and replacing with src
	 */
	$.fn.preload = function () {
		var deferreds = [];
		
		$(this).each(function () {
			var node = $(this),
				src = node.data('src') || node.attr('src');
			
			if (src) {
				deferreds.push($.preload(src).done(function () {
					node.attr('src', src);
				}));
			}
		});
		
		return $.when.apply($, deferreds);
	};
	
	/**
	 * Concurrent connection count
	 * @const
	 * @type {Number}
	 */
	$.preload.CONCURRENT_CONNECTIONS = 4;
	
	return $.preload;

}));