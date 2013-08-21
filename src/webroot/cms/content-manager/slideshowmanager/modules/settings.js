YUI.add('slideshowmanager.settings', function (Y) {
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
	
	Settings.NAME = 'slideshowmanager-settings';
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
		},
		'activeItemIndex': {
			'value': null,
			'setter': '_setActiveItemIndex'
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
				'deleteButton': null
			};
			
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(Settings.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(Settings.NAME, []); /*{
				'id': 'done',
				'context': this,
				'callback': this.onSidebarDone
			}]);*/
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
				// Don't close editing when clicking outside image
				if (properties[i].type == 'InlineMedia') {
					properties[i].autoClose = false;
					properties[i].separateSlide = false;
					properties[i].allowZoomResize = true;
					properties[i].allowCropZooming = true;
					properties[i].labelAddVideo = Supra.Intl.get(['slideshowmanager', 'media', 'add_video']);
					properties[i].labelAddImage = Supra.Intl.get(['slideshowmanager', 'media', 'add_image']);
				}
				if (Supra.Input.isContained(properties[i].type)) {
					filtered.push(properties[i]);
				}
			}
			
			return filtered;
		},
		
		/**
		 * Returns non-inline properties grouped by 'group' attribute
		 * 
		 * @returns {Array} List of non-inline properties grouped
		 */
		getGoupedProperties: function () {
			var properties = this.getProperties(),
				i = 0,
				ii = properties.length,
				
				groups = {},
				group_inputs = {},
				groups_arr = this.get('host').options.property_groups || [],
				g = 0,
				gg = groups_arr.length,
				
				grouped = [],
				
				id = null;
			
			// Index all groups
			for (; g<gg; g++) {
				groups[groups_arr[g].id] = groups_arr[g];
			}
			
			for (; i<ii; i++) {
				id = properties[i].group;
				if (id && id in groups) {
					// Add property to the group
					if (!group_inputs[id]) {
						// Create input for group
						group_inputs[id] = {
							'id': groups[id].id,
							'properties': [],
							'labelButton': groups[id].label,
							'type': 'Group',
							'icon': groups[id].icon,
							'buttonStyle': groups[id].icon ? 'icon' : undefined
						};
						grouped.push(group_inputs[id]);
					}
					
					group_inputs[id].properties.push(properties[i]);
				} else {
					// Add property to the output
					grouped.push(properties[i]);
				}
			}
			
			return grouped;
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
			var properties = this.getGoupedProperties(),
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
			
			//Buttons input plugin
			input = form.getInput('buttons');
			if (input) {
				input.plug(Supra.SlideshowManagerViewButton, {});
			}
			
			//Mask form plugin, used to add color to the "mask" theme property
			input = form.getInput('theme');
			if (input) {
				form.plug(Supra.SlideshowManagerMaskPlugin, {
					'themeInputName': 'theme',
					'colorInputName': 'mask_color',
					'maskInputName': 'mask_image'
				});
			}
			
			//On input value change update inline inputs
			var id       = null,
				inputs   = form.getInputs(),
				input    = null,
				property = null;
			
			for (id in inputs) {
				property = Y.Array.find(properties, function (item) { return item.id == id; });
				input = inputs[id];
				
				input.after('valueChange', this._firePropertyChangeEvent, this, id, input);
				
				if (property && property.type == 'InlineMedia') {
					input.after('valueChange', this._updateMediaTypeClassName, this, id);
					input.after('render',      this._updateMediaTypeClassName, this, id);
				}
			}
			
			//Delete button
			var button = this.widgets.deleteButton = new Supra.Button({'label': Supra.Intl.get(['slideshowmanager', 'delete_slide']), 'style': 'small-red'});
				button.render(form.getContentNode());
				button.addClass('su-button-delete');
				button.on('click', this.fireRemoveEvent, this);
			
			if (this.get('host').list.getItemCount() <= 1) {
				button.hide();
			}
			
			this.widgets.form = form;
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
			var form = this.getForm(),
				slideshow = form.get('slideshow'),
				action = Manager.getAction('PageContentSettings'),
				index = this.get('activeItemIndex');
			
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
				//'hideDoneButton': true,
				'toolbarActionName': Settings.NAME,
				
				'title': this._uiGetTitle(index),
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
			// Stop editing
			var view = this.get('host').view,
				inputs = this.widgets.form.getInputs(),
				key = null,
				input = null;
			
			for (key in inputs) {
				input = inputs[key];
				if (input.get('focused') && input.isInstanceOf('input-media-inline') || input.isInstanceOf('block-background')) {
					input.stopEditing();
				}
			}
			
			this.hideForm();
			
			//this.get('host').close();
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
		 * On item count change update UI
		 */
		updateItemCount: function () {
			var button = this.widgets.deleteButton,
				count  = this.get('host').list.getItemCount(); 
			
			if (button) {
				if (count > 1) {
					button.show();
				} else {
					button.hide();
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
			
			this.updateItemCount();
			
			if (this.get('visible')) {
				var slideshow = form.get('slideshow'),
					rootSlideId = slideshow.getHistory()[0];
				
				// Scroll to root slide
				slideshow.set('slide', rootSlideId);
			}
			
			return id;
		},
		
		/**
		 * Returns sidebar title from index
		 * 
		 * @param {Number} index Active item index
		 * @returns {String} Sidebar title
		 * @private
		 */
		_uiGetTitle: function (index) {
			var action = Manager.getAction('PageContentSettings'),
				title  = '';
			
			if (index >= 0) {
				title = Supra.Intl.get(['slideshowmanager', 'sidebar_title_numbered']).replace('{nr}', index + 1);
			} else if (action.options) {
				title = action.options.title;
			}
			
			return title;
		},
		
		/**
		 * Update sidebar title
		 * 
		 * @param {Number} index Active item index
		 * @private
		 */
		_uiUpdateTitle: function (index) {
			var action = Manager.getAction('PageContentSettings'),
				title  = this._uiGetTitle(index);
			
			if (action.get('created')) {
				if (title) {
					action.set('title', title);
				}
			}
		},
		
		/**
		 * Active item index attribute setter
		 * 
		 * @param {Number} index Active item index
		 * @returns {Number} New attribute value
		 * @private
		 */
		_setActiveItemIndex: function (index) {
			this._uiUpdateTitle(index);
			return index;
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
		
		/**
		 * Update media input classnames
		 * 
		 * @TODO FIXME this is very dirty solution
		 * @param {String} name Property name
		 * @private
		 */
		_updateMediaTypeClassName: function (event, name) {
			if (!this.widgets.form) return;
			
			var input = this.widgets.form.getInput(name),
				value = input.get('value'),
				
				type = (value && value.type) || 'media',
				node = input.get('targetNode'),
				classname_old = '',
				classname_new = '';
			
			if (node) {
				node = node.closest('*[class*="type-media"], *[class*="type-image"], *[class*="type-video"]');
				
				if (node) {
					classname_old = node.getDOMNode().className.match(/[^\s]*type-(image|media|video)[^\s]*/i)[0];
					
					if (classname_old) {
						classname_new = classname_old
											.replace('type-image', 'type-' + type)
											.replace('type-video', 'type-' + type)
											.replace('type-media', 'type-' + type);
						
						node.removeClass(classname_old).addClass(classname_new);
					}
				}
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
	
	Supra.SlideshowManagerSettings = Settings;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.form']});