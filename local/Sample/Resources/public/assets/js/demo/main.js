var demo = (function () {
	
	var SAFE_FONTS = ['Arial', 'Tahoma', 'Helvetica', 'sans-serif', 'Arial Black', 'Impact',
		'Trebuchet MS', 'MS Sans Serif', 'MS Serif', 'Geneva', 'Comic Sans MS' /* trololol.... */,
		'Palatino Linotype', 'Book Antiqua', 'Palatino', 'Monaco', 'Charcoal',
		'Courier New', 'Georgia', 'Times New Roman', 'Times',
		'Lucida Console', 'Lucida Sans Unicode', 'Lucida Grande', 'Gadget',
		'monospace'];
	
	var demo = {
		'properties': [],
		'propertiesMap': {},
		
		// Action queue
		'styleQueue': [],
		
		// Font list
		'fontList': null,
		
		// Returns property
		'getProperty': function (property, properties) {
			if (property in this.propertiesMap) {
				return this.propertiesMap[property];
			}
			
			var properties = properties || this.properties,
				i = 0,
				ii = properties.length,
				val = null;
			
			for (; i<ii; i++) {
				if (properties[i].id == property) {
					return this.propertiesMap[property] = properties[i];
				}
				
				if (properties[i].properties) {
					val = this.getProperty(property, properties[i].properties);
					if (val) {
						return this.propertiesMap[property] = val;
					}
				}
			}
			
			return null;
		},
		
		// Returns property value data
		'getPropertyValueData': function (name, id) {
			var property = this.getProperty(name),
				values   = null,
				i        = 0,
				ii       = 0;
			
			if (property && property.values) {
				values = property.values;
				
				for (ii=values.length; i<ii; i++) {
					if (values[i].id == id) {
						return values[i];
					}
				}
			} else {
				return null;
			}
		},
		
		/**
		 * Returns default property value
		 */
		'getDefaultPropertyValue': function (property) {
			var values = this.getProperty('set').values,
				customization = values.length ? values[0].customization : {};
			
			return property in customization ? customization[property] : null;
		},
		
		/**
		 * If less scripts are loaded then set property, otherwise
		 * wait till they are loaded
		 */
		'set': function (property, value, silent) {
			var property_object = this.getProperty(property);
			if (property_object && property_object.type == 'Fonts') {
				this.addFont(value);
				
				property += '_family';
			}
			
			if (!demo.loader.ready) {
				if (!demo.loader.loading) {
					demo.loader.load()
						.done($.proxy(this.applyQueue, this));
				}
				
				this.styleQueue.push(arguments);
			} else {
				demo.less.set(property, value, silent);
			}
		},
		
		/**
		 * Call redraw on less
		 */
		'redraw': function () {
			if (demo.less) {
				demo.less.redraw();
			}
		},
		
		/**
		 * Apply all queued styles 
		 * @private
		 */
		'applyQueue': function () {
			var queue = this.styleQueue,
				i     = 0,
				ii    = queue.length;
			
			for (; i<ii; i++) {
				demo.less.set(queue[i][0], queue[i][1], true);
			}
			
			demo.less.redraw();
			this.styleQueue = [];
		},
		
		/**
		 * Add font
		 */
		'addFont': function (font) {
			var fonts = this.fontList,
				node  = $('link[href*="fonts.googleapis.com"]'),
				uri   = '',
				safe  = SAFE_FONTS;
			
			// Remove safe fonts
			font = font.replace(/\s*,\s*/g, ',').split(',');
			font = $.grep(font, function (font, index) {
				return $.inArray(font, SAFE_FONTS) == -1;
			});
			font = font.join(',');
			
			if (!font) return;
			
			if (!fonts) {
				var family = node.attr('href').match(/family=([^:]*)/i);
				
				if (family) {
					this.fontList = fonts = decodeURI(family[1]).replace('+', ' ').split('|');
				} else {
					this.fontList = fonts = [];
				}
			}
			
			if ($.inArray(font, fonts) == -1) {
				fonts.push(font);
				uri = encodeURI(fonts.join('|').replace(/\s/g, '+'));
				node.attr('href', '//fonts.googleapis.com/css?family=' + uri + ':300,300italic,regular,italic,700,700italic&subset=latin,cyrillic-ext,latin-ext,cyrillic');
			}
		}
		
	};
	
	// Script and CSS loader
	demo.loader = {
		// Scripts and CSS has been loaded
		ready: false,
		
		// Loading
		loading: false,
		
		// Loading icon
		icon: null,
		
		load: function () {
			if (this.loading) {
				return this.loading;
			}
			
			this.icon = $('<div class="demo-loading-icon"></div>');
			this.icon.appendTo($('body'));
			
			if (this.ready) {
				var deferred = $.Deferred();
				deferred.resolve();
				return deferred.promise();
			}
			
			var script   = $('script[type="demo/url"]'),
				link     = $('link[href*="_current.css"]'),
				loading  = this.loading = $.Deferred(),
				deferred = null;
			
			// Load scripts after small timeout to make sure
			// loading icon is visible, becuase if script is cached
			// then it will not have time to actually appear before
			// demo JS starts running
			setTimeout($.proxy(function () {
				deferred = $.ajax({
					url: script.attr('data-src'),
					dataType: 'script'
				});
				
				deferred.done($.proxy(function () {
					// Remove original CSS
					link.remove();
					this.loading = false;
					this.ready = true;
					
					// 
					demo.popup.applyInitialValues();
					
					setTimeout($.proxy(function () {
						this.icon.remove();
					}, this), 250);
					
					loading.resolveWith(this);
				}, this));
				deferred.fail($.proxy(function () {
					loading.rejectWith(this);
				}, this));
			}, this), 250);
			
			return loading.promise();
		}
	};
	
	
	demo.popup = {
		
		container: null,
		popup: null,
		created: false,
		
		inputs: {
			'boxed': null,
			'set': null,
			'pattern': null
		},
		
		applyInitialValues: function () {
			if (this.inputs.boxed) {
				if (this.inputs.boxed.value()) {
					demo.less.set('boxed', true);
				} else {
					demo.less.set('boxed', false);
				}
			}
		},
		
		init: function () {
			var container = $('#customize'),
				popup     = container.find('section.c-popup');
			
			this.container = container;
			this.popup = popup;
			
			// Create inputs for managing
			this.createForm();
			
			// Handle toggle button click
			popup.find('.c-toggle').mousedown($.proxy(this.toggle, this));
			
			// On landing page popup should be opened by default
			if (document.location.pathname == '/') {
				this.toggle();
			}
		},
		
		toggle: function () {
			var container = this.container,
				node = this.popup,
				expand = node.hasClass('c-popup-collapsed'),
				right = expand ? '0px' : '-217px';
			
			container.animate({'right': right}, 'fast');
			node.toggleClass('c-popup-collapsed');
			
			return false;
		},
		
		// Create 'boxed' checkbox input
		createInputBoxed: function () {
			var has_property = demo.getProperty('boxed'),
				body_is_boxed = $('body').hasClass('boxed'),
				input = null,
				node = null;
			
			if (has_property) {
				node = $('#customizeRowLayout').closest('.c-input-checkbox');
				
				input = new demo.Checkbox({
					'node': node,
					'value': body_is_boxed
				});
				input.on('change', function (event, data) {
					demo.set('boxed', data.value);
				});
				
				this.inputs.boxed = input;
			} else {
				// Hide boxed/wide input row
				node = $('#customizeRowLayout').closest('.c-row');
				node.addClass('hidden');
			}
		},
		
		// Create patch input
		createInputPatch: function (node, property) {
			var input = new demo.Patch({
				'node': node,
				'values': demo.getProperty(property).values,
				'background': demo.getDefaultPropertyValue('backgroundColor') || demo.getDefaultPropertyValue('bodyBackgroundColor') || '#ffffff'
			});
			
			input.on('change', function (event, data) {
				if (data.valueData.customization) {
					demo.set(property, data.value, true);
					
					// This input actually affects other properties instead of beeing useful less variable
					var values = data.valueData.customization,
						key    = null,
						value  = null;
					
					for (key in values) {
						value = demo.getPropertyValueData(key, values[key]) || values[key];
						demo.set(key, value, true);
					}
				} else {
					demo.set(property, data.valueData, true);
				}
				
				demo.redraw();
			});
			
			this.inputs[property] = input;
		},
		
		// Load resources and create form
		createForm: function () {
			if (this.created) return;
			
			this.createInputBoxed();
			this.createInputPatch($('#customizeRowSet'), 'set');
			
			this.created = true;
		}
		
	};
	
	// We use timeout to prevent any lag
	setTimeout(function () {
		demo.popup.init();
	}, 1000);
	
	return demo;
})();