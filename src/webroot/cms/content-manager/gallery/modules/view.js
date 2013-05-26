YUI.add('gallery.view', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	//Templates
	var TEMPLATE_ADD_NEW_ITEM = '\
				<span class="supra-gallery-new-empty-label yui3-inline-reset yui3-box-reset">\
					' + Supra.Intl.get(['gallery', 'empty']) + '\
				</span>\
				<span class="supra-gallery-new-add yui3-inline-reset yui3-box-reset">\
					' + Supra.Intl.get(['gallery', 'add_item']) + '\
				</span>\
				<span class="supra-gallery-new-layouts">{{ layouts }}</span>';
	
	var TEMPLATE_ADD_NEW_ITEM_LAYOUT = '\
				<span class="supra-gallery-new-layout supra-gallery-new-layout-simple" data-id="{{ id }}">\
					<span></span>\
				</span>';
	
	/**
	 * Settings form
	 */
	function View (config) {
		View.superclass.constructor.apply(this, arguments);
	}
	
	View.NAME = 'gallery-view';
	View.NS = 'view';
	
	View.ATTRS = {
		// Visibility
		'visible': {
			'value': false,
			'setter': '_setVisible',
			'getter': '_getVisible'
		},
		// Active slide id
		'activeItemId': {
			'value': null,
			'setter': '_setActiveItemId'
		},
		// Iframe
		'iframe': {
			'value': null
		},
		// List node
		'listNode': {
			'value': null
		},
		// Show insert button
		'showInsertControl': {
			value: true,
			setter: '_setShowInsertControl'
		},
		// List is 'shared'
		'shared': {
			value: false,
			setter: '_setShared'
		}
	};
	
	Y.extend(View, Y.Plugin.Base, {
		
		/**
		 * List of widgets
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this.widgets = {
				'inputs': {},
				'events': [],
				'nodes': {}
			};
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
			// Active property
			this.stopEditing();
			
			// Clean up inputs
			this._cleanUpInputs();
			
			// Remove highlight
			if (this.highlight) {
				this.highlight.resetAll();
			}
			
			// New Item control
			var node = this.newItemControl;
			if (node) {
				node.destroy(true);
			}
			
			// Container node
			var node = this.get('listNode');
			if (node) {
				node.destroy(true);
				this.set('listNode', null);
			}
			
			this.newItemControl = null;
			this._activeInput = null;
			this._activePropertyId = null;
		},
		
		/* ---------------------------- ATTRIBUTES --------------------------- */
		
		
		/**
		 * Show settings form
		 */
		show: function () {
			if (!this.get('visible')) {
				this.set('visible', true);
			}
		},
		
		/**
		 * Hide settings form
		 */
		hide: function () {
			if (this.get('visible')) {
				this.set('visible', false);
			}
		},
		
		/**
		 * showInsertControl attribute setter
		 * 
		 * @param {Boolean} value Attribute value
		 * @returns {Boolean} New attribute value
		 * @private
		 */
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
		 * Visible attribute setter
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
		 * Visible attribute getter
		 * 
		 * @returns {Boolean} Visible attribute value
		 * @private
		 */
		_getVisible: function () {
			var iframe = this.get('iframe');
			return iframe ? iframe.get('visible') : false;
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
			
 			iframe.render(host.one('.su-gallery-content'));
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
				html   = this.getHtml();
			
			iframe.set('loading', true);
			
			if (html) {
				iframe.once('ready', this.renderItems, this);
	 			iframe.set('html', html);
	 			// Note: renderItems is event listener, so it will be called after HTML is set, not before!
		 	}
		},
		
		
		/* ---------------------------- DATA --------------------------- */
		
		
		/**
		 * activeItemId attribute setter
		 * 
		 * @param {String} id Active item ID
		 * @returns {String} New attribute value
		 * @private 
		 */
		_setActiveItemId: function (id) {
			var iframe = this.get('iframe'),
				old_id = null,
				old_data = null,
				new_data = null,
				node = null;
			
			if (iframe && !iframe.get('loading')) {
				old_id = this.get('activeItemId');
				old_data = this.get('host').data.getSlideById(old_id);
				new_data = this.get('host').data.getSlideById(id);
				
				this._enableInputs(id);
				
				if (old_data && old_data.id in this.widgets.nodes) {
					node = this.widgets.nodes[old_data.id];
					if (node) {
						node.removeClass('supra-gallery-item-focused');
					}
				}
				if (id && id in this.widgets.nodes) {
					node = this.widgets.nodes[id];
					if (node) {
						node.addClass('supra-gallery-item-focused');
					}
				}
				
				node = Y.Node(this.getDocument().body);
				node.toggleClass('supra-gallery-has-item-focused', !!id);
				node.toggleClass('supra-gallery-has-item-blurred', !id);
				
				return id;
			}
		},
		
		
		/* ---------------------------- CONTENT EDITING --------------------------- */
		
		
		/**
		 * Active property input
		 * @private
		 */
		_activeInput: null,
		
		/**
		 * Active property id
		 * @private
		 */
		_activePropertyId: null,
		
		
		/**
		 * On input focus change Gallery state to item editing
		 * This is for inline image, background inputs
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} property Property info
		 * @param {Object} input Property input
		 * @private
		 */
		_onInputFocus: function (event, property, input) {
			var active = this.get('activeItemId'),
				node   = input.get('targetNode'),
				id     = node ? this.getIdFromNode(node) : null;
			
			if (!id) {
				// ID not found, invalid input state
				return;
			}
			
			if (input !== this._activeInput || active !== id) {
				this._startEditing(event, property, input, id);
			}
		},
		
		/**
		 * On input blur stop editing it
		 * This is for inline image, background inputs
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} property Property info
		 * @param {Object} input Property input
		 * @private
		 */
		_onInputBlur: function (event, property, input) {
			if (input === this._activeInput) {
				this.stopEditing();
			}
		},
		
		/**
		 * Active input: start editing content, close sidebar
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} property Property info
		 * @param {Object} input Property input
		 * @param {String} id Slide id
		 * @private
		 */
		_startEditing: function (event, property, input, id) {
			var old_input = this._activeInput,
				active    = null;
			
			if (old_input === input && this.get('host').get('activeSlideId') == id) {
				// Already editing
				return;
			}
			
			if (Manager.getAction('MediaSidebar').get('visible')) {
				// Can't edit anything while media library is shown
				return;
			}
			
			if (input.get('disabled') || !input.isInstanceOf('input-html-inline')) {
				
				if (input.isInstanceOf('input-html-inline')) {
					//Stop editing, but keep EditorToolbar
					this.stopEditing(true);
					this.get('host').settings.hide();
					Supra.Manager.EditorToolbar.execute();	
				} else {
					//Stop editing
					this.stopEditing();
				}
				
				// Change active slide
				active = this.get('host').get('activeSlideId');
				if (active != id) {
					this.get('host').set('activeSlideId', id);
				}
				
				this._activeInput = input;
				this._activePropertyId = property.id;
				
				this.fire('focusItem', {'input': input, 'id': id, 'property': property});
				
				input.set('disabled', false);
				
				if (!input.startEditing()) {
					// Input rejected editing, just show form instead
					// This will happen if input MediaInline value is empty (nether video nor image)
					this.get('host').settings.showForm();
				}
			}
		},
		
		/**
		 * Start editing inline-media or inline-image if value is set, otherwise
		 * UI is "Add" button, which is controlled by input and shouldn't be handler here
		 */
		_startEditingPartialInline: function (event, property, input, id) {
			if (input.get('value')) {
				this._startEditing(event, property, input, id);
			}
		},
		
		/**
		 * Start editing slide by opening sidebar
		 * 
		 * @param {Object} event Event facade object
		 * @param {String} id Slide id
		 * @private
		 */
		_startEditingGlobal: function (event, id) {
			var active = this.get('host').get('activeSlideId');
			
			if (!id || active == id) {
				// Selected item is already beeing edited, don't need to do anything
				return;
			}
			
			if (Manager.getAction('MediaSidebar').get('visible')) {
				// Can't edit anything while media library is shown
				return;
			}
			
			// Stop editing previous input to normalize UI
			this.stopEditing();
			
			// Change active slide
			this.get('host').set('activeSlideId', id);
			
			// Show settings form
			this.get('host').settings.showForm();
			
			this.fire('focusItem', {'input': null, 'id': id, 'property': null});
		},
		
		/**
		 * Deatcivate input: disable content editing and show sidebar
		 */
		stopEditing: function (preserveToolbar) {
			var input = this._activeInput;
			
			if (input && !input.get('disabled')) {
				if (input.isInstanceOf('input-html-inline')) {
					
					input.set('disabled', true);
					if (preserveToolbar !== true) {
						Supra.Manager.EditorToolbar.hide();
						
						this.get('host').settings.reset();
						this.get('host').settings.show();
					}
					
				} else if (input.isInstanceOf('input-media-inline')) {
					this.get('host').settings.reset();
					this.get('host').settings.show();
				}
				
				this.fire('blurItem', {'input': input, 'property': this._activePropertyId});
			}
			
			this._stopEditingLoop = false;
			this._activeInput = null;
			this._activePropertyId = null;
		},
		
		/**
		 * Return true if user is editing something
		 */
		isEditing: function () {
			return !!this.get('activeItemId');
		},
		
		/**
		 * Fire data change event
		 * 
		 * @param {Object} event Event facade object
		 * @param {String} property Changed property name
		 * @private
		 */
		_firePropertyChangeEvent: function (event, property, input) {
			var id   = this.get('activeItemId'),
				data = this.get('host').data,
				save = {};
			
			if (id && property && !this.silentUpdatingValues) {
				property = this.get('host').settings.getProperty(property.id);
				if (property.type == 'InlineHTML') {
					// Inline HTML must be parsed, can't be easily done afterwards
					save[property.id] = input.get('saveValue');
				} else {
					// All other properties, including image can be parsed correctly
					// We will need all image data, to restore state after slide change
					save[property.id] = input.get('value');
				}
				data.changeSlide(id, save);
			}
		},
		
		
		/* ---------------------------- HTML NEW ITEM RENDERING --------------------------- */
		
		
		/**
		 * Render new item bar
		 */
		renderNewItemSelector: function () {
			var control = TEMPLATE_ADD_NEW_ITEM,
				iframe = this.get('iframe'),
				container = iframe.one('*[data-supra-container]'),
				
				layouts = this.get('host').layouts,
				data    = layouts.getAllLayouts(),
				layout  = null,
				
				wrapper_html = null,
				html    = '';
			
			if (data.length > 1) {
				// Multiple templates, create button for each
				for (var i=0, ii=data.length; i<ii; i++) {
					layout = layouts.getLayoutById(data[i].id);
					html += '<span class="supra-gallery-new-layout" data-id="' + layout.id + '"><span class="supra-button">' + Supra.Intl.get(['gallery', 'add']) + layout.title + '</span></span>';
				}
			} else if (data.length) {
				// Single template, different design ("+" button)
				html = TEMPLATE_ADD_NEW_ITEM_LAYOUT.replace('{{ id }}', data[0].id);
			} else {
				// Close manager, bacause it's not possible to edit items
				this.get('host').close();
				
				// Show message to user before closing manager
				var block = Action.getContent().get('activeChild');
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['gallery', 'error_template']).replace('{block}', block.getBlockTitle()),
					'buttons': [{'id': 'error'}]
				});
				
				Y.log('Block "' + block.getBlockTitle() + '" doesn\'t have any templates. Block configuration must specify them.', 'error', 'gallery.view');
				
				return false;
			}
			
			wrapper_html = this.getItemTemplateWrapper({'class': ['supra-gallery-new']});
			html = wrapper_html[0] + TEMPLATE_ADD_NEW_ITEM.replace('{{ layouts }}', html) + wrapper_html[1];
			control = Y.Node.create(html);
			container.append(control);
			
			// Event listeners
			control.all('span.supra-gallery-new-layout .supra-button, span.supra-gallery-new-layout-simple').on('click', this._onNewItemClick, this);
			
			this.newItemControl = control;
		},
		
		/**
		 * Returns item template wrapper node info
		 * 
		 * @returns {Object} Item template wrapper node info
		 * @private
		 */
		_getItemTemplateWrapperInfo: function () {
			var iframe = this.get('iframe'),
				container = iframe.one('*[data-supra-container]'),
				
				layouts = this.get('host').layouts,
				data    = layouts.getAllLayouts(),
				
				tmp     = '',
				tag     = '',
				
				attrs   = {'class': []},
				
				attr_arr = null;
			
			for (var i=0, ii=data.length; i<ii; i++) {
				tmp = layouts.getLayoutById(data[i].id).html.match(/<([a-z]+)([^>]*)/);
				if (tmp) {
					tag = tmp[1];
					
					// Parse attributes
					if (tmp[2] && tmp[2].indexOf('class="') != -1) {
						attrs['class'] = tmp[2].match(/class="([^"]*)"/)[1].split(' ');
					}
					
					break;
				}
			}
			
			if (!tag) {
				if (container.test('UL, OL')) {
					tag = 'LI';
				} else {
					tag = 'DIV';
				}
			}
			
			return {
				'tag': tag,
				'attrs': attrs
			}
		},
		
		/**
		 * Returns item start and end tag html
		 * 
		 * @param {Object} attrs Additional attributes
		 * @returns {Array} Array with start and end tag html
		 */
		getItemTemplateWrapper: function (attrs) {
			var info = this._getItemTemplateWrapperInfo(),
				attributes = [],
				attr = null;
			
			for (attr in info.attrs) {
				if (Y.Lang.isArray(info.attrs[attr])) {
					attributes.push(
						attr + '="' + (attrs[attr] || []).concat(info.attrs[attr]).join(' ') + '"'
					);
				} else if (attrs[attr]) {
					attributes.push(
						attr + '="' + String(attrs[attr]) + '"'
					);
				} else {
					attributes.push(
						attr + '="' + String(info.attrs[attr]) + '"'
					);
				}
			}
			
			return [
				'<' + info.tag + (attributes.length ? ' ' + attributes.join(' ') : '') + '>',
				'</' + info.tag + '>'
			];
		},
		
		/**
		 * Returns item CSS selector
		 * 
		 * @returns {String} Item CSS selector
		 */
		getItemCSSSelector: function () {
			var info = this._getItemTemplateWrapperInfo(),
				selector = info.tag;
			
			if (info.attrs['class'] && info.attrs['class'].length) {
				selector += '.' + info.attrs['class'].join('.');
			}
			
			return selector;
		},
		
		/**
		 * On new item click add item
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_onNewItemClick: function (event) {
			var target = event.target.closest('.supra-gallery-new-layout'),
				layout = target.getAttribute('data-id');
			
			if (layout) {
				var data = this.get('host').data,
					item = data.getNewSlideData();
				
				item.layout = layout;
				data.addSlide(item);
				
				event.preventDefault();
			}
		},
		
		
		/* ---------------------------- HTML CONTENT RENDERING --------------------------- */
		
		
		/**
		 * Returns item ID from node
		 * 
		 * @param {Object} node Node for which to look for ID
		 * @returns {String} id Item id
		 */
		getIdFromNode: function (node) {
			var nodes = this.widgets.nodes,
				id    = null;
			
			node = node.closest('.supra-gallery-item');
			if (!node) return null;
			
			for (id in nodes) {
				if (nodes[id].compareTo(node)) return id;
			}
			
			return node;
		},
		
		/**
		 * Render all items
		 */
		renderItems: function () {
			var data = this.get('host').data.get('data'),
				i    = 0,
				ii   = data.length,
				
				iframe = this.get('iframe'),
				container = iframe.one('*[data-supra-container]'),
				
				node = null;
			
			// Remove old items
			container.empty();
			
			this.silentUpdatingValues = true;
			this.get('host').settings.silentUpdatingValues = true;
			
			for (; i<ii; i++) {
				this.renderItem(data[i].id);
			}
			
			this.renderNewItemSelector();
			
			this.silentUpdatingValues = false;
			this.get('host').settings.silentUpdatingValues = false;
			
			// Event for other components
			this.fire('addItem');
			
			// Style for empty list
			node = Y.Node(this.getDocument().body);
			
			node.toggleClass('supra-gallery-empty', !data.length);
			node.addClass('supra-gallery-has-item-blurred');
		},
		
		/**
		 * Render item from data
		 * 
		 * @param {String} id Item id
		 */
		renderItem: function (id) {
			if (!id) return;
			
			// Stop editing current item
			this.stopEditing();
			
			// Don't update data, ui, etc. on value change
			var updating = this.silentUpdatingValues;
			
			if (!updating) {
				this.silentUpdatingValues = true;
				this.get('host').settings.silentUpdatingValues = true;
			}
			
			var container = null,
				new_item_control = this.newItemControl,
				active = this.get('activeItemId'),
				id = (typeof id === 'string' ? id : active),
				iframe = this.get('iframe'),
				data = this.get('host').data.getSlideById(id);
			
			// Find container node
			container = iframe.one('*[data-supra-container]');
			
			if (!container.compareTo(this.get('listNode'))) {
				this.set('listNode', container);
			}
			
			if (!data) {
				// Nothing to render
				return;
			}
			
			// Render new item
			var html = Y.Node.create(this.get('host').layouts.getLayoutHtml(data.layout));
			html.addClass('supra-gallery-item');
			
			if (new_item_control) {
				new_item_control.insert(html, 'before');
			} else {
				container.append(html);
			}
			
			// Save node
			this.widgets.nodes[id] = html;
			
			// Set partially inline properties
			this._restorePartialInlineInputs(data, html);
			
			// Restore input properties
			this._restoreInputs(data, html);
			
			// On click inside element focus it
			html.after('click', this._startEditingGlobal, this, id);
			
			// On input value change update data, ui, etc.
			if (!updating) {
				this.silentUpdatingValues = false;
				this.get('host').settings.silentUpdatingValues = false;
				
				// Event for other components
				this.fire('addItem');
				
				// Style for new item bar
				Y.Node(this.getDocument().body).removeClass('supra-gallery-empty');
			}
		},
		
		/**
		 * Remove item from view
		 * 
		 * @param {String} id Item id
		 */
		removeItem: function (id) {
			if (!id) return;
			
			var node = this.widgets.nodes[id];
			if (node) {
				// Don't update data, ui, etc.
				this.silentUpdatingValues = true;
				this.get('host').settings.silentUpdatingValues = true;
				
				// Remove inputs
				this._cleanUpInputs(id);
				
				// Remove node
				node.remove(true);
				
				// On input value change update data, ui, etc.
				this.silentUpdatingValues = false;
				this.get('host').settings.silentUpdatingValues = false;
				
				// Event for other components
				this.fire('removeItem');
				
				// Style for new item bar
				if (!this.get('host').data.get('data').length) {
					Y.Node(this.getDocument().body).addClass('supra-gallery-empty');
				}
			}
		},
		
		/**
		 * Remove old inputs before content is destroyed
		 * 
		 * @private
		 */
		_cleanUpInputs: function (id) {
			var allInputs = this.widgets.inputs,
				inputs = null,
				ii = 0,
				i = 0,
				values = {},
				events = this.widgets.events,
				ee = events.length,
				e = 0;
			
			if (this._activeInput) {
				this._firePropertyChangeEvent({}, {'id': this._activePropertyId}, this._activeInput);
			}
			
			// Remove inline inputs
			if (!id) {
				// Remove ALL inputs
				for (id in allInputs) {
					inputs = allInputs[id];
					for (i=0,ii=inputs.length; i<ii; i++) {
						values[inputs[i].get('id')] = inputs[i].get('value');
						inputs[i].destroy();
					}
				}
				
				// Remove contained input event listeners
				for (; e<ee; e++) {
					events[e].detach();
				}
				
				this.widgets.inputs = {};
				this.widgets.events = [];
			} else {
				// Only for given id
				inputs = allInputs[id];
				if (inputs) {
					for (i=0,ii=inputs.length; i<ii; i++) {
						values[inputs[i].get('id')] = inputs[i].get('value');
						inputs[i].destroy();
					}
				}
				
				delete(this.widgets.inputs[id]);
			}
			
			this._activeInput = null;
			
			// Reset partial inline inputs
			var properties = this.get('host').settings.getProperties(),
				property = null,
				i = 0,
				ii = properties.length,
				form = this.get('host').settings.getForm(),
				input = null;
			
			for (; i<ii; i++) {
				property = properties[i];
				if (Supra.Input.isInline(property.type)) {
					input = form.getInput(property.id);
					if (input) {
						input.set('targetNode', null);
					}
				} else if (property.type == 'Set') {
					input = form.getInput(property.id);
					
					// GalleryViewButton plugin
					if (input && input.inline) {
						input.inline.set('targetNode', null);
					}
				}
			}
		},
		
		/**
		 * Enable inputs for item
		 * 
		 * @param {String} id Item id
		 * @private
		 */
		_enableInputs: function (id) {
			var properties = this.get('host').settings.getProperties(),
				property = null,
				i = 0,
				ii = properties.length,
				node = null,
				form = this.get('host').settings.getForm(),
				input,
				container = this.widgets.nodes[id],
				data = this.get('host').data.getSlideById(id),
				value = null;
			
			if (!container) {
				return;
			}
			
			this.silentUpdatingValues = true;
			this.get('host').settings.silentUpdatingValues = true;
			
			for (; i<ii; i++) {
				property = properties[i];
				input = form.getInput(property.id);
				value = data[property.id];
				
				if (property.type == 'InlineImage' || property.type == 'InlineMedia') {
					node = container.one('*[data-supra-item-property="' + property.id + '"]');
					
					if (node) {
						input.set('targetNode', node);
						input.set('value', value);
					} else {
						input.set('targetNode', null);
					}
				} else if (property.type == 'BlockBackground') {
					node = container.one('*[data-supra-item-property="' + property.id + '"]');
					
					if (node) {
						node.addClass('hidden');
						node = node.ancestor();
						
						input.set('targetNode', node);
						input.set('value', value);
					} else {
						input.set('targetNode', null);
					}
				} else if (property.type == 'Set') {
					node = container.one('*[data-supra-item-property="' + property.id + '"]');
					
					// GalleryViewButton plugin
					if (input && input.inline) {
						if (node) {
							input.inline.set('targetNode', node);
							input.set('value', value);
						} else {
							input.inline.set('targetNode', null);
						}
					}
				}
			}
			
			this.silentUpdatingValues = false;
			this.get('host').settings.silentUpdatingValues = false;
		},
		
		/**
		 * Restore partial inline input nodes and values
		 * 
		 * @param {Object} data Slide data
		 * @private
		 */
		_restorePartialInlineInputs: function (data, container) {
			var properties = this.get('host').settings.getProperties(),
				property = null,
				i = 0,
				ii = properties.length,
				iframe = this.get('iframe'),
				node = null,
				form = this.get('host').settings.getForm(),
				input;
			
			if (!container) {
				container = iframe;
			}
			
			for (; i<ii; i++) {
				property = properties[i];
				if (property.type == 'InlineImage' || property.type == 'InlineMedia') {
					node = container.one('*[data-supra-item-property="' + property.id + '"]');
					if (node) {
						input = form.getInput(property.id);
						
						node.on('mousedown', this._startEditingPartialInline, this, property, input, data.id);
						input.set('targetNode', node);
						input.set('value', data[property.id]);
						input.on('blur', this._onInputBlur, this, property, input);
						input.on('focus', this._onInputFocus, this, property, input);
					}
				} else if (property.type == 'BlockBackground') {
					node = container.one('*[data-supra-item-property="' + property.id + '"]');
					if (node) {
						input = form.getInput(property.id);
						
						node.addClass('hidden');
						node = node.ancestor();
						
						input.set('targetNode', node);
						input.set('value', data[property.id]);
					}
				} else if (property.type == 'Set') {
					node = container.one('*[data-supra-item-property="' + property.id + '"]');
					if (node) {
						input = form.getInput(property.id);
						
						// GalleryViewButton plugin
						if (input && input.inline) {
							input.inline.set('targetNode', node);
						}
					}
				}
			}
		},
		
		/**
		 * Create new inputs after content is inserted
		 * 
		 * @param {Object} data Slide data
		 * @private
		 */
		_restoreInputs: function (data, container) {
			var properties = this.get('host').options.properties,
				ii = properties.length,
				i = 0,
				property = null,
				value  = null,
				input  = null,
				node   = null,
				srcNode = null,
				contNode = null,
				
				inputs = this.widgets.inputs[data.id] || (this.widgets.inputs[data.id] = []),
				create = !inputs.length,
				
				events = this.widgets.events,
				iframe = this.get('iframe'),
				
				active = this._activePropertyId,
				
				is_inline = false,
				is_contained = false;
			
			if (!container) {
				container = iframe;
			}
			
			for (; i<ii; i++) {
				property = properties[i];
				is_inline = Supra.Input.isInline(property.type);
				is_contained = Supra.Input.isContained(property.type);
				
				if (is_inline && !is_contained && create) {
					
					srcNode = node = container.one('*[data-supra-item-property="' + property.id + '"]');
					contNode = null;
					
					if (node) {
						value = data[property.id];
						
						input = new Supra.Input[property.type]({
							'doc': iframe.get('doc'),
							'win': iframe.get('win'),
							'toolbar': Manager.EditorToolbar.getToolbar(),
							'srcNode': srcNode,
							'targetNode': node,
							'value': value,
							'plugins': property.plugins
						});
						
						inputs.push(input);
						input.render(contNode);
						input.set('value', value);
						
						if (active && active == property.id) {
							input.set('disabled', false);
							this._activeInput = input;
						} else {
							input.set('disabled', true);
						}
						
						if (srcNode) {
							srcNode.on('mousedown', this._startEditing, this, property, input, data.id);
						}
						
						input.on('change', this._firePropertyChangeEvent, this, property, input, data.id);
						
						if (input.getEditor) {
							input.getEditor().addCommand('manage', Y.bind(this.stopEditing, this));
						}
					}
				} else if (!is_inline && is_contained) {
					// Image
					
					if (property.type == 'Image') {
						node = container.one('*[data-supra-item-property="' + property.id + '"]');
						
						if (node) {
							value = data[property.id];
							input = this.get('host').settings.getForm().getInput(property.id);
							
							events.push(
								input.on('valueChange', this._onContainerInputChange, this)
							);
							
							input.set('value', value);
							
						}
					}
				}
			}
		},
		
		/**
		 * Handle container input value change
		 * 
		 * @param {Object} evt Event facade object
		 */
		_onContainerInputChange: function (evt) {
			var input    = evt.target,
				id       = evt.target.get('name') || evt.target.get('id'),
				property = this.get('host').settings.getProperty(id),
				node     = this.get('iframe').one('*[data-supra-item-property="' + property.id + '"]'),
				value    = null;
			
			if (property.type == 'Image') {
				value = (evt.newVal ? evt.newVal.file_web_path : '') || '';
				if (node.test('img')) {
					// Update src
					node.setAttribute('src', value);
				} else {
					// Update background image
					node.setStyle('background-image', 'url(' + value + ')');
				}
			}
		},
		
		/**
		 * Returns HTML needed to recreate block
		 * 
		 * @returns {String} HTML
		 * @private
		 */
		getHtml: function () {
			var block = Action.getContent().get('activeChild'),
				
				listSelector = '*[data-supra-container]',
				listNode = block.getNode().one(listSelector),
				
				node = null,
				nodeTag = '',
				nodeClass = '',
				nodeId = '',
				
				cloned = null,
				html = '',
				structure = [];
			
			if (!listNode) {
				// Close manager, since it's not edit items
				this.get('host').close();
				
				// Show message to user before closing manager
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['gallery', 'error_template']).replace('{block}', block.getBlockTitle()),
					'buttons': [{'id': 'error'}]
				});
				
				Y.log('Block "' + block.getBlockTitle() + '" list node can\'t be identified. Block list node must have "data-supra-container" attribute.', 'error', 'gallery.view');
				
				return false;
			}
			
			// Copy existing HTML
			cloned = block.getNode().cloneNode(true);
			cloned.one(listSelector)
				  .addClass('supra-gallery-editing')
				  .empty();
			
			html = cloned.get('innerHTML');
			html = html.replace(/style="[^"]+"/ig, '');
			
			structure = [html];
			
			// Recreate DOM structure
			node = block.getNode().getDOMNode();
			
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
					structure.unshift('<div class="yui3-inline-reset yui3-box-reset supra-gallery-wrapper">');
					structure.push('</div>');
					
					// Add class to the body
					nodeClass += ' supra-gallery';
				}
				
				structure.unshift('<' + nodeTag + '' + (nodeTag === 'html' ? ' lang=' + (node.getAttribute('lang') || '') : '') + ' class="' + nodeClass + '" id="' + nodeId + '">');
				structure.push('</' + nodeTag + '>');
				
				node = node.parentNode;
			}
			
			structure[0] += '<head>' + this.getHTMLLinks() + '</head>';
			
			return structure.join('');
		},
		
		/**
		 * Returns HTML for link tags
		 * 
		 * @returns {String} HTML for link tags
		 * @private
		 */
		getHTMLLinks: function () {
			// Recreate styles
			var doc = this.getOriginalDocument(),
				google_fonts = null,
				fonts_nodes = null,
				
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
				linkMedia = links.item(i).getAttribute('media') || 'all';
				
				stylesheets.push('<link rel="stylesheet" type="text/css" href="' + linkHref + '" media="' + linkMedia + '" />');
			}
			
			for (; s<ss; s++) {
				styleMedia = styles.item(s).getAttribute('media') || 'all';
				stylesheets.push('<style type="text/css" media="' + linkMedia + '">' + styles.item(s).get('innerHTML') + '</style>');
			}
			
			// Gallery manager stylesheet for new item, drag and drop, etc. styles
			linkHrefExtra = Manager.Loader.getActionInfo('Gallery').folder + 'modules/view.css';
			stylesheets.push('<link rel="stylesheet" type="text/css" href="' + linkHrefExtra + '" />');
			
			// Google fonts
			google_fonts = new Supra.GoogleFonts({'doc': doc});
			fonts_nodes = google_fonts.getLinkNodes();
			
			if (fonts_nodes.length) {
				for (var i=0,ii=fonts_nodes.length; i<ii; i++) {
					stylesheets.push('<link rel="stylesheet" type="text/css" href="' + fonts_nodes[i].getAttribute('href') + '" />');
				}
			}
			
			return stylesheets.join('');
		},
		
		/**
		 * Returns original iframe object
		 * 
		 * @returns {Object} Original iframe object
		 */
		getOriginalIframe: function () {
			return Action.getIframeHandler();
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
			return doc ? Y.Node(doc.body).one('.supra-gallery-wrapper') : null;
		}
		
		
	});
	
	Supra.GalleryView = View;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.iframe']});