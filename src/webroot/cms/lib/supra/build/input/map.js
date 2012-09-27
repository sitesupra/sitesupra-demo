YUI.add('supra.input-map', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Map manager
	var MapManager = Supra.Input.MapManager = {
		/**
		 * Map API is being loaded
		 * @type {Boolean}
		 */
		loading: false,
		
		/**
		 * Map API is loaded
		 * @type {Boolean}
		 */
		loaded: false,
		
		/**
		 * List of event listeners
		 * @type {Array}
		 */
		listeners: [],
		
		/**
		 * 
		 */
		prepare: function (callback, context) {
			if (this.loaded || this.loading) {
				if (this.loaded && callback) {
					callback.call(context || window);
				} else if (this.loading && callback) {
					this.listeners.push({'fn': callback, 'obj': context || window});
				}
				
				//Already loading or loaded
				return;
			}
			
			this.loading = true;
			
			if (callback) {
				this.listeners.push({'fn': callback, 'obj': context || window});
			}
			
			var script = document.createElement('script');
				script.type = 'text/javascript';
				script.src  = 'http://maps.googleapis.com/maps/api/js?sensor=true&callback=Supra.Input.MapManager.ready';
			
			document.body.appendChild(script);
		},
		
		ready: function () {
			var listeners = MapManager.listeners,
				i = 0,
				ii = listeners.length;
			
			MapManager.listeners = [];
			MapManager.loading = false;
			MapManager.loaded = true;
			
			for(; i<ii; i++) {
				listeners[i].fn.call(listeners[i].obj);
			}
		}
	};
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-map';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Button is used instead of input
		 */
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		/**
		 * Map instance
		 * @type {Object}
		 * @private
		 */
		map: null,
		
		/**
		 * Map marker
		 * @type {Object}
		 * @private
		 */
		marker: null,
		
		/**
		 * Location cache
		 * @type {Array}
		 * @private
		 */
		value: null,
		
		/**
		 * Map node
		 * @type {HTMLElement}
		 * @private
		 */
		node: null,
		
		
		renderUI: function () {
			//Create map
			Input.superclass.renderUI.apply(this, arguments);
			
			var node = Y.Node.create('<div></div>');
			node.setStyle('height', '300px');
			this.get('boundingBox').append(node);
			this.node = node.getDOMNode();
			
			MapManager.prepare(this.createMap, this); 
		},
		
		/**
		 * Create map
		 */
		createMap: function () {
			if (this.map) return;
			
			if (!this.value) {
				this.value = this.get('defaultValue') || [0, 0];
			}
			
			var latlng = new google.maps.LatLng(this.value[0], this.value[1]),
				node = this.node,
				myOptions = {
					zoom: 8,
					center: latlng,
					streetViewControl: false,
					mapTypeId: google.maps.MapTypeId.ROADMAP
				},
				map,
				marker;
			
			map = this.map = new google.maps.Map(node, myOptions);
			
			//Add marker
			if (!latlng) {
				latlng = map.getCenter();
			}
			marker = this.marker = new google.maps.Marker({'position': latlng, 'map': map, 'draggable': true});
			
			//On marker drag trigger change event
			google.maps.event.addListener(marker, 'dragend', Y.bind(this._afterValueChange, this));
		},
		
		/**
		 * Value setter
		 * 
		 * @param {Array} data Latitude and longitude
		 * @private
		 */
		_setValue: function (data) {
			this.value = data;
			
			if (this.map) {
				if (data) {
					var latlng = new google.maps.LatLng(data[0], data[1]);
					this.map.setCenter(latlng);
					this.marker.setPosition(latlng);
				}
			}
			
			return data;
		},
		
		/**
		 * Value getter
		 * 
		 * @return Latitude and longitude
		 * @type {Array}
		 * @private
		 */
		_getValue: function () {
			if (!this.map || !this.marker) return this.value;
			
			var point = this.marker.getPosition();
			return point ? [point.lat(), point.lng()] : this.value;
		},
		
		/**
		 * After value change trigger event
		 */
		_afterValueChange: function () {
			this.fire('change', {'value': this.get('value')});
		}
		
	});
	
	Supra.Input.Map = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});