/**
 * Google maps block
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'refresh/refresh', 'plugins/helpers/responsive'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var DATA_INSTANCE_PROPERTY = 'map';
	
	//Default widget options
	var DEFAULTS = {
		'latitude': 0,
		'longitude': 0,
		'zoom': 14,
		'markerText': ''
	};
	
	function GoogleMap (element, options) {
		this.options = $.extend({}, DEFAULTS, options || {});
		this.element = element;
		this._createMap();
		this._createMarker();
		
		this.update = $.proxy(this.update, this);
		$.responsive.on('resize', this.update);
	}
	
	GoogleMap.prototype = {
		
		/**
		 * Options
		 * @type {Object}
		 */
		'options': null,
		
		/**
		 * Container element
		 * @type {Object}
		 */
		'element': null,
		
		/**
		 * Map instance
		 * @type {Object}
		 */
		'map': null,
		
		/**
		 * Marker instance
		 * @type {Object}
		 */
		'marker': null,
		
		/**
		 * InfoWindow instance
		 * @type {Object}
		 */
		'info': null,
		
		/**
		 * Create map
		 * 
		 * @private
		 */
		'_createMap': function () {
			var map = null,
				marker = null,
				options = null,
				latlng = null,
				node = $('<div style="width: 100%; height: 100%;"></div>');
			
			latlng = new google.maps.LatLng(this.options.latitude, this.options.longitude);
			
			options = {
				zoom: this.options.zoom || DEFAULTS.zoom,
				center: latlng,
				streetViewControl: false,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			
			this.element.append(node);
			map = this.map = new google.maps.Map(node.get(0), options);
		},
		
		'_createMarker': function (latlng) {
			var latlng = new google.maps.LatLng(this.options.latitude, this.options.longitude),
				marker = new google.maps.Marker({'position': latlng, 'map': this.map});
			
			google.maps.event.addListener(marker, 'click', $.proxy(this.openInfo, this));
			google.maps.event.addListenerOnce(this.map, 'idle', $.proxy(this._createInfoBox, this));
			
			this.marker = marker;
		},
		
		'_createInfoBox': function () {
			var html = this.options.markerText,
				options = null,
				info = null;
			
			if (html) {
				
				info = new google.maps.InfoWindow({
		            content: '<div class="map-info-box" style="min-width: 100px;">' + html + '</div>',
		            boxStyle: {
		            	opacity: 0.75,
		            	width: '200px'
		            },
		            maxWidth: 400,
		            disableAutoPan: false
		        });
				
				this.info = info;
				this.openInfo();
			}
		},
		
		'openInfo': function () {
			if (this.info) {
				this.info.open(this.map, this.marker);
			}
		},
		
		/**
		 * On resize update map
		 */
		'update': function () {
			google.maps.event.trigger(this.map, 'resize');
		},
		
		/**
		 * Destroy map
		 */
		'destroy': function () {
			this.element.empty();
			this.info = null;
			this.marker = null;
			this.map = null;
		}
		
	};
	
	/*
	 * jQuery plugin
	 * Creates map or apply options or call a function
	 */
	$.fn.googleMap = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof GoogleMap.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null;
		
		return this.each(function () {
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				element.data(DATA_INSTANCE_PROPERTY, {});
				
				$.googleMap.load(function () {
					
					widget = new GoogleMap (element, $.extend({}, element.data(), options || {}));
					element.data(DATA_INSTANCE_PROPERTY, widget);
					
				});
			} else {
				if (fn) {
					widget[fn].call(widget);
				}
			}
		});
	};
	
	
	// Google maps loading
	$.googleMap = {
		// Already loading map scripts
		'_loading': false,
		
		// Already loaded map scripts
		'_loaded': false,
		
		// Callbacks
		'_callbacks': [],
		
		/**
		 * Laod Google Maps API and call callback when done
		 * 
		 * @param {Function} callback Callback function
		 */
		'load': function (callback) {
			if (this._loaded) {
				return callback();
			}
			
			this._callbacks.push(callback);
			
			if (this._loading) {
				return;
			}
			
			if (typeof google !== 'undefined' && !(google instanceof Node)) {
				// IE11 maps DOM elements with name to window object
				if (google.maps) {
					callback();
				} else if (google.load) {
					this._loading = true;
					google.load("maps", "3",  {callback: $.proxy(this._ready, this), other_params:"sensor=false"});
				}
			} else {
				this._loading = true;
				var script = document.createElement('script');
					script.type = 'text/javascript';
					script.src  = document.location.protocol + '//maps.googleapis.com/maps/api/js?sensor=false&callback=$.googleMap._ready';
				
				document.body.appendChild(script);
			}
			
		},
		
		/**
		 * Google Maps API finished loading, call callbacks
		 * 
		 * @private
		 */
		'_ready': function () {
			var callbacks = this._callbacks,
				i = 0,
				ii = callbacks.length;
			
			for (; i<ii; i++) {
				callbacks[i]();
			}
			
			this._callbacks = [];
			this._loading = false;
			this._loaded = true;
		}
	};
	
	
	
	//$.refresh implementation
	if ($.refresh) {
		$.refresh.on('refresh/googleMap', function (event, info) {
			info.target.googleMap(info.target.data());
		});
		
		$.refresh.on('cleanup/googleMap', function (event, info) {
			var map = info.target.data(DATA_INSTANCE_PROPERTY);
			if (map && map.destroy) {
				map.destroy();
				info.target.data(DATA_INSTANCE_PROPERTY, null)
			}
		});
	}
	
	//requirejs
	return GoogleMap;
	
}));