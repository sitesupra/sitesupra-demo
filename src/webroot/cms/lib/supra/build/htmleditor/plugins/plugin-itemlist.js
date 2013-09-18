YUI().add('supra.htmleditor-plugin-itemlist', function (Y) {
	
	//Constants
	var HTMLEDITOR_TOOLBAR = 'itemlist';
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_STRING, Supra.HTMLEditor.MODE_TEXT, Supra.HTMLEditor.MODE_BASIC, Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	Supra.HTMLEditor.addPlugin('itemlist', defaultConfiguration, {
		
		
		/* --------------------------- TOOLBAR --------------------------- */
		
		
		/**
		 * Show itemlist toolbar
		 */
		showToolbar: function () {
			var toolbar = this.htmleditor.get('toolbar'),
				orientation = this.getOptions().orientation;
			
			if (!toolbar.isGroupVisible(HTMLEDITOR_TOOLBAR)) {
				toolbar.showGroup(HTMLEDITOR_TOOLBAR);
				
				if (orientation == 'horizontal') {
					toolbar.getButton('itemlist-row-before').hide();
					toolbar.getButton('itemlist-row-delete').hide();
					toolbar.getButton('itemlist-row-after').hide();
					toolbar.getButton('itemlist-column-before').show();
					toolbar.getButton('itemlist-column-delete').show();
					toolbar.getButton('itemlist-column-after').show();
				} else {
					toolbar.getButton('itemlist-row-before').show();
					toolbar.getButton('itemlist-row-delete').show();
					toolbar.getButton('itemlist-row-after').show();
					toolbar.getButton('itemlist-column-before').hide();
					toolbar.getButton('itemlist-column-delete').hide();
					toolbar.getButton('itemlist-column-after').hide();
				}
			}
		},
		
		/**
		 * Hide itemlist toolbar
		 */
		hideToolbar: function () {
			var toolbar = this.htmleditor.get('toolbar');
			
			if (toolbar.isGroupVisible(HTMLEDITOR_TOOLBAR)) {
				toolbar.hideGroup(HTMLEDITOR_TOOLBAR);
			}
		},
		
		
		/* --------------------------- Options --------------------------- */
		
		
		/**
		 * Configuration options, how to handle DOM, etc.
		 * @type {Object}
		 * @private
		 */
		_options: null,
		
		/**
		 * Returns options from embeded script tag in page HTML
		 */
		getOptions: function () {
			if (this._options !== null) return this._options;
			
			var container = this.getBlockContainer();
				node = container ? container.one('[type="text/supra-instructions"]') : null,
				options = {
					'orientation': 'horizontal',
					'properties': []
				};
			
			if (node) {
				try {
					options = Supra.mix(options, Y.JSON.parse(node.get('innerHTML')) || {});
				} catch (err) {
					options = false;
				}
			}
			
			if (!options || !options.properties || !options.properties.length) {
				options = false; 
			}
			
			this._options = options;
			
			return options;
		},
		
		/**
		 * Returns property options
		 */
		getPropertyOptions: function (property) {
			var properties = this.getOptions().properties,
				i = 0,
				ii = properties.length;
			
			property = property.replace(/\d+/, '%d');
			
			for (; i<ii; i++) {
				if (properties[i].name === property) return properties[i];
			}
			
			return null;
		},
		
		
		/* --------------------------- ITEMS --------------------------- */
		
		/**
		 * Number of items used
		 * @type {Number}
		 * @private
		 */
		_count: 0,
		
		/**
		 * Total number of items
		 * @type {Number}
		 * @private
		 */
		_total: 0,
		
		/**
		 * List of nodes grouped by property name
		 * @type {Object}
		 * @private
		 */
		_nodes: null,
		
		
		/**
		 * Remove cache
		 */
		purgeCache: function () {
			this._nodes = null;
		},
		
		/**
		 * Collect cache
		 */
		collectCache: function () {
			this.getItemNodes();
		},
		
		/**
		 * Returns list of all nodes grouped by property name
		 * 
		 * @returns {Object}
		 */
		getItemNodes: function () {
			if (this._nodes) return this._nodes;
			
			var node = this.getBlockContainer(),
				properties = this.getOptions().properties,
				i = 0,
				ii = properties.length,
				nodes = {},
				
				tmp = null,
				count = 0,
				total = 0;
			
			if (node) {
				for (; i<ii; i++) {
					tmp = node.all(properties[i]['item-selector']);
					nodes[properties[i]['name']] = tmp;
					
					if (i == 0) {
						total = tmp.size();
						count = total - tmp.filter('.supra-hidden').size();
					}
				}
			}
			
			this._total = total;
			this._count = count;
			
			return nodes; 
		},
		
		/**
		 * Returns block container node
		 * 
		 * @returns {Object} Block container node
		 */
		getBlockContainer: function () {
			var root = this.htmleditor.get('root');
			if (root && root.getNode) return root.getNode();
			return null;
		},
		
		/**
		 * Returns active index
		 * 
		 * @returns {Number} Active item index
		 */
		getActiveIndex: function () {
			var properties = this.getOptions().properties,
				i = 0,
				ii = properties.length,
				classname = '',
				
				nodes = this.getItemNodes(),
				tmp = null,
				k = 0,
				kk = 0;
			
			for (; i<ii; i++) {
				classname = properties[i]['classname-active'];
				if (classname) {
					tmp = nodes[properties[i].name];
					k = 0;
					kk = tmp.size();
					
					for (; k<kk; k++) {
						if (tmp.item(k).hasClass(classname)) return k;
					}
				}
			}
			
			// Fail...
			return 0;
		},
		
		/**
		 * Returns nodes by index
		 */
		getItemNodesByIndex: function (index) {
			var nodes = this.getItemNodes(),
				properties = this.getOptions().properties,
				i = 0,
				ii = properties.length,
				output = [],
				node = null;
			
			for (; i<ii; i++) {
				node = nodes[properties[i].name].item(index);
				if (node) {
					output.push(node);
				}
			}
			
			return output;
		},
		
		/**
		 * Hide item by index
		 * 
		 * @param {Number} index Item index
		 */
		hideItemByIndex: function (index) {
			var nodes = this.getItemNodesByIndex(index),
				i = 0,
				ii = nodes.length;
			
			for (; i<ii; i++) {
				nodes[i].addClass('supra-hidden');
			}
		},
		
		/**
		 * Show item by index
		 * 
		 * @param {Number} index Item index
		 */
		showItemByIndex: function (index) {
			var nodes = this.getItemNodesByIndex(index),
				i = 0,
				ii = nodes.length;
			
			for (; i<ii; i++) {
				nodes[i].removeClass('supra-hidden');
			}
		},
		
		/**
		 * Set active item by index
		 * 
		 * @param {Number} index Item index
		 */
		setActiveItemByIndex: function (index) {
			if (index >= 0 && index < this._count) {
				
				var properties = this.getOptions().properties,
					i = 0,
					ii = properties.length,
					nodes = this.getItemNodes(),
					tmp = null,
					k = 0,
					kk = 0,
					classname = '';
				
				for (; i<ii; i++) {
					classname = properties[i]['classname-active'];
					
					if (classname) {
						tmp = nodes[properties[i].name];
						k = 0;
						kk = tmp.size();
						
						for (; k<kk; k++) {
							if (k === index) {
								tmp.item(k).addClass(classname);
							} else {
								tmp.item(k).removeClass(classname);
							}
						}
					}
				}
				
			}
		},
		
		/**
		 * Copy property values from index a to index b
		 */
		copyItemPropertiesByIndex: function (a, b) {
			var properties = this.getOptions().properties,
				i = 0,
				ii = properties.length,
				name_a = null,
				name_b = null,
				root = this.htmleditor.get('root'), // root is either a block or form
				inputs = root.getInputs ? root.getInputs() : root.properties.get('form').getInputs(),
				input_a = null,
				input_b = null;
			
			for (; i<ii; i++) {
				name_a = properties[i].name.replace('%d', (a + 1)); // +1 because property names starts from 1
				name_b = properties[i].name.replace('%d', (b + 1)); // +1 because property names starts from 1
				
				input_a = inputs[name_a];
				input_b = inputs[name_b];
				
				if (input_a && input_b) {
					input_b.setValue(input_a.getValue());
				}
			}
		},
		
		/**
		 * Reset item property values
		 */
		resetItemPropertiesByIndex: function (index) {
			var properties = this.getOptions().properties,
				i = 0,
				ii = properties.length,
				name = null,
				root = this.htmleditor.get('root'), // root is either a block or form
				inputs = root.getInputs ? root.getInputs() : root.properties.get('form').getInputs(),
				input = null;
			
			for (; i<ii; i++) {
				name = properties[i].name.replace('%d', (index + 1)); // +1 because property names starts from 1
				input = inputs[name];
				
				if (input) {
					input.setValue('');
				}
			}
		},
		
		
		/* --------------------------- COMMANDS --------------------------- */
		
		
		cmdInsertBefore: function () {
			this.collectCache();
			
			// Maximum number of items reached
			if (this._count >= this._total) return;
			
			this.showItemByIndex(this._count);
			this._count++;
			
			var active = this.getActiveIndex(),
				i = this._count,
				ii = active + 1; 
			
			for (; i>ii; i--) {
				this.copyItemPropertiesByIndex(i-2, i-1);
			}
			
			this.resetItemPropertiesByIndex(active);
			this.htmleditor._changed();
		},
		
		cmdInsertAfter: function () {
			this.collectCache();
			
			// Maximum number of items reached
			if (this._count >= this._total) return;
			
			this.showItemByIndex(this._count);
			this._count++;
			
			var active = this.getActiveIndex(),
				i = this._count,
				ii = active + 2; 
			
			for (; i>ii; i--) {
				this.copyItemPropertiesByIndex(i-2, i-1);
			}
			
			this.resetItemPropertiesByIndex(active + 1);
			this.setActiveItemByIndex(active + 1);
			this.htmleditor._changed();
		},
		
		cmdDelete: function () {
			this.collectCache();
			
			// At least one item must remain
			if (this._count <= 1) return;
			
			this.hideItemByIndex(this._count - 1);
			this._count--;
			
			var active = this.getActiveIndex(),
				i = active,
				ii = this._count; 
			
			for (; i<ii; i++) {
				this.copyItemPropertiesByIndex(i+1, i);
			}
			
			if (active >= this._count) { 
				this.setActiveItemByIndex(active - 1);
			}
			
			this.resetItemPropertiesByIndex(this._count);
			this.htmleditor._changed();
		},
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			// Find options
			var options = this.getOptions();
			
			if (options) {
				// Add commands
				htmleditor.addCommand('itemlist-before', Y.bind(this.cmdInsertBefore, this));
				htmleditor.addCommand('itemlist-after', Y.bind(this.cmdInsertAfter, this));
				htmleditor.addCommand('itemlist-delete', Y.bind(this.cmdDelete, this));
			}
			
			htmleditor.on('disabledChange', this.purgeCache, this);
			htmleditor.on('editingAllowedChange', this.purgeCache, this);
			
			//When un-editable node is selected hide toolbar
			htmleditor.on('disabledChange', function (event) {
				if (event.newVal !== event.prevVal) {
					if (event.newVal || !options) {
						this.hideToolbar();
					} else {
						this.showToolbar();
					}
				}
			}, this);
			htmleditor.on('editingAllowedChange', function (event) {
				if (!event.allowed || !options) {
					this.hideToolbar();
				} else {
					this.showToolbar();
				}
			}, this);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			this.purgeCache();
		},
		
		/**
		 * Process HTML
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {HTML}
		 */
		tagHTML: function (html) {
			return html;
		},
		
		/**
		 * Process HTML
		 * Called before HTML is set
		 * 
		 * @param {String} html HTML
		 * @param {Object} data Data
		 * @return Processed HTML
		 * @type {String}
		 */
		untagHTML: function (html, data) {
			return html;
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['supra.htmleditor-base']});