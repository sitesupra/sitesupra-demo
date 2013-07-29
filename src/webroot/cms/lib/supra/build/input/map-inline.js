YUI.add('supra.input-map-inline', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	//Default value
	var DEFAULT_VALUE = {
		'latitude': 56.95,
		'longitude': 24.1,
		'zoom': 14,
		'height': 0
	};
	
	//Minimal map height
	var MAP_MIN_HEIGHT = 80;
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-map-inline';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		// Image node which is edited
		'targetNode': {
			value: null,
			setter: 'createMap'
		},
		'doc': {
			value: null
		},
		'win': {
			value: null
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Constants
		 */
		LABEL_TEMPLATE: '',
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
		 * Info box
		 * @type {Object}
		 * @private
		 */
		info: null,
		
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
		
		/**
		 * Silent value change
		 * @type {Boolean}
		 * @private
		 */
		silent: false,
		
		/**
		 * Event listeners have been attached
		 * @type {Boolean}
		 * @private
		 */
		eventsBinded: false,
		
		/**
		 * Map was created by this widget, not page itself
		 * @type {Boolean}
		 * @private
		 */
		mapSourceSelf: true,
		
		/**
		 * Dragend event listener
		 * @type {Object}
		 * @private
		 */
		dragendListener: null,
		
		/**
		 * Zoom change event listener
		 * @type {Object}
		 * @private
		 */
		zoomChangeListener: null,
		
		/**
		 * Size content box
		 * @type {Object}
		 * @private
		 */
		sizeBox: null,
		
		/**
		 * Width input
		 * @type {Object}
		 * @private
		 */
		inputWidth: null,
		
		/**
		 * Height input
		 * @type {Object}
		 * @private
		 */
		inputHeight: null,
		
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Bind to context
			this._afterValueChange = Y.bind(this._afterValueChange, this);
			
			//Create size input
			this.createSizeInput();
			
			//MapManager.prepare(this.createMap, this);
			this.createMap(this.get('targetNode'));
		},
		
		startEditing: function () {
			if (!this.get('disabled')) {
				this.bindMapEvents();
			}
			
			return Input.superclass.startEditing.apply(this, arguments);
		},
		
		stopEditing: function () {
			Input.superclass.stopEditing.apply(this, arguments);
			this.unbindMapEvents();
		},
		
		/**
		 * Start editing
		 * Enable marker drag and drop
		 */
		bindMapEvents: function () {
			if (this.marker && !this.eventsBinded) {
				var global = this.get('win');
				
				if (this.dragendListener) {
					// Remove in case if old reference
					global.google.maps.event.removeListener(this.dragendListener);
				}
				if (this.zoomChangeListener) {
					// Remove in case if old reference
					global.google.maps.event.removeListener(this.zoomChangeListener);
				}
				
				// Hide info box while editing
				if (this.info) {
					this.info.close();
				}
				
				this.marker.set('draggable', true);
				this.dragendListener = global.google.maps.event.addListener(this.marker, 'dragend', this._afterValueChange);
				this.zoomChangeListener = global.google.maps.event.addListener(this.map, 'zoom_changed', this._afterValueChange);
				
				this.eventsBinded = true;
				
				this.map.set('center', this.marker.get('position'));
				this.map.set('zoom', this.get('value').zoom);
			}
		},
		
		/**
		 * Stop editing
		 * Disable marker drag and drop
		 */
		unbindMapEvents: function () {
			if (this.marker && this.eventsBinded) {
				var global = this.get('win');
				
				if (this.info && this.mapSourceSelf) {
					this.info.open(this.map, this.marker);
				}
				
				global.google.maps.event.removeListener(this.dragendListener);
				global.google.maps.event.removeListener(this.zoomChangeListener);
				
				this.marker.set('draggable', false);
				
				this.zoomChangeListener = null;
				this.dragendListener = null;
				this.eventsBinded = false;
			}
		},
		
		/**
		 * Create map
		 * 
		 * @param {Object} targetNode Element inside which to create map
		 */
		createMap: function (targetNode) {
			if (targetNode && targetNode !== this.get('targetNode')) {
				this.unbindMapEvents();
				this.map = null;
				this.marker = null;
				this.info = null;
				
				MapManager.prepare(this.get('doc'), this.get('win'), function () {
					this._createMap(targetNode);
				}, this);
				
				
				var input_height = this.inputHeight,
					input_width = this.inputWidth,
					value = this.get('value');
				
				if (input_height) {
					if (value && value.height) {
						input_height.set('value', targetNode.get('offsetHeight'));
					} else {
						input_height.set('value', targetNode.get('offsetHeight'));
					}
				}
				
				if (input_width) {
					input_width.set('value', targetNode.get('offsetWidth'));
				}
			}
			return targetNode;
		},
		
		_createMap: function (targetNode) {
			var global = this.get('win'),
				doc    = this.get('doc'),
				value  = this.get('value'),
				cont   = targetNode,
				node   = null,
				map    = null,
				marker = null,
				latlng = null,
				options = null,
				
				g_node = null,
				g_instance = null;
			
			if (!cont) return;
			node = cont.getDOMNode();
			cont.addClass('supra-map');
			
			if (global.jQuery) {
				g_node = global.jQuery(node);
				g_instance = g_node.data('map');
				
				if (g_instance && g_instance.map) {
					// We can get existing map instance created by $.fn.map plugin
					latlng = new global.google.maps.LatLng(value.latitude, value.longitude);
					
					this.map = g_instance.map;
					this.marker = g_instance.marker;
					this.info = g_instance.info;
					this.mapSourceSelf = false;
					
					this.map.set('center', latlng);
					this.map.set('zoom', value.zoom);
					this.marker.set('position', latlng);
					this.marker.set('draggable', true);
					
					if (this.info) {
						// Hide info while editing
						this.info.close();
					}
					
					return;
				}
			}
			
			cont.empty(); // in case there already is a map
			
			latlng = new global.google.maps.LatLng(value.latitude, value.longitude);
			options = {
				zoom: value.zoom,
				center: latlng,
				streetViewControl: false,
				mapTypeId: global.google.maps.MapTypeId.ROADMAP
			};
			
			map = this.map = new global.google.maps.Map(node, options);
			
			//Add marker
			if (!latlng) {
				latlng = map.getCenter();
			}
			marker = this.marker = new global.google.maps.Marker({'position': latlng, 'map': map, 'draggable': true});
			
			this.mapSourceSelf = true;
		},
		
		_handleMapResize: function () {
			var win = this.get('win'),
				map = this.map;
			
			if (win && map) {
				win.google.maps.event.trigger(this.map, "resize");
			}
		},
		
		
		/* -------------------------------------- SIZE -------------------------------------- */
		
		
		createSizeInput: function () {
			var properties = this.getParentWidget('page-content-properties'),
				contentBox = this.get('contentBox'),
				label      = Supra.Intl.get(['inputs', 'resize_map']),
				sizeBox    = this.sizeBox = Y.Node.create('<div class="clearfix su-sizebox"><p class="label">' + label + '</p></div>');
			
			this.sizeBox = Y.Node.create();
			
			// Width
			var width = this.inputWidth = new Supra.Input.String({
				'type': 'String',
				'style': 'size',
				'valueMask': /^[0-9]*$/,
				'label': Supra.Intl.get(['inputs', 'resize_width']),
				'value': 0
			});
			
			width.render(sizeBox);
			width.set('disabled', true);
			
			// Size button
			var btn = new Supra.Button({"label": "", "style": "small-gray"});
				btn.render(sizeBox);
				btn.set("disabled", true);
				btn.addClass("su-button-ratio");
			
			// Height
			var height = this.inputHeight = new Supra.Input.String({
				'type': 'String',
				'style': 'size',
				'valueMask': /^[0-9]*$/,
				'label': Supra.Intl.get(['inputs', 'resize_height']),
				'value': 0
			});
			
			height.render(sizeBox);
			contentBox.prepend(sizeBox);
			
			height.after('valueChange', this._uiOnHeightInputChange, this);
			height.on('input', Supra.throttle(function (e) {
				if (this.inputHeight.get('focused')) {
					this._uiOnHeightInputInput(e.value);
				}
			}, 250, this, true), this);
		},
		
		_uiSetMapHeight: function (height) {
			var targetNode   = this.get('targetNode'),
				height       = Math.max(MAP_MIN_HEIGHT, height),
				input_width  = this.inputWidth,
				input_height = this.inputHeight,
				prev         = this._uiSilentHeightUpdate;
			
			if (targetNode) {
				if (height != targetNode.get('offsetHeight')) {
					targetNode.setStyle('height', height + 'px');
					this._handleMapResize();
				}
			}
			if (input_height && input_height.get('value') != height) {
				this._uiSilentHeightUpdate = true;
				input_height.set('value', height);
				this._uiSilentHeightUpdate = prev;
				
				if (targetNode) {
					input_width.set('value', targetNode.get('offsetWidth'));
				}
			}
		},
		
		/**
		 * Returns map height from target node or input
		 * 
		 * @returns {Number} Map height in pixels
		 * @private
		 */
		_uiGetMapHeight: function () {
			var targetNode = this.get('targetNode'),
				input      = this.inputHeight,
				height     = 0;
			
			if (targetNode) {
				height = targetNode.get('offsetHeight');
			}
			if (input && !height) {
				height = parseInt(input.get('value'), 10) || 0;
			}
			
			return Math.max(height, MAP_MIN_HEIGHT); 
		},
		
		_uiGetMapWidth: function (targetNode) {
			var targetNode = targetNode || this.get('targetNode');
			if (targetNode) {
				return targetNode.get('offsetHeight');
			} else {
				return 0;
			}
		},
		
		_uiOnHeightInputChange: function () {
			if (this._uiSilentHeightUpdate) return;
			this._uiSilentHeightUpdate = true;
			
			var value  = parseInt(this.inputHeight.get('value'), 10) || 0,
				height = Math.max(value, MAP_MIN_HEIGHT);
			
			this._uiSetMapHeight(height);
			this._afterValueChange();
			
			this._uiSilentHeightUpdate = false;
		},
		
		_uiOnHeightInputInput: function (value) {
			var value  = parseInt(this.inputHeight.get('value'), 10) || 0,
				height = Math.max(value, MAP_MIN_HEIGHT),
				targetNode = this.get('targetNode');
			
			if (targetNode) {
				targetNode.setStyle('height', height + 'px');
				this._handleMapResize();
			}
		},
		
		
		/* -------------------------------------- ATTRIBUTES -------------------------------------- */
		
		
		/**
		 * Disabled attribute change
		 * 
		 * @param {Boolean} disabled
		 * @returns {Boolean} New attribute value
		 * @private
		 */
		/*_setDisabled: function (disabled) {
			if (disabled === true) {
				this.stopEditing();
			} else if (disabled === false){
				this.startEditing();
			}
			
			return false;
		},*/
		
		/**
		 * Value setter
		 * 
		 * @param {Object} value Object with latitude and longitude properties
		 * @returns New attribute value
		 * @private
		 */
		_setValue: function (value) {
			if (this.silent) return value;
			
			var latlng = null,
				map = this.map,
				marker = this.marker,
				global = this.get('win'),
				input_height = this.inputHeight;
			
			value = Supra.mix({}, DEFAULT_VALUE, this.get('defaultValue'), value);
			
			// Validate values
			value.zoom = parseInt(value.zoom, 10) || DEFAULT_VALUE.zoom;
			value.latitude = parseFloat(value.latitude) || 0;
			value.longitude = parseFloat(value.longitude) || 0; 
			value.height = parseInt(value.height, 10) || 0;
			
			if (map && marker) {
				latlng = new global.google.maps.LatLng(value.latitude, value.longitude);
				map.setCenter(latlng);
				map.setZoom(value.zoom || DEFAULT_VALUE.zoom);
				marker.setPosition(latlng);
			}
			
			if (!value.height) {
				// Old version didn't had height, for compatibiliy take it from content
				value.height = this._uiGetMapHeight();
			}
			
			this._uiSetMapHeight(value.height);
			
			return value;
		},
		
		/**
		 * Value getter
		 * 
		 * @returns {Object} Object with latitude and longitude properties
		 * @private
		 */
		_getValue: function (value) {
			var value = Supra.mix({}, DEFAULT_VALUE, this.get('defaultValue'), value),
				point = null,
				map = this.map,
				marker = this.marker,
				input = this.inputHeight;
			
			if (map && marker) {
				point = marker.getPosition();
				value.latitude = point.lat();
				value.longitude = point.lng();
			}
			if (input) {
				value.height = Math.max(parseInt(input.get('value'), 10) || 0, MAP_MIN_HEIGHT);
			}
			
			return value;
		},
		
		/**
		 * After value change trigger event
		 */
		_afterValueChange: function () {
			var value = this.get('value');
			
			if (this.map) {
				value.zoom = this.map.get('zoom');
			}
			
			this.silent = true;
			this.set('value', value);
			this.silent = false;
			
			this.fire('change', {'value': value});
		}
		
	});
	
	Supra.Input.InlineMap = Input;
	
	
	/**
	 * Handle loading Google Maps API and call callback when done
	 */
	var MapManager = Supra.Input.MapManager = {
		
		/**
		 * Property name which is set on document
		 */
		DOCUMENT_PROPERTY: 'supraGoogleMapsGuid',
		
		/**
		 * Function prefix which is set on window
		 */
		WINDOW_FUNCTION: 'supraGoogleMapsReady',
		
		/**
		 * Unique ID counter
		 */
		guid: 1,
		
		/**
		 * Maps
		 */
		maps: {},
		
		/**
		 * List of event listeners
		 * @type {Array}
		 */
		listeners: [],
		
		/**
		 * 
		 */
		prepare: function (doc, win, callback, context) {
			var guid = doc[this.DOCUMENT_PROPERTY],
				map  = null;
			
			if (!guid) {
				guid = this.create(doc, win);
			}
			
			map = this.maps[guid];
			
			if (map.loaded && callback) {
				callback.call(context || window);
			} else if (map.loading && callback) {
				map.listeners.push({'fn': callback, 'obj': context || window});
			}
		},
		
		create: function (doc, win) {
			// Create callback
			var guid = this.guid++,
				fn   = this.WINDOW_FUNCTION + guid,
				self = this;
			
			doc[this.DOCUMENT_PROPERTY] = guid;
			win[fn] = function () { self.ready(guid); };
			
			// Create object
			this.maps[guid] = {
				'loaded': false,
				'loading': true,
				'listeners': []
			};
			
			if (win.google && (win.google.maps || win.google.load)) {
				if (win.google.maps) {
					// Google Maps loaded
					this.maps[guid].loading = false;
					this.maps[guid].loaded = true;
				} else {
					// Google API loaded, load Maps
					win.google.load("maps", "3",  {callback: win[fn], other_params:"sensor=false"});
				}
			} else {
				// Check if there already is script included
				if (Y.Node(doc).one('script[src*="//maps.googleapis.com/maps/api/js"]')) {
					// We don't have access to callback (we shouldn't touch it),
					// so we use timeout to check when it's loaded
					this.checkReadyRetries = 50;
					this.checkReadyTimer = Y.later(100, this, this.checkReady, [doc, win, guid], true);
				} else {
					// Load Google Maps
					var script = doc.createElement('script');
						script.type = 'text/javascript';
						script.src  = document.location.protocol + '//maps.googleapis.com/maps/api/js?sensor=false&callback=' + fn;
					
					doc.body.appendChild(script);
				}
			}
			
			return guid;
		},
		
		/**
		 * Continuously check if google maps has been loaded
		 * 
		 * @param {Object} doc Document element
		 * @param {Object} win Window element
		 * @param {Object} guid Map unique ID
		 * @private
		 */
		checkReady: function (doc, win, guid) {
			if (win.google && win.google.maps) {
				// Google Maps loaded
				this.checkReadyTimer.cancel();
				this.ready(guid);
			} else {
				// Check if we need to stop trying
				this.checkReadyRetries--;
				if (!this.checkReadyRetries) {
					this.checkReadyTimer.cancel();
				}
			}
		},
		
		ready: function (guid) {
			var map = this.maps[guid],
				listeners = map.listeners,
				i = 0,
				ii = listeners.length;
			
			map.listeners = [];
			map.loading = false;
			map.loaded = true;
			
			for(; i<ii; i++) {
				listeners[i].fn.call(listeners[i].obj);
			}
		}
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});