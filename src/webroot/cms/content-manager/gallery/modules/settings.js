YUI.add('gallery.settings', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/**
	 * Settings form
	 */
	function Settings (config) {
		Settings.superclass.constructor.apply(this, arguments);
	}
	
	Settings.NAME = 'gallery-settings';
	Settings.NS = 'settings';
	
	Settings.ATTRS = {
		// Visibility
		'visible': {
			'value': false,
			'setter': '_setVisible',
			'getter': '_getVisible'
		},
		'activeItemId': {
			'value': null,
			'setter': '_setActiveItemId'
		}
	};
	
	Y.extend(Settings, Y.Plugin.Base, {
		
		/**
		 * Widgets
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		/**
		 * Value update in progress, don't fire events
		 * @type {Boolean}
		 * @private
		 */
		silentUpdatingValues: false,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this.widgets = {
				'form': null,
				'deleteButton': null,
				'buttons': {}
			};
			
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(Settings.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(Settings.NAME, []);
		},
		
		/**
		 * Automatically called by Base, during destruction
		 * 
		 * @private
		 */
		destructor: function () {
			this.resetAll();
			this.destroyForm();
		},
		
		/**
		 * Reset cache, clean up
		 */
		resetAll: function () {
		},
		
		/**
		 * Fire remove event
		 * 
		 * @private
		 */
		fireRemoveEvent: function () {
			var id = this.get('activeItemId'),
				button = this.widgets.deleteButton;
			
			this.fire('removeClick', {'data': {'id': id}});
			
			// Show loading icon while animating list
			button.set('loading', true);
			Y.later(350, this, function () {
				button.set('loading', false);
			});
		},
		
		
		/* ---------------------------- Form --------------------------- */
		
		
		/**
		 * Returns settings form
		 * 
		 * @returns {Object} Form
		 */
		getForm: function () {
			return this.widgets.form || this.createForm();
		},
		
		/**
		 * Returns non-inline properties
		 * 
		 * @returns {Array} List of non-inline properties
		 */
		getProperties: function () {
			var properties = this.get('host').options.properties,
				filtered = [],
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (Supra.Input.isContained(properties[i].type)) {
					filtered.push(properties[i]);
				}
			}
			
			return filtered;
		},
		
		/**
		 * Returns inline-only properties
		 * 
		 * @returns {Array} List of inline-only properties
		 */
		getInlineProperties: function () {
			var properties = this.get('host').options.properties,
				filtered = [],
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (Supra.Input.isInline(properties[i].type)) {
					filtered.push(properties[i]);
				}
			}
			
			return filtered;
		},
		
		/**
		 * Returns property by id
		 * 
		 * @param {String} id Property id
		 * @returns {Object} Property data
		 */
		getProperty: function (id) {
			var properties = this.get('host').options.properties,
				filtered = [],
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (properties[i].id == id) {
					return properties[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Returns property by type
		 * 
		 * @param {String} type Input type
		 * @param {Function} filter Filter function, optional
		 * @returns {Object} Property
		 */
		getPropertyByType: function (type, filter) {
			var properties = this.get('host').options.properties,
				i          = 0,
				ii         = properties ? properties.length : null,
				filter     = Y.Lang.isFunction(filter) ? filter : null;
			
			for (; i<ii; i++) {
				if (properties[i].type == type) {
					if (filter) {
						if (!filter(properties[i])) {
							continue;
						}
					}
					
					return properties[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Create settigns form
		 * 
		 * @private
		 */
		createForm: function () {
			if (this.widgets.form) return this.widgets.form;
			
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').get('contentInnerNode');
			if (!content) return;
			
			//Properties form
			var properties = this.getProperties(),
				form_config = {
					'inputs': properties,
					'style': 'vertical',
					'slideshow': true
				};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
				form.addClass('yui3-form-fill'),
				input = null;
				
			this.widgets.form = form;
			
			//Buttons input plugin
			input = form.getInput('buttons');
			if (input) {
				input.plug(Supra.GalleryViewButton, {});
			}
			
			//On input value change update inline inputs
			var ii = properties.length,
				i = 0,
				type = null,
				input = null;
			
			for (; i<ii; i++) {
				input = form.getInput(properties[i].id);
				input.after('valueChange', this._firePropertyChangeEvent, this, properties[i].id, input);
			}
			
			// Render buttons for inline inputs
			this.renderInputButtons();
			
			//Delete button
			var button = this.widgets.deleteButton = new Supra.Button({'label': Supra.Intl.get(['gallery', 'delete_slide']), 'style': 'small-red'});
				button.render(form.getContentNode());
				button.addClass('su-button-delete');
				button.on('click', this.fireRemoveEvent, this);
			
			return form;
		},
		
		/**
		 * Destroy settings form
		 * 
		 * @private
		 */
		destroyForm: function () {
			if (this.settings_form) {
				var bounding = this.widgets.form.get('boundingBox');
				this.widgets.form.destroy();
				this.widgets.form = null;
				bounding.remove(true);
			}
		},
		
		/**
		 * Show form
		 * 
		 * @private
		 */
		showForm: function () {
			//Make sure PageContentSettings is rendered
			var form      = this.getForm(),
				slideshow = form.get('slideshow'),
				action    = Manager.getAction('PageContentSettings');
			
			if (!form) {
				if (action.get('loaded')) {
					if (!action.get('created')) {
						action.renderAction();
						this.showForm();
					}
				} else {
					action.once('loaded', function () {
						this.showForm();
					}, this);
					action.load();
				}
				return false;
			}
			
			action.execute(form, {
				'doneCallback': Y.bind(this.onSidebarDone, this),
				'toolbarActionName': Settings.NAME,
				
				'title': Supra.Intl.get(['gallery', 'sidebar_title']),
				'scrollable': false // we are using form slideshow, which will have scrollable
			});
			
			// Update form state
			slideshow.set('noAnimations', true);
			this.set('activeItemId', this.get('activeItemId'));
			slideshow.set('noAnimations', false);
		},
		
		/**
		 * When sidebar is closed stop editing associated input
		 * 
		 * @private
		 */
		onSidebarDone: function () {
			this.get('host').listView();
		},
		
		/**
		 * Hide form
		 * 
		 * @private
		 */
		hideForm: function () {
			var form = this.widgets.form;
			if (form && form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * Start editing inline input
		 * 
		 * @param {Object} property Property data
		 * @private
		 */
		startEditing: function (property) {
			var id     = this.get('host').view.get('activeItemId'),
				view   = this.get('host').view,
				
				form   = this.widgets.form,
				
				input  = null,
				inputs = view.widgets.inputs[id],
				i      = 0,
				ii     = inputs.length;
			
			if (inputs) {
				for (; i<ii; i++) {
					// Inline input?
					if (inputs[i].get('name') === property.id) {
						input = inputs[i];
						break;
					}
				}
				if (!input) {
					// Partial inline input?
					input = form.getInput(property.id);
				}
				
				if (input) {
					view._startEditing({}, property, input, id);
				}
			}
		},
		
		renderInputButtons: function () {
			//Add buttons for inline inputs
			var form = this.getForm(),
				content = form.getContentNode(),
				
				buttons = this.widgets.buttons = [],
				button  = null,
				
				inline  = this.getInlineProperties(),
				i       = inline.length - 1;
			
			for (; i>=0; i--) {
				button = new Supra.Button({
					'style': 'icon',
					'label': inline[i].label,
					'icon': '/cms/lib/supra/img/sidebar/icons/button-layout.png'
				});
				
				button.render(content);
				content.prepend(button.get('boundingBox'));
				
				button.addClass('su-button-fill');
				button.set('name', inline[i].id);
				buttons.push(button);
				
				button.on('click', function (event, property) {
					this.startEditing(property);
				}, this, inline[i]);
			}
		},
		
		/**
		 * Show buttons for which there are inline editables and hide for
		 * those for which there aren't
		 */
		showHideButtons: function () {
			var buttons = this.widgets.buttons,
				i       = 0,
				ii      = buttons.length,
				name    = '',
				
				form    = this.getForm(),
				
				id      = this.get('host').view.get('activeItemId'),
				view    = this.get('host').view,
				
				inputs  = view.widgets.inputs[id],
				input   = null,
				k       = 0,
				kk      = inputs.length;
			
			for (; i<ii; i++) {
				name = buttons[i].get('name');
				input = null;
				
				for (k=0; k<kk; k++) {
					if (inputs[k].get('name') == name) {
						input = inputs[k];
						break;
					}
				}
				
				if (!input) {
					input = form.getInput(name);
					if (input && !input.get('targetNode')) {
						input = null;
					}
				}
				
				if (input) {
					buttons[i].show();
				} else {
					buttons[i].hide();
				}
			}
		},
		
		
		/* ---------------------------- Data --------------------------- */
		
		
		/**
		 * activeItemId attribute setter
		 * 
		 * @param {String} id Active item ID
		 * @returns {String} New attribute value
		 * @private 
		 */
		_setActiveItemId: function (id) {
			var data   = this.get('host').data.getSlideById(id),
				form   = this.widgets.form;
			
			if (form) {
				this.silentUpdatingValues = true;
				form.setValues(data, 'id', true); // no encoding
				this.silentUpdatingValues = false;
			}
			
			if (this.get('visible')) {
				var slideshow = form.get('slideshow'),
					rootSlideId = slideshow.getHistory()[0];
				
				// Scroll to root slide
				slideshow.set('slide', rootSlideId);
			}
			
			if (!id) {
				this.hideForm();
			} else {
				this.showHideButtons();
			}
			
			return id;
		},
		
		/**
		 * Fire data change event
		 * 
		 * @param {Object} event Event facade object
		 * @param {String} property Changed property name
		 * @param {Object} input Input which value changed
		 * @private
		 */
		_firePropertyChangeEvent: function (event, property_name, input) {
			if (this.silentUpdatingValues) return;
			
			var id   = this.get('activeItemId'),
				data = this.get('host').data,
				save = {},
				value = '',
				property = null;
			
			if (event.newVal !== event.prevVal && id && property_name) {
				property = this.getProperty(property_name);
				if (property.type == 'InlineHTML') {
					// Inline HTML must be parsed right now, can't be easily done afterwards
					save[property_name] = input.get('saveValue');
				} else {
					// All other properties, including image can be parsed correctly
					// We will need all image data, to restore state after slide change
					save[property_name] = input.get('value');
				}
				
				data.changeSlide(id, save);
			}
		},
		
		
		/* ---------------------------- Show/hide --------------------------- */
		
		
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
		 * Reset panel to root
		 */
		reset: function () {
			this.getForm().get('slideshow').scrollRoot();
		},
		
		/**
		 * Visible attribute setter
		 * 
		 * @param {Boolean} visible Visibility state
		 * @returns {Boolean} New visibility state
		 * @private
		 */
		_setVisible: function (visible) {
			if (visible) {
				this.showForm();
			} else {
				this.hideForm();
			}
			return true;
		},
		
		/**
		 * Visible attribute getter
		 * 
		 * @returns {Boolean} True if settings form is visible, otherwise false
		 * @private
		 */
		_getVisible: function () {
			var form = this.widgets.form;
			return form && form.get('visible');
		}
		
	});
	
	Supra.GallerySettings = Settings;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.form']});