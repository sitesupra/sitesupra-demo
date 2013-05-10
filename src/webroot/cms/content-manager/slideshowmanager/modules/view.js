YUI.add('slideshowmanager.view', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/**
	 * Settings form
	 */
	function View (config) {
		View.superclass.constructor.apply(this, arguments);
	}
	
	View.NAME = 'slideshowmanager-view';
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
		 * Input values
		 * @type {Object}
		 * @private
		 */
		inputValues: null,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this.widgets = {
				'inputs': [],
				'events': []
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
			
			// Container node
			var node = this.get('listNode');
			if (node) {
				node.destroy(true);
				this.set('listNode', null);
			}
			
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
			
 			iframe.render(host.one('.su-slideshow-manager-content'));
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
				iframe.once('ready', this.renderItem, this);
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
				new_data = null;
			
			if (iframe && !iframe.get('loading')) {
				old_id = this.get('activeItemId');
				old_data = this.get('host').data.getSlideById(old_id);
				new_data = this.get('host').data.getSlideById(id);
				
				this.updateLayoutClassName(old_data ? old_data.layout : '', new_data ? new_data.layout : '');
				this.renderItem(id);
				return id;
			}
		},
		
		/**
		 * Update layout classname
		 * 
		 * @param {String} old_layout Old layout id
		 * @param {String} new_layout New layout id
		 */
		updateLayoutClassName: function (old_layout, new_layout) {
			if (old_layout === new_layout) return;
			
			var iframe = this.get('iframe');
			if (!iframe || iframe.get('loading')) return;
			
			var node      = iframe.one('*[data-supra-container]'),
				old_class = 'layout-' + old_layout,
				new_class = 'layout-' + new_layout;
			
			node.replaceClass(old_class, new_class);
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
		 * On input focus start editing it
		 * This is for inline image, background inputs
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} property Property info
		 * @param {Object} input Property input
		 * @private
		 */
		_onInputFocus: function (event, property, input) {
			if (input !== this._activeInput) {
				this._startEditing(event, property, input);
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
		 * @private
		 */
		_startEditing: function (event, property, input) {
			var old_input = this._activeInput;
			
			if (old_input === input) {
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
				
				this._activeInput = input;
				this._activePropertyId = property.id;
				
				input.set('disabled', false);
				input.startEditing();
			}
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
						this.get('host').settings.show();
					}
					
				} else if (input.isInstanceOf('input-media-inline')) {
					
					this.get('host').settings.show();
					
				}
			}
			
			this._stopEditingLoop = false;
			this._activeInput = null;
			this._activePropertyId = null;
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
		
		
		/* ---------------------------- HTML CONTENT RENDERING --------------------------- */
		
		
		/**
		 * Render item from data
		 * 
		 * @param {String}
		 */
		renderItem: function (id) {
			// Don't update data, ui, etc. on value change
			this.silentUpdatingValues = true;
			this.get('host').settings.silentUpdatingValues = true;
			
			var container = null,
				id = (typeof id === 'string' ? id : this.get('activeItemId')),
				iframe = this.get('iframe'),
				data = this.get('host').data.getSlideById(id);
			
			// Destroy old inline properties
			this._cleanUpInputs();
			
			// Find container node
			container = iframe.one('*[data-supra-container]');
			this.set('listNode', container);
			
			// Remove old elements
			container.empty();
			
			if (!data) {
				// Nothing to render
				return;
			}
			
			// Render new item
			var html = this.get('host').layouts.getLayoutHtml(data.layout);
			container.set('innerHTML', html);
			
			// Classname for styling
			container.addClass('layout-' + data.layout);
			
			// Set partially inline properties
			this._restorePartialInlineInputs(data);
			
			// Restore input properties
			this._restoreInputs(data);
			
			// On input value change update data, ui, etc.
			this.silentUpdatingValues = false;
			this.get('host').settings.silentUpdatingValues = false;
		},
		
		/**
		 * Remove old inputs before content is destroyed
		 * 
		 * @private
		 */
		_cleanUpInputs: function () {
			var inputs = this.widgets.inputs,
				ii = inputs.length,
				i = 0,
				values = {},
				events = this.widgets.events,
				ee = events.length,
				e = 0;
			
			if (this._activeInput) {
				this._firePropertyChangeEvent({}, {'id': this._activePropertyId}, this._activeInput);
			}
			
			// Remove contained input event listeners
			for (; e<ee; e++) {
				events[e].detach();
			}
			
			// Remove inline inputs
			for (; i<ii; i++) {
				values[inputs[i].get('id')] = inputs[i].get('value');
				inputs[i].destroy();
			}
			
			this.widgets.events = [];
			this.widgets.inputs = [];
			this.inputValues = values;
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
					
					// SlideshowManagerViewButton plugin
					if (input && input.inline) {
						input.inline.set('targetNode', null);
					}
				}
			}
		},
		
		/**
		 * Restore partial inline input nodes and values
		 * 
		 * @param {Object} data Slide data
		 * @private
		 */
		_restorePartialInlineInputs: function (data) {
			var properties = this.get('host').settings.getProperties(),
				property = null,
				i = 0,
				ii = properties.length,
				iframe = this.get('iframe'),
				node = null,
				form = this.get('host').settings.getForm(),
				input;
			
			for (; i<ii; i++) {
				property = properties[i];
				if (property.type == 'InlineImage' || property.type == 'InlineMedia') {
					node = iframe.one('*[data-supra-item-property="' + property.id + '"]');
					if (node) {
						input = form.getInput(property.id);
						
						node.on('mousedown', this._startEditing, this, property, input);
						input.set('targetNode', node);
						input.set('value', data[property.id]);
						input.on('blur', this._onInputBlur, this, property, input);
						input.on('focus', this._onInputFocus, this, property, input);
					}
				} else if (property.type == 'BlockBackground') {
					node = iframe.one('*[data-supra-item-property="' + property.id + '"]');
					if (node) {
						input = form.getInput(property.id);
						
						node.addClass('hidden');
						node = node.ancestor();
						
						input.set('targetNode', node);
						input.set('value', data[property.id]);
					}
				} else if (property.type == 'Set') {
					node = iframe.one('*[data-supra-item-property="' + property.id + '"]');
					if (node) {
						input = form.getInput(property.id);
						
						// SlideshowManagerViewButton plugin
						if (input && input.inline) {
							input.inline.set('targetNode', node);
						}
					}
				} else if (property.type == 'SlideshowInputResizer') {
					node = iframe.one('*[data-supra-container]');
					if (node) {
						input = form.getInput(property.id);
						
						input.set('targetNode', node);
						input.set('value', data[property.id]);
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
		_restoreInputs: function (data) {
			var properties = this.get('host').options.properties,
				ii = properties.length,
				i = 0,
				property = null,
				values = this.inputValues,
				value  = null,
				input  = null,
				node   = null,
				srcNode = null,
				contNode = null,
				inputs = this.widgets.inputs,
				events = this.widgets.events,
				iframe = this.get('iframe'),
				
				active = this._activePropertyId,
				
				is_inline = false,
				is_contained = false;
			
			for (; i<ii; i++) {
				property = properties[i];
				is_inline = Supra.Input.isInline(property.type);
				is_contained = Supra.Input.isContained(property.type);
				
				if (is_inline && !is_contained) {
					
					if (property.type == 'SlideshowInputResizer') {
						contNode = node = iframe.one('*[data-supra-container]');
						srcNode = null;
					} else {
						srcNode = node = iframe.one('*[data-supra-item-property="' + property.id + '"]');
						contNode = null;
					}
					
					if (node) {
						value = data[property.id] || values[property.id];
						
						input = new Supra.Input[property.type]({
							'doc': iframe.get('doc'),
							'win': iframe.get('win'),
							'toolbar': Manager.EditorToolbar.getToolbar(),
							'srcNode': srcNode,
							'targetNode': node,
							'value': value
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
							srcNode.on('mousedown', this._startEditing, this, property, input);
						}
						
						input.on('change', this._firePropertyChangeEvent, this, property, input);
						
						if (input.getEditor) {
							input.getEditor().addCommand('manage', Y.bind(this.stopEditing, this));
						}
					}
				} else if (!is_inline && is_contained) {
					// Image
					
					if (property.type == 'Image') {
						node = iframe.one('*[data-supra-item-property="' + property.id + '"]');
						
						if (node) {
							value = data[property.id] || values[property.id];
							input = this.get('host').settings.getForm().getInput(property.id);
							
							events.push(
								input.on('valueChange', this._onContainerInputChange, this)
							);
							
							input.set('value', value);
							
						}
					}
				}
			}
			
			this.inputValues = {};
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
					'message': Supra.Intl.get(['slideshowmanager', 'error_template']).replace('{block}', block.getBlockTitle()),
					'buttons': [{'id': 'error'}]
				});
				
				Y.log('Block "' + block.getBlockTitle() + '" list node can\'t be identified. Block list node must have "data-supra-container" attribute.', 'error', 'slideshowmanager.view');
				
				return false;
			}
			
			// Copy existing HTML
			cloned = block.getNode().cloneNode(true);
			cloned.one(listSelector)
				  .addClass('su-slideshowmanager-editing')
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
					structure.unshift('<div class="yui3-inline-reset yui3-box-reset supra-slideshowmanager-wrapper">');
					structure.push('</div>');
					
					// Add class to the 
					nodeClass += ' supra-slideshowmanager';
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
				fonts_node = null,
				
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
			linkHrefExtra = Manager.Loader.getActionInfo('SlideshowManager').folder + 'modules/view.css';
			stylesheets.push('<link rel="stylesheet" type="text/css" href="' + linkHrefExtra + '" />');
			
			// Google fonts
			google_fonts = new Supra.GoogleFonts({'doc': doc});
			fonts_node = google_fonts.getLinkNode();
			
			if (fonts_node) {
				stylesheets.push('<link rel="stylesheet" type="text/css" href="' + fonts_node.getAttribute('href') + '" />');
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
			return doc ? Y.Node(doc.body).one('.supra-slideshowmanager-wrapper') : null;
		}
		
		
	});
	
	Supra.SlideshowManagerView = View;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.iframe']});