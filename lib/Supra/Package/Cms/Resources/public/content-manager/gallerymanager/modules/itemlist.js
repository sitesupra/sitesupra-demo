YUI.add('gallerymanager.itemlist', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	//Templates
	var TEMPLATE_ADD_NEW_ITEM = '\
			<span class="yui3-inline-reset yui3-box-reset">\
				<span class="supra-gallerymanager-new-empty-label yui3-inline-reset yui3-box-reset">' + Supra.Intl.get(['gallerymanager', 'empty']) + '</span>\
				<span class="supra-gallerymanager-new-add yui3-inline-reset yui3-box-reset">\
					<span></span>\
					' + Supra.Intl.get(['gallerymanager', 'add_item']) + '\
				</span>\
				<span class="supra-gallerymanager-new-drop yui3-inline-reset yui3-box-reset">\
					<span></span>\
					' + Supra.Intl.get(['gallerymanager', 'drag_and_drop']) + '\
				</span>\
				<span class="supra-gallerymanager-new-drop-add yui3-inline-reset yui3-box-reset">\
					' + Supra.Intl.get(['gallerymanager', 'just_drag_and_drop']) + '\
				</span>\
			</span>';
	
	/*
	 * Editable content
	 */
	function ItemList (config) {
		ItemList.superclass.constructor.apply(this, arguments);
	}
	
	ItemList.NAME = 'gallerymanager-itemlist';
	ItemList.NS = 'itemlist';
	
	ItemList.ATTRS = {
		// Supra.Iframe instance
		'iframe': {
			value: null
		},
		// List container node
		'listNode': {
			value: null
		},
		// Show insert button
		'showInsertControl': {
			value: true,
			setter: '_setShowInsertControl'
		},
		// Visibility
		'visible': {
			value: true,
			setter: '_setVisible',
			getter: '_getVisible'
		},
		// List is 'shared'
		'shared': {
			value: false,
			setter: '_setShared'
		}
	};
	
	Y.extend(ItemList, Y.Plugin.Base, {
		
		items: {},
		
		newItemControl: null,
		
		template: null,
		
		editingId: null,
		editingProperty: null,
		editingInput: null,
		
		
		/**
		 * Selector for list node
		 * @type {String}
		 * @private
		 */
		listNodeSelector: "",
		
		/**
		 * Selector for list item nodes
		 * @type {String}
		 * @private
		 */
		listItemSelector: "",
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this.renderIframe();
		},
		
		/**
		 * Automatically called by Base, during destruction
		 */
		destructor: function () {
			this.resetAll();
		},
		
		/**
		 * Reset cache, clean up
		 */
		resetAll: function () {
			var shared = this.get('shared');
			
			// Widgets / plugins
			if (this.order) {
				this.order.set('disabled', shared);
				
				try {
					//FIXME
					this.order.resetAll();
				} catch (err) {}
			}
			
			if (this.highlight) {
				this.highlight.resetAll();
			}
			
			if (this.drop) {
				this.drop.set('disabled', shared);
				this.drop.resetAll();
			}
			
			if (this.uploader) {
				this.uploader.set('disabled', shared);
			}
			
			if (this.newItemControl) {
				this.newItemControl.destroy(true);
				this.newItemControl.remove();
				this.newItemControl = null;
			}
			
			// Items
			for (var id in this.items) {
				this.removeItem(id, true);
			}
			
			// Cache / data
			this.items = {};
			this.template = null;
			this.listNodeSelector = null;
			this.listItemSelector = null;
			
			// Container node
			var node = this.get('listNode');
			if (node) {
				node.destroy(true);
				this.set('listNode', null);
			}
		},
		
		
		/* ---------------------------- ATTRIBUTES --------------------------- */
		
		
		_setShowInsertControl: function (value) {
			var control = this.newItemControl;
			if (control) {
				// We are making assumtion that 'hidden' class is defined
				if (value) {
					control.addClass('hidden');
				} else {
					control.removeClass('hidden');
				}
			}
			
			return !!value;
		},
		
		/**
		 * Shared attribute setter
		 * 
		 * @param {Boolean} shared Shared state
		 * @returns {Boolean} New shared state
		 * @private
		 */
		_setShared: function (shared) {
			// New item button
			this.set('showInsertControl', !shared);
			return !!shared;
		},
		
		/**
		 * Visibility attribute setter
		 * 
		 * @param {Boolean} visible Visibility state
		 * @returns {Boolean} New visibility state
		 * @private
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
		 * @private
		 */
		_getVisible: function () {
			var iframe = this.get('iframe');
			return iframe ? iframe.get('visible') : false;
		},
		
		
		/* ---------------------------- ITEM DATA --------------------------- */
		
		
		/**
		 * Returns item data by id
		 * 
		 * @param {String} id Item id
		 * @returns {Object} Item data or null if item is not found
		 */
		getDataById: function (id) {
			var propertyName = this.get('host').gallery_property_id,
				images = this.get('host').data[propertyName],
				i = 0,
				ii = images.length;
			
			// Render all items
			for (; i<ii; i++) {
				if (images[i].id == id) return images[i];
			}
			
			return null;
		},
		
		/**
		 * Returns item data by node
		 * 
		 * @param {Object} node Item node
		 * @returns {Object} Item data or null if item is not found
		 */
		getDataByNode: function (node) {
			var id = node.getData('item-id');
			return id ? this.getDataById(id) : null;
		},
		
		/**
		 * Returns item node by id
		 * 
		 * @param {String} id Image ID
		 * @returns {Object} Item node
		 */
		getNodeById: function (id) {
			var item = this.items[id];
			return item ? item.node : null;
		},
		
		/**
		 * Update data for input which is being edited
		 */
		updateData: function () {
			if (this.editingId && !this.get('host').ui_updating) {
				// Save value
				var propertyName = this.get('host').gallery_property_id,
					
					value = null,
					images = this.get('host').data[propertyName],
					i = 0,
					ii = images.length,
					
					id = this.editingId,
					property = this.editingProperty,
					
					form = this.get('host').settings_form;
				
				if (this.editingInput) {
					// For item without image there will be no image input
					value = this.editingInput.get('saveValue');
				}
				
				this.get('host').ui_updating = true;
				
				for (; i<ii; i++) {
					if (images[i].id == id) {
						if (property === 'image') {
							images[i].image = value;
						} else {
							images[i].properties[property] = value;
							
							//Update settings form
							if (form && form.get('visible')) {
								form.getInput(property).set('value', value);
							}
						}
					}
				}
				
				this.get('host').ui_updating = false;
			}
		},
		
		/**
		 * Update image data without validating UI state 
		 */
		updateImageDataAuto: function (event, id) {
			var propertyName = this.get('host').gallery_property_id,
				
				value = event.target.get('saveValue'),
				images = this.get('host').data[propertyName],
				i = 0,
				ii = images.length;
			
			this.get('host').ui_updating = true;
			
			for (; i<ii; i++) {
				if (images[i].id == id) {
					images[i].image = value;
					break;
				}
			}
			
			this.get('host').ui_updating = false;
		},
		
		/**
		 * Convert old format data without size and crop into new format
		 * with size and crop
		 * 
		 * @param {Object} data Old format data
		 * @returns {Object} New format data
		 * @private
		 */
		normalizeItemData: function (data) {
			if (this.get('host').data.design == 'icon') {
				// Icon
				if (!data.image) {
					data.image = new Y.DataType.Icon();
				}
			} else {
				// Image
				if (data.image && data.image.crop_width) {
					// Image has crop and size properties
					if (!data.image.image) {
						data.broken = true;
					}
				} else if (!data.image || data.image.id) {
					// No crop or size properties, add default ones
					var image = data.image;
					data.image = {
						'image': image && image.sizes ? image : null,
						'size_width': 0,
						'size_height': 0,
						'crop_left': 0,
						'crop_top': 0,
						'crop_width': 0,
						'crop_height': 0
					};
					
					if (!image) {
						data.broken = true;
					}
				}
			}
			
			return data;
		},
		
		
		/* ---------------------------- ITEM RENDERING --------------------------- */
		
		
		/**
		 * Render all items from data
		 */
		renderItems: function () {
			var propertyName = this.get('host').gallery_property_id,
				
				container = null,
				items = this.get('host').data[propertyName],
				i = 0,
				ii = items.length;
			
			// Find container node
			container = this.get('iframe').one(this.listNodeSelector);
			container.addClass('su-gallerymanager-editing');
			container.addClass('supra-gallerymanager-empty');
			
			this.set('listNode', container);
			
			// Remove old elements
			container.get('children').each(function (item) {
				if (!item.hasClass('su-gallerymanager-new')) {
					item.destroy(true);
					item.remove();
				}
			});
			
			// Render all items
			for (; i<ii; i++) {
				this.renderItem(this.normalizeItemData(items[i]));
			}
			
			// Render "Add new item" item
			this.renderNewItemControl();
		},
		
		/**
		 * Add item
		 * 
		 * @param {Object} image_data Image data
		 */
		addItem: function (image_data) {
			var propertyName = this.get('host').gallery_property_id,
				
				images = this.get('host').data[propertyName],
				properties = this.get('host').image_properties,
				property = null,
				image_id = Supra.Y.guid(),
				image = this.normalizeItemData({
					'image': image_data,
					'id': image_id,
					'properties': {}
				});
			
			// Check if image doesn't exist in data already
			if (image_data) {
				for (var i=0, ii=images.length; i<ii; i++) {
					if (images[i].id == image_data.id) {
						// Such image is already in the list, show error
						Supra.Manager.executeAction('Confirmation', {
							'message': Supra.Intl.get(['gallerymanager', 'error_duplicate_image']),
							'buttons': [
								{'id': 'error', 'label': 'Ok'},
							]
						});
						return false;
					}
				}
			} else {
				image_data = {};
			}
			
			// Set properties
			for (var i=0, ii=properties.length; i<ii; i++) {
				property = properties[i].id;
				
				if (property === 'title') {
					image.properties[property] = image_data[property] || image_data.filename || properties[i].value || properties[i].label || '';
				} else if (properties[i].type === 'String' || properties[i].type === 'Text') {
					image.properties[property] = image_data[property] || properties[i].value || properties[i].label || '';
				} else {
					image.properties[property] = image_data[property] || properties[i].value || '';
				}
			}
			
			images.push(image);
			this.renderItem(image);
			
			return image;
		},
		
		/**
		 * Render single item
		 * 
		 * @param {Object} data Item data
		 */
		renderItem: function (data) {
			// Validate
			if (data.id in this.items) return false;
			
			// Render item
			var model    = this.getItemRenderData(data),
				template = this.template,
				key      = null,
				node     = null,
				item     = null;
			
			node = Y.Node.create(template(model));
			
			// Re-append new item node, so it's always last item in the list
			if (this.newItemControl) {
				this.newItemControl.insert(node, 'before');
			} else {
				this.get('listNode').append(node);
			}
			
			// Data
			item = {
				'properties': {},
				'node': node,
				'id': data.id
			};
			
			// Find editables
			item.properties = this.processItemHTMLProperties(item.id, node, data);
			
			node.setData('item-id', item.id);
			
			this.items[item.id] = item;
			
			this.get('listNode').removeClass('supra-gallerymanager-empty');
			this.fire('addItem', {'node': node, 'id': data.id});
		},
		
		/**
		 * Render item which will be a button to add new item
		 * 
		 * @private
		 */
		renderNewItemControl: function () {
			if (this.get('showInsertControl')) {
				
				//Find template
				var block = this.getBlock(),
					templateNode = this.getItemTemplateNode(),
					template = null,
					node = null,
					html = '',
					
					// Regular expression to find wrapper element
					regex_tag = /<([a-z]+)(\s[^>]*)?>/i;
				
				// Create wrapper element
				if (templateNode) {
					html = templateNode.get('innerHTML').match(regex_tag);
					html = html[0] + TEMPLATE_ADD_NEW_ITEM + '</' + html[1] + '>';
				} else {
					html = '<li>' + TEMPLATE_ADD_NEW_ITEM + '</li>';
				}
				
				// Create node
				template = Supra.Template.compile(html);
				node = this.newItemControl = Y.Node.create(template(this.getItemRenderData()));
				node.addClass('supra-gallerymanager-new');
				
				// Add to list and attach listeners
				this.get('listNode').append(node);
				
				node.one('span').on('click', function () {
					this.fire('addNewItem');
					var image = this.addItem();
					
					// Start editing image
					this.focusInlineEditor(image.id, 'image');
				}, this);
			}
		},
		
		/**
		 * Find inline inputs and create editors
		 * 
		 * @param {String} itemId Item ID
		 * @param {Object} itemNode Y.Node
		 * @param {Object} data Image data
		 * @returns Object with nodes and inputs
		 */
		processItemHTMLProperties: function (itemId, itemNode, data) {
			var nodes = itemNode.all('span[data-supra-item-property]'),
				node  = null,
				i     = 0,
				ii    = nodes.size(),
				props = {},
				
				iframe = this.get('iframe'),
				iframeNode = iframe.get('contentBox'),
				doc   = iframe.get('doc'),
				win   = iframe.get('win'),
				
				input = null;
			
			// Image
			props.image = this.processItemImageProperty(itemId, itemNode, data);
			
			// Input properties like title, description
			for (; i<ii; i++) {
				node = nodes.item(i);
				
				var container = node.ancestor(),
					containerDOMNode = container.getDOMNode(),
					nodeDOMNode = node.getDOMNode(),
					property = node.getAttribute('data-supra-item-property');
				
				// Remove temporary node
				while (nodeDOMNode.firstChild) {
					containerDOMNode.appendChild(nodeDOMNode.firstChild);
				}
				
				node.remove();
				
				// Create input
				input = new Supra.Input.InlineString({
					'iframeNode': iframeNode,
					'doc': doc,
					'win': win,
					'srcNode': container,
					'toolbar': Supra.Manager.EditorToolbar.getToolbar(),
					'editImageAutomatically': false
				});
				
				input.render();
				input.on('change', this.updateData, this);
				
				// Save property
				props[property] = {
					'node': container,
					'input': input
				};
				
				container.setData('item-property', property);
				container.setData('item-id', itemId);
				container.on('mousedown', this.focusInlineEditor, this);
			}
			
			return props;
		},
		
		/**
		 * Find image and create editor
		 * 
		 * @param {String} itemId Item ID
		 * @param {Object} itemNode Y.Node
		 * @param {Object} data Image data
		 * @return {Object} Object with node and input
		 * @private
		 */
		processItemImageProperty: function (itemId, itemNode, data) {
			var imageNode = itemNode.one('img, svg'),
				input = null,
				
				size = null,
				ratio = 0,
				width = 0,
				height = 0,
				node_width = 0,
				node_height = 0,
				crop_width = 0,
				crop_height = 0,
				
				value = data.image,
				
				mode = 0;
			
			if (this.get('host').data.design == 'icon') {
				// New items don't have image
				if (value && value.svg) {
					
					// Size and crop properties not set yet
					if (!value.width) {
						node_width = parseInt(imageNode.get('offsetWidth') || imageNode.getAttribute('width'), 10) || imageNode.ancestor().get('offsetWidth') || 32;
						node_height = parseInt(imageNode.get('offsetHeight') || imageNode.getAttribute('height'), 10) || imageNode.ancestor().get('offsetHeight') || 32;
						
						value.width = width;
						value.height = height;
					}
					
					input = new Supra.GalleryManagerImageEditor({
						'srcNode': imageNode,
						'value': value,
						'disabled': this.get('shared'),
						'mode': Supra.GalleryManagerImageEditor.MODE_ICON
					});
					
					imageNode.setStyle('visibility', 'visible');
					
					// When image is resized save data
					input.on('change', this.updateData, this);
					
					// When image is resized automatically (not user input), then save data
					// without validating UI state (eg. if user is editing)
					input.on('resize', this.updateImageDataAuto, this, itemId);
					
					input.render();
				} else {
					imageNode.setStyle('visibility', 'hidden');
				}
				
			} else {
				
				// New items don't have image
				if (value.image) {
					
					// Size and crop properties not set yet
					if (!value.size_width) {
						size = value.image.sizes.original;
						
						node_width = parseInt(imageNode.getAttribute('width') || imageNode.get('offsetWidth'), 10) || imageNode.ancestor().get('offsetWidth');
						node_height = parseInt(imageNode.getAttribute('height') || imageNode.get('offsetHeight'), 10) || imageNode.ancestor().get('offsetHeight');
						
						if (node_height < 18) {
							// Additional padding due to inline-block style will cause several pixels "margin"
							// which is not actual height set
							node_height = 0;
						}
						
						ratio = size.width / size.height;
						width = Math.min(size.width, node_width || 99999);
						height = ~~(width / ratio);
						crop_width = width;
						crop_height = Math.min(size.height, height, node_height || 99999);
						
						value.size_width = width;
						value.size_height = height;
						value.crop_width = crop_width;
						value.crop_height = crop_height;
					}
					
					input = new Supra.GalleryManagerImageEditor({
						'srcNode': imageNode,
						'value': value,
						'disabled': this.get('shared'),
						'mode': Supra.GalleryManagerImageEditor.MODE_IMAGE
					});
					
					// When image is resized save data
					input.on('change', this.updateData, this);
					
					// When image is resized automatically (not user input), then save data
					// without validating UI state (eg. if user is editing)
					input.on('resize', this.updateImageDataAuto, this, itemId);
					
					input.render();
				} else {
					imageNode.setAttribute('src', '/public/cms/supra/img/px.gif');
					imageNode.setStyles({
						'background': '#e5e5e5 url(/public/cms/supra/img/medialibrary/icon-broken-plain.png) 50% 50% no-repeat',
						'min-width': '100%'
					});
				}
				
			}
			
			imageNode.setData('item-property', 'image');
			imageNode.setData('item-id', itemId);
			imageNode.on('click', this.focusInlineEditor, this);
			
			return {
				'node': imageNode,
				'input': input
			};
		},
		
		replaceItem: function (id, image) {
			//Don't replace if user choose same image
			if (id == image.id) return false;
			
			//Image image with which user is trying to replace already
			//exists in the list, then skip
			image = this.addItem(image);
			if (!image) return false;
			
			var old_data = this.getDataById(id),
				new_data = this.getDataById(image.id),
				old_node = this.getNodeById(id),
				new_node = this.getNodeById(image.id),
				properties = this.get('host').image_properties,
				property = null,
				item = this.items[image.id],
				input = null;
			
			for (var i=0, ii=properties.length; i<ii; i++) {
				property = properties[i].id;
				
				// Replace title, description, etc. with old value if old value was not default
				if (old_data.properties[property] && old_data.properties[property] != properties[i].value) {
					new_data.properties[property] = old_data.properties[property];
					
					// Update input value
					input = Supra.getObjectValue(item, ['properties', property, 'input']);
					if (input) {
						input.set('value', new_data.properties[property]);
					}
				}
			}
			
			old_node.insert(new_node, 'before');
			
			// Change focus
			if (this.editingId == id) {
				this.focusInlineEditor(image.id, this.editingProperty);
			}
			
			this.removeItem(id);
			
			return true;
		},
		
		/**
		 * Remove single item
		 * 
		 * @param {String} id Item id
		 * @param {Boolean} listOnly Remove only from list, but not from data
		 * @returns {Boolean} True if item was removed, otherwise false
		 */
		removeItem: function (id, listOnly) {
			if (id in this.items) {
				// Hide settings form
				if (this.editingId == id) {
					this.get('host').settingsFormCancel();
				}
				
				var propertyName = this.get('host').gallery_property_id,
					
					item = this.items[id],
					properties = item.properties,
					key = null,
					
					images = this.get('host').data[propertyName],
					i = 0,
					ii = images.length;
				
				// Event
				this.fire('removeItem', {'node': item.node, 'id': id});
				
				// Destroy inputs
				for (key in properties) {
					if (properties[key].input) {
						try {
							properties[key].input.destroy();
						} catch (err) {
							// IE error
						}
					}
				}
				
				// Remove node
				item.node.remove(true);
				
				// Remove data
				delete(this.items[id]);
				
				if (listOnly !== true) {
					for (; i<ii; i++) {
						if (images[i].id == id) {
							images.splice(i, 1);
							break;
						}
					}
				}
				
				// Style list
				if (!images.length) {
					this.get('listNode').addClass('supra-gallerymanager-empty');
				}
				
				return true;
			} else {
				return false;
			}
		},
		
		
		/* ---------------------------- INLINE EDITING --------------------------- */
		
		
		/**
		 * Focus input
		 * 
		 * @param {String|Event} id Input ID or event which target is input
		 * @param {String} property Property name
		 */
		focusInlineEditor: function (id, property) {
			var container = null,
				input = null,
				shared = this.get('shared');
			
			if (typeof id === 'object') {
				// Event
				container = id.target;
				property = container.getData('item-property');
				id = container.getData('item-id');
			}
			
			if (!id || !property) {
				return;
			}
			
			// Blur non-inline editor
			var active = Y.Node(document.activeElement);
			if (active.test('input, textarea')) {
				active.blur();
			}
			
			if (this.editingId == id && this.editingProperty == property) {
				if (property === 'image') {
					// Image input may loose focus and is closed, while 
					// sidebar stays open and property is still considered to be focused
					// (not a bug, otherwise behaviour would be wierd)
					if (!shared && this.editingInput && !this.editingInput.get('editing')) {
						this.editingInput.edit();
					}
				} else {
					// Nothing has changed
					// Image can be re-focused
					return;
				}
			}
			
			// Is there an input for this property?
			input = this.items[id].properties[property].input;
			
			this.blurInlineEditor();
			
			if (input) {
				this.editingId = id;
				this.editingProperty = property;
				this.editingInput = input;
				
				if (property !== 'image' || !shared) {
					input.set('disabled', false);
					input.focus();
				}
				
				this.get('host').showImageSettings(id);
				
			} else if (property === 'image') {
				// If image input doesn't exist, that mean this item doesn't have image
				// selected yet, so open media library
				this.editingId = id;
				this.editingProperty = property;
				this.editingInput = null;
				
				if (!shared) {
					this.get('host').openMediaLibraryForReplace(id);
				} else {
					// Can't edit image, just show settings
					this.get('host').showImageSettings(id);
				}
			}
			
			this.fire('focusItem', {'input': this.editingInput, 'id': id, 'property': property});
			
			// Highlight focused item
			var node = this.items[id].node;
			if (node) {
				node.addClass('su-gallerymanager-focused');
			}
			
			// Hide new item control
			var control = this.newItemControl;
			if (control) {
				control.addClass('hidden');
			}
		},
		
		/**
		 * Blur currently active editor
		 */
		blurInlineEditor: function () {
			if (this.editingId) {
				// Update data
				this.updateData();
				
				// Reset input state
				if (this.editingInput) {
					this.editingInput.set('disabled', true);
				}
				
				this.fire('blurItem', {'input': this.editingInput, 'id': this.editingId, 'property': this.editingProperty});
				
				// Remove highlight from item
				var node = this.items[this.editingId].node;
				if (node) {
					node.removeClass('su-gallerymanager-focused');
				}
				
				// Unset data
				this.editingId = null;
				this.editingProperty = null;
				this.editingInput = null;
				
				// Hide new item control
				var control = this.newItemControl;
				if (control && this.get('showInsertControl')) {
					control.removeClass('hidden');
				}
			}
		},
		
		/**
		 * Update item UI
		 * 
		 * @param {String} id Item id
		 */
		updateUI: function (id) {
			var item = this.items[id],
				properties = null,
				key = null,
				data = this.getDataById(id);
			
			if (item) {
				this.get('host').ui_updating = true;
				
				properties = item.properties;
				for (key in properties) {
					if (key !== 'image') {
						properties[key].input.set('value', data.properties[key]);
					}
				}
				
				this.get('host').ui_updating = false;
				this.fire('updateItem', {'node': item.node, 'id': id});
			}
		},
		
		
		/* ---------------------------- IFRAME --------------------------- */
		
		
		/**
		 * Create iframe
		 * 
		 * @private
		 */
		renderIframe: function () {
			var host   = this.get('host'),
				iframe = new Supra.Iframe({
					'visible': this.get('visible')
				});
			
 			iframe.render(host.one('.yui3-gallery-manager-content'));
 			iframe.addClass('fullscreen');
 			this.set('iframe', iframe);
		},
		
		/**
		 * Reload iframe content
		 * 
		 * @private
		 */
		reloadIframe: function () {
			this.resetAll();
			
			var iframe = this.get('iframe'),
				html   = this.getHTML();
			
			iframe.set('loading', true);
			
			if (html) {
				iframe.once('ready', this.renderItems, this);
	 			iframe.set('html', html);
	 			// Note: renderItems is event listener, so it will be called after HTML is set, not before!
		 	}
		},
		
		
		/* ---------------------------- HTML CONTENT RENDERING --------------------------- */
		
		
		/**
		 * Returns item template node
		 * 
		 * @private
		 */
		getItemTemplateNode: function () {
			var node = this.getBlock().getNode(),
				template = null;
			
			template = node.one('[data-supra-id="gallerymanager-item"]');
			if (template) return template;
			
			template = node.one('script[type="text/supra-template"], script[type="text/template"]');
			return template;
		},
		
		/**
		 * Returns HTML needed to recreate block
		 * 
		 * @returns {String} HTML
		 * @private
		 */
		getHTML: function () {
			var block = this.getBlock(),
				node = null,
				nodeTag = '',
				nodeClass = '',
				nodeId = '',
				structure = [],
				selector = '',
				listSelector = '',
				listItemSelector = '';
			
			// Find template
			var templateNode = this.getItemTemplateNode();
			if (templateNode) {
				// Template node should have container node selector as attribute
				listSelector = templateNode.getAttribute('data-supra-container-selector') || 'ul, ol';
				
				// Template node can have child selector as attribute
				listItemSelector = templateNode.getAttribute('data-supra-item-selector') || '';
				
				// If there is no selector or no node then use block main node
				node = (listSelector ? block.getNode().one(listSelector) : null) || block.getNode();
				
				try {
					this.template = Supra.Template.compile(templateNode.get('innerHTML'));
				} catch (err) {
					// Error, template can't be compiled
					Y.log('Block "' + block.getBlockTitle() + '" item template is invalid: "' + err.message + '"', 'error', 'gallerymanager.itemlist');
				}
			} else {
				// Error, template is missing
				Y.log('Block "' + block.getBlockTitle() + '" item template which would be used by GalleryManager is missing. Block should have a <script type="text/supra-template"> or <script type="text/template"> template.', 'error', 'gallerymanager.itemlist');
			}
			
			if (!this.template) {
				// Close manager, since it's not possible to create new items
				this.get('host').cancelChanges();
				
				// Show message to user before closing GalleryManager
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['gallerymanager', 'error_template']).replace('{block}', block.getBlockTitle()),
					'buttons': [{'id': 'error'}]
				});
				
				return false;
			}
			
			// Recreate DOM structure
			if (node) {
				node = node.getDOMNode();
				
				while (node && node.tagName) {
					nodeTag = node.tagName.toLowerCase();
					nodeClass = (node.getAttribute('class') || '').replace(/(^|\s)(yui3\-[a-zA-Z0-9\-_]+|su\-[a-zA-Z0-9\-_]+|editing)/g, ' ');
					
					nodeId = node.getAttribute('id') || '';
					if (nodeId && nodeId.indexOf('yui_') !== -1) {
						nodeId = '';
					}
					
					if (nodeTag == 'body') {
						// Add wrapper for content inside body, which will be 100% height of content
						// it's needed for HTML5 drag and drop events to work correctly
						structure.unshift('<div class="yui3-inline-reset yui3-box-reset su-gallerymanager-wrapper">');
						structure.push('</div>');
					}
					
					structure.unshift('<' + nodeTag + '' + (nodeTag === 'html' ? ' lang=' + (node.getAttribute('lang') || '') : '') + ' class="' + nodeClass + '" id="' + nodeId + '">');
					structure.push('</' + nodeTag + '>');
					
					if (nodeTag !== 'html' && nodeTag !== 'body') {
						nodeClass = Y.Lang.trim(nodeClass).replace(/\s+/g, '.');
						selector = (nodeTag + (nodeId ? '#' + nodeId : '') + (nodeClass ? '.' + nodeClass : '')) + (selector ? ' ' + selector : '');
					}
					
					node = node.parentNode;
				}
			} else {
				structure = [
					'<html class="supra-cms">',
						'<body><div class="yui3-inline-reset yui3-box-reset su-gallerymanager-wrapper">',
							'<ul>',
							'</ul>',
						'</div></body>',
					'</html>'
				];
				selector = 'ul';
			}
			
			structure[0] += '<head>' + this.getHTMLLinks() + '</head>';
			
			// Save selector to find node when iframe is ready
			this.listNodeSelector = selector
			
			if (listItemSelector) {
				this.listItemSelector = listItemSelector;
			}
			
			return structure.join('');
		},
		
		/**
		 * Returns HTML for stylesheet link tags
		 * 
		 * @returns {String} HTML for stylesheet link tags
		 * @private
		 */
		getHTMLLinks: function () {
			// Recreate styles
			var doc = this.getOriginalDocument(),
				
				links = Y.Node(doc).all('link[rel="stylesheet"]'),
				i = 0,
				ii = links.size(),
				
				linkHref = '',
				linkMedia = '',
				
				linkHrefExtra  = '',
				
				styles = Y.Node(doc).all('style[type="text/css"]'),
				s = 0,
				ss = styles.size(),
				
				styleMedia = '',
				
				stylesheets = [];
			
			for (; i<ii; i++) {
				linkHref = links.item(i).getAttribute('href') || '';
				linkMedia = links.item(i).getAttribute('media') || '';
				
				stylesheets.push('<link rel="stylesheet" type="text/css" href="' + linkHref + '" media="' + linkMedia + '" />');
			}
			
			for (; s<ss; s++) {
				styleMedia = styles.item(s).getAttribute('media') || 'all';
				stylesheets.push('<style type="text/css" media="' + linkMedia + '">' + styles.item(s).get('innerHTML') + '</style>');
			}
			
			// Gallery manager stylesheet for new item, drag and drop, etc. styles
			linkHrefExtra = Manager.Loader.getActionInfo('GalleryManager').folder + 'modules/itemlist.css';
			stylesheets.push('<link rel="stylesheet" type="text/css" href="' + linkHrefExtra + '" />');
			
			return stylesheets.join('');
		},
		
		/**
		 * Returns data for template rendering
		 * 
		 * @param {Object} data Item data, optional
		 * @returns {Object} Data for rendering item
		 * @private
		 */
		getItemRenderData: function (data) {
			var model = {
					'supra': {
						'cmsRequest': true
					},
					'supraBlock': {
						'property': Y.bind(function (name) {
							var data = this.get('host').data;
							return name in data ? data[name] : '';
						}, this)
					}
				},
				key;
			
			model = Supra.mix(model, this.get('host').data);
			
			if (data) {
				model.id = data.id;
				model.property = Supra.mix({}, data.properties);
				
				for (key in model.property) {
					model.property[key] = '<span class="yui3-inline-reset" data-supra-item-property="' + key + '">' + (model.property[key] || '') + '</span>';
				}
			}
			
			return model;
		},
		
		/**
		 * Returns original iframe document
		 * 
		 * @returns {Object} Original iframe document in which is block which is being edited
		 */
		getOriginalDocument: function () {
			return Action.getIframeHandler().get('doc');
		},
		
		/**
		 * Returns iframe document
		 * 
		 * @returns {Object} Iframe document in which item list is located 
		 */
		getDocument: function () {
			return this.get('iframe').get('doc');
		},
		
		/**
		 * Returns all content wrapper node
		 * 
		 * @returns {Object} Content wrapper node
		 */
		getWrapperNode: function () {
			var doc = this.getDocument();
			return doc ? Y.Node(doc.body).one('.su-gallerymanager-wrapper') : null;
		},
		
		/**
		 * Returns single item CSS selector
		 * 
		 * @return {String} CSS selector for item
		 */
		getChildSelector: function () {
			if (this.listItemSelector) return this.listItemSelector;
			
			var container = this.get('listNode'),
				childSelector = 'div',
				children = null;
			
			if (container) {
				// Get children tag name
				if (container.test('ol, ul')) {
					childSelector = 'li';
				} else {
					children = container.get('children');
					if (children.size()) {
						childSelector = children.item(0).get('tagName');
					}
				}
			}
			
			this.listItemSelector = childSelector;
			return childSelector;
		},
		
		/**
		 * Returns list CSS selector
		 * 
		 * @return {String} CSS selector for list
		 */
		getListSelector: function () {
			if (this.listNodeSelector) return this.listNodeSelector;
			
			// Return ul/ol as default
			return 'ul, ol';
		},
		
		/**
		 * Returns currently edited block
		 * 
		 * @return {Object} Block which is currently being edited
		 * @private
		 */
		getBlock: function () {
			return Action.getContent().get('activeChild');
		}
		
	});
	
	Supra.GalleryManagerItemList = ItemList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.iframe', 'supra.template', 'plugin']});