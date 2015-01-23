YUI.add('itemmanager.itemlist', function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	var Manager = Supra.Manager,
		ACTIVE_NONE = '';
	
	
	function ItemList () {
		ItemList.superclass.constructor.apply(this, arguments);
	}
	
	ItemList.NAME = 'itemmanager-itemlist';
	ItemList.NS = 'itemlist';
	
	ItemList.ATTRS = {
		// Supra.Iframe instance
		'iframe': {
			value: null
		},
		
		// Supra.Form instance
		'form': {
			value: null
		},
		
		// Form properties
		'properties': {
			value: null
		},
		
		// Element, from which page structure should be recreated
		'contentElement': {
			value: null
		},
		
		// Show insert button
		'insertControlVisible': {
			value: true,
			setter: '_setInsertControlsVisible'
		},
		
		// Visibility
		'visible': {
			value: true,
			setter: '_setVisible',
			getter: '_getVisible'
		},
		
		/**
		 * Active item id and inline property name
		 * @type {Array}
		 */
		'active': {
			value: [ACTIVE_NONE /* item */, ACTIVE_NONE /* property */]
		}
	};
	
	Y.extend(ItemList, Y.Plugin.Base, {
		
		/**
		 * Elements
		 * @type {Object}
		 * @protected
		 */
		elements: {},
		
		/**
		 * Click on document should be ignored
		 * Used to prevent mousedown -> click events from starting -> stopping
		 * editing
		 * @type {Boolean}
		 * @protected
		 */
		silentDocumentClick: false,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @protected
		 */
		initializer: function(config) {
			this.after('activeChange', this._attrActiveChange, this);
			this.after('insertControlVisibleChange', this._attrInsertControlVisibleChange, this);
			
			if (this.get('form').getInput('collection')) {
				this.setup();
			} else {
				this.get('form').on('input:add', function (e) {
					if (e.config.id === 'collection') {
						this.setup();
					}
				}, this);
			}
			
			// Ordering
			this.plug(Supra.ItemManagerItemListOrder);
			
			// Initialize properties form
			this.showPropertiesForm(null, true);
		},
		
		/**
		 * Automatically called by Base, during destruction
		 * 
		 * @protected
		 */
		destructor: function () {
			this.resetAll();
		},
		
		/**
		 * Find elements, attach listeners
		 * 
		 * @protected
		 */
		setup: function () {
			var contentElement = this.get('contentElement'),
				newItemElement,
				collection,
				doc = this.get('iframe').get('doc');
			
			// When document is clicked outside element, stop editing
			Y.Node(doc).on('click', this.onDocumentClick, this);
			
			// New item
			newItemElement = contentElement.one('[data-new-item]');
			newItemElement.one('span').on('click', this.addItem, this);
			
			// Listen when new item is added
			collection = this.get('form').getInput('collection');
			collection.on('before:item:add', this.beforeItemAdd, this);
			collection.on('item:add', this.afterItemAdd, this);
			collection.on('item:remove', this.afterItemRemove, this);
			
			this.elements = {
				'newItem': newItemElement,
				'items': {}
			};
		},
		
		
		/* --------------------------- Sidebar -------------------------- */
		
		
		/**
		 * Show sidebar properties form
		 *
		 * @param {String} id Item id for which to show properties
		 */
		showPropertiesForm: function (id, init_only) {
			var form = this.get('form'),
				host = this.get('host'),
				options = host.options,
				data = options.data ? [].concat(options.data) : [],
				i = 0,
				ii = data.length,
				collection,
				action = Supra.Manager.getAction('PageContentSettings');
			
			if (!action.get('visible') || init_only) {
				action.execute(form, {
					'doneCallback': Y.bind(this.hidePropertiesForm, this),
					'hideCallback': null,
					'hideEditorToolbar': true,
					'hideDoneButton': false,
					'toolbarActionName': host.NAME + 'Settings',
					
					'properties': null,		//Properties class instance
					'scrollable': false,
					'title': 'Item settings',
					'icon': '/public/cms/supra/img/sidebar/icons/settings.png',
					'init_only': init_only
				});
			}
			
			// Show properties for specific item
			if (!init_only && id) {
				form.getInput('collection').set('visibleItem', id);
			}
			
			// On first initialization call set values
			if (init_only) {
				// Add __suid to the item data
				for (; i < ii; i++) {
					Supra.mix({
						'__suid': Y.guid()
					}, data[i], true);
				}
				
				// Set values
				form.setValuesObject({'collection': options.data}, 'id');
				
				// After item is added focus it
				collection = form.getInput('collection');
				collection.on('item:add', this.afterNewItemAdd, this);
			}
		},
		
		/**
		 * Hide sidebar properties form
		 */
		hidePropertiesForm: function () {
			Supra.Manager.PageContentSettings.hide();
		},
		
		
		/* ---------------------- Active item change --------------------- */
		
		
		/**
		 * Active attribute change
		 *
		 * @param {Object} e Event facade object
		 * @protected
		 */
		_attrActiveChange: function (e) {
			var input,
				showSidebar = false,
				element;
			
			if (e.prevVal[0] !== e.newVal[0] || e.prevVal[1] !== e.newVal[1]) {
				// Stop editing previous property
				if (e.prevVal[1]) {
					input = this.getItemInput(e.prevVal[0], e.prevVal[1]);
					if (input) {
						if (input.stopEditing) {
							input.stopEditing();
						}
						
						input.set('disabled', true);
						input = null;
					}
				}
				
				// Attributes
				if (e.prevVal[0]) {
					element = this.elements.items[e.prevVal[0]];
					if (element) {
						element.removeClass('supra-itemmanager-active');
					}
				}
				if (e.newVal[0]) {
					element = this.elements.items[e.newVal[0]];
					if (element) {
						element.addClass('supra-itemmanager-active');
					}
				}
				
				if (e.newVal[1]) {
					input = this.getItemInput(e.newVal[0], e.newVal[1]);
					if (input) {
						input.set('disabled', false);
						
						// Show sidebar for all inputs, except HTML
						if (!input.isInstanceOf('input-html-inline') || input.isInstanceOf('input-string-inline')) {
							showSidebar = true;
						}
					} else {
						// Input is missing
						showSidebar = true;
					}
				} else if (e.newVal[0]) {
					// Item is set, but not property
					showSidebar = true;
				} else {
					// Item was unset, hide sidebar
					this.hidePropertiesForm();
				}
				
				// "Add item" button visible if there is no item selected
				this.set('insertControlVisible', !e.newVal[0]);
			} else {
				if (e.newVal[1]) {
					input = this.getItemInput(e.newVal[0], e.newVal[1]);
					
					if (input && input.isInstanceOf('block-background')) {
						// Image input may loose focus
						showSidebar = true;
					}
				}
			}
			
			if (showSidebar) {
				// Disable animations, because in UI items are separate
				// objects
				var slideshow = this.get('form').get('slideshow');
				slideshow.set('noAnimations', true);
				
				this.showPropertiesForm(e.newVal[0]);
				
				if (input && input.startEditing) {
					if (!input.startEditing() && !input.get('value')) {
						// Editing for block and image will fail if there is no image
						if (input.isInstanceOf('block-background') || input.isInstanceOf('input-image-inline')) {
							// Open sidebar for user to choose image
							input.openMediaSidebar();
						}
					}
				}
				
				slideshow.set('noAnimations', false);
			}
		},
		
		/**
		 * Set active item and property
		 * 
		 * @param {String|Null} _item Item id
		 * @param {String|Null} _property Property id
		 */
		setActive: function (_item, _property) {
			var active   = this.get('active'),
				item     = _item,
				property = _property;
			
			// Normalize arguments; null === use current value
			if (!property && property !== ACTIVE_NONE) {
				property = active[1];
			}
			if (!item && item !== ACTIVE_NONE) {
				item = active[0];
			}
			if (item === ACTIVE_NONE) {
				property = ACTIVE_NONE;
			}
			
			this.set('active', [item, property]);
		},
		
		/**
		 * Returns active item id
		 *
		 * @returns {String|Null} Active item id or null
		 */
		getActiveItemId: function () {
			var active = this.get('active');
			return active ? active[0] : null;
		},
		
		/**
		 * Returns active property id
		 *
		 * @returns {String|Null} Active property id or null
		 */
		getActivePropertyId: function () {
			var active = this.get('active');
			return active ? active[1] : null;
		},
		
		
		/* ---------------------------- Items --------------------------- */
		
		
		/**
		 * Returns property input for item
		 *
		 * @param {String} item Item id
		 * @param {String} property Property id
		 * @returns {Object} Input
		 */
		getItemInput: function (item, property) {
			var collection = this.get('form').getInput('collection'),
				set = collection.getInputs(),
				i   = 0,
				ii  = set.length;
			
			for (; i<ii; i++) {
				if (set[i].getInput('__suid').get('value') === item) {
					return set[i].getInput(property);
				}
			}
			
			return null;
		},
		
		/**
		 * Add new item
		 */
		addItem: function () {
			var values = {},
				properties = this.get('properties'),
				i = 0,
				ii = properties.length,
				type;
			
			for (; i < ii; i++) {
				type = String(properties[i].type || '');
				type = type.substr(0, 1).toUpperCase() + type.substr(1);
				
				if (Supra.Input[type] && Supra.Input[type].lipsum) {
					values[properties[i].id] = Supra.Input[type].lipsum();
				} else {
					values[properties[i].id] = Supra.Input.getDefaultValue(properties[i].type);
				}
			}
			
			this.get('form').getInput('collection').addItem(values);
		},
		
		/**
		 * Before item is added to the form create iframe DOM element,
		 * otherwise since element doesn't exist input won't be created
		 * 
		 * @param {Object} e Event facade object
		 * @protected
		 */
		beforeItemAdd: function (e) {
			if (e.data) {
				var html = this.get('host').renderer.getItemHTML(e.data, e.index),
					node = Y.Node.create(html);
				
				this.elements.newItem.insert(node, 'before');
				this.elements.items[e.data.__suid] = node;
				
				node.on('click', this.onItemClick, this, e.data.__suid);
			}
		},
		
		/**
		 * After item is added disable inline input editing
		 * 
		 * @param {Object} e Event facade object
		 * @protected
		 */
		afterItemAdd: function (e) {
			var collection = this.get('form').getInput('collection'),
				set = collection.getInput(e.index),
				inputs = set.getInputs(),
				input,
				element,
				key;
			
			for (key in inputs) {
				input = inputs[key];
				if (Supra.Input.isInline(input)) {
					input.set('disabled', true);
					
					element = input.get('targetNode') || input.get('srcNode');
					element.on('click', this.onInlineEditableClick, this, e.data.__suid, key, input);
					element.on('mousedown', this.onInlineEditableMouseDown, this, e.data.__suid, key, input);
				}
			}
			
			this.fire('item:add');
		},
		
		/**
		 * After new item is added start editing it
		 * 
		 * @param {Object} e Event facade object
		 * @protected
		 */
		afterNewItemAdd: function (e) {
			if (e.data) {
				var properties = this.get('properties'),
					type = properties.length ? properties[0].type : null;
				
				if (type && Supra.Input.isInline(type)) {
					this.setActive(e.data.__suid, properties[0].id);
				} else {
					this.setActive(e.data.__suid, null);
				}
			}
		},
		
		/**
		 * After item is removed, remove element from iframe DOM
		 * 
		 * @param {Object} e Event facade object
		 * @protected
		 */
		afterItemRemove: function (e) {
			var items = this.elements.items,
				id    = e.data.__suid,
				active = this.getActiveItemId();
			
			if (id && id in items) {
				items[id].remove(true);
				delete(items[id]);
			}
			
			// Stop editing if item was removed
			if (active === id) {
				this.setActive(ACTIVE_NONE);
			}
			
			this.fire('item:remove');
		},
		
		/**
		 * Handle click on inline editable
		 *
		 * @param {Object} e Event facade object
		 * @param {String} id Item id
		 * @param {String} name Input name
		 * @param {Object} input Input
		 */
		onInlineEditableClick: function (e, id, name, input) {
			if (e.button === 1) {
				this.setActive(id, name);
			}
		},
		
		/**
		 * Handle mouse down on inline editable
		 * Start editing item, but only for text inputs
		 *
		 * @param {Object} e Event facade object
		 * @param {String} id Item id
		 * @param {String} name Input name
		 * @param {Object} input Input
		 */
		onInlineEditableMouseDown: function (e, id, name, input) {
			if (e.button === 1 && (input.isInstanceOf('input-html-inline'))) {
				this.setActive(id, name);
				this.silentDocumentClick = true;
			} else if (e.button === 1 && this.getActiveItemId() === id && this.getActivePropertyId() === name) {
				this.silentDocumentClick = true;
			}
		},
		
		/**
		 * Handle click on item
		 * 
		 * @param {Object} e Event facade object
		 * @param {String} id Item id
		 */
		onItemClick: function (e, id) {
			if (e.button === 1 && this.getActiveItemId() !== id) {
				this.setActive(id, ACTIVE_NONE);
			}
		},
		
		/**
		 * On document click outside any item stop editing
		 *
		 * @param {Object} e Event facade object
		 */
		onDocumentClick: function (e) {
			if (this.silentDocumentClick) {
				// Click was already handled by mousedown event
				this.silentDocumentClick = false;
			} else {
				var target = e.target.closest('[data-item], [data-new-item]');
				
				if (e.button === 1 && !target) {
					this.setActive(ACTIVE_NONE, ACTIVE_NONE);
				}
			}
		},
		
		
		/* -------------------------- Attributes ------------------------- */
		
		
		/**
		 * Insert control visibility attribute setter
		 * 
		 * @param {Boolean} visible Visibility state
		 * @returns {Boolean} New visibility state
		 * @protected
		 */
		_attrInsertControlVisibleChange: function (e) {
			var control = this.elements.newItem;
			if (control && e.newVal !== e.prevVal) {
				control.toggleClass('hidden', !e.newVal);
			}
		},
		
		/**
		 * Visibility attribute setter
		 * 
		 * @param {Boolean} visible Visibility state
		 * @returns {Boolean} New visibility state
		 * @protected
		 */
		_setVisible: function (visible) {
			var iframe = this.get('iframe');
			if (iframe) iframe.set('visible', visible);
			return visible;
		},
		
		/**
		 * Visiblity attribute getter
		 * 
		 * @returns {Boolean} Visible attribute value
		 * @protected
		 */
		_getVisible: function () {
			var iframe = this.get('iframe');
			return iframe ? iframe.get('visible') : false;
		}
		
	});
	
	
	Supra.ItemManagerItemList = ItemList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.iframe', 'plugin', 'itemmanager.itemlist-order']});
