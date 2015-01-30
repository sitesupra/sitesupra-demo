/**
 * Google maps block
 * 
 * @version 1.0.2
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'frontend/util/debounce'], factory);
	} else if (typeof module !== "undefined" && module.exports) {
		// CommonJS
		module.exports = factory(jQuery, debounce);
	} else { 
        // AMD is not supported, assume all required scripts are already loaded
        factory(jQuery, debounce);
    }
}(this, function ($, debounce) {
    'use strict';
	
    // This data namespace should not be changed, because CMS checks element
    // for data with this name for existing google.Map instance
    // If CMS won't be able to find it, then new one will be created
    // destroying old one
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
        
        // Map will not be created if it already exists, it could be
        // created by CMS in page editing mode
        if (this._createMap()) {
            this._createMarker();
    		
            $(window).on('resize', debounce(this.update, this, 100, true));
        }
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
			
            if (this.element.find('.gm-style').length) {
                // Map already has been created, exit
                return false;
            }
            
			latlng = new google.maps.LatLng(this.options.latitude, this.options.longitude);
			
			options = {
				zoom: this.options.zoom || DEFAULTS.zoom,
				center: latlng,
				streetViewControl: false,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			
			this.element.append(node);
			map = this.map = new google.maps.Map(node.get(0), options);
            
            return true;
		},
		
		'_createMarker': function () {
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
					// Latitude and lonitude is on the inner element
                    var inner = element.children(),
                        data = $.extend({}, element.data(), inner.data(), options || {});
                    
					widget = new GoogleMap (inner, data);
                    if (widget) {
                        element.data(DATA_INSTANCE_PROPERTY, widget);
                        inner.data(DATA_INSTANCE_PROPERTY, widget);
                    }
				});
			} else {
				if (fn) {
					widget[fn].call(widget);
				}
			}
		});
	};
	
	
	// Google maps API loading
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
            var ready,
                interval;
            
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
                    if (google.maps.LatLng) {
                        this._ready();
                    } else {
                        ready = $.proxy(this._ready, this);
                        interval = setInterval(function () {
                            if (google.maps.LatLng) {
                                clearInterval(interval);
                                ready();
                            }
                        });
                    }
				} else if (google.load) {
					this._loading = true;
					google.load("maps", "3",  {callback: $.proxy(this._ready, this), other_params:"sensor=false"});
				}
			} else {
                // Check if there already is script included
				if ($('script[src*="//maps.googleapis.com/maps/api/js"]').length) {
                    ready = $.proxy(this._ready, this);
                    interval = setInterval(function () {
                        if (google.maps.LatLng) {
                            clearInterval(interval);
                            ready();
                        }
                    });
                } else {
    				this._loading = true;
    				var script = document.createElement('script');
    					script.type = 'text/javascript';
    					script.src  = document.location.protocol + '//maps.googleapis.com/maps/api/js?sensor=false&callback=$.googleMap._ready';
    				
    				document.body.appendChild(script);
                }
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
			
			for (; i < ii; i++) {
				callbacks[i]();
			}
			
			this._callbacks = [];
			this._loading = false;
			this._loaded = true;
		}
	};
	
	//requirejs
	return GoogleMap;
	
}));
