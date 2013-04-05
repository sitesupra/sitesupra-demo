YUI.add('supra.input-map', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
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
			
			Supra.Input.MapManager.prepare(document, window, this.createMap, this); 
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