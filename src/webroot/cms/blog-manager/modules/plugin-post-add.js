//Invoke strict mode
"use strict";

YUI().add('blog.plugin-add-post', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Supra.Manager.getAction('Blog');
	
	
	/**
	 * Page edit settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginBlogPostAdd';
	Plugin.NS = 'post_add';
	
	Plugin.ATTRS = {
		// Button to which this plugin should binded to
		'target': {
			value: null
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Children widget list
		 * 
		 * @type {Object}
		 * @private
		 */
		'_widgets': {
			/* Form */
			'form': null,
			/* Form container panel */
			'panel': null,
			/* Create page button */
			'buttonCreate': null,
			/* Cancel button */
			'buttonCancel': null
		},
		
		/**
		 * Default page title when panel is opened
		 * 
		 * @type {String}
		 * @private
		 */
		'_defaultTitle': '',
		
		
	
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * Attach to event listeners, etc.
		 * 
		 * @constructor
		 * @private
		 */
		'initializer': function () {
			var host   = this.get('host'),
				button = this.get('target');
			
			button.on('click', this.show, this);
		},
		
		/**
		 * Create edit form popup
		 * 
		 * @private
		 */
		'_createPanel': function () {
			var container = this.get('host').one('.blog-add'),
				widgets = this._widgets,
				panel = null;
			
			widgets.panel = panel = new Supra.Panel({
				'srcNode': container,
				'autoClose': false,
				'arrowVisible': true,
				'zIndex': 6, // above new item and recycle bin
				'visible': false,
				'closeOnEscapeKey': true,
				'arrowAlign': this.get('target').get('boundingBox'),
				'xy': [10, 110]
			});
			
			//Create form
			this._createForm(container);
			
			//Render all widgets
			for(var i in widgets) {
				if (widgets[i]) {
					widgets[i].render();
				}
			}
			
			//Listeners
			this._bindEventListeners();
		},
		
		/**
		 * Create form
		 * 
		 * @param {Object} container Container element
		 * @private
		 */
		'_createForm': function (container) {
			var widgets = this._widgets,
				buttons = container.all('.middle button'),
				button_tpl = container.one('.template-section button'),
				button_lay = container.one('.layout-section button');
			
			//Buttons
			widgets.buttonCreate = new Supra.Button({'srcNode': buttons.item(0)});
			widgets.buttonCancel = new Supra.Button({'srcNode': buttons.item(1)});
			
			widgets.buttonCreate.on('click', this.createPage, this);
			widgets.buttonCancel.on('click', this.hide, this);
			
			//Form
			widgets.form = new Supra.Form({
				'srcNode': container.one('form')
			});
		},
		
		/**
		 * Bind all event listeners
		 * 
		 * @private
		 */
		'_bindEventListeners': function () {
			//Inputs
			var widgets = this._widgets,
				inputs = widgets.form.getInputs(),
				id = null;
			
			for(id in inputs) {
				inputs[id].on('change', this._onPagePropertyChange, this);
				
				//On return key create page
				if (inputs[id].isInstanceOf('input-string'))	{
					inputs[id].get('inputNode').on('keydown', function (e) {
						if (e.keyCode == 13) {
							var input = Y.Widget.getByNode(e.target);
							this._onPagePropertyChange({"target": input});				
							
							this.createPage();
						}
					}, this);
				}
			}
			
			inputs.title.on('focus', this._onTitleFocus, this);
			inputs.title.on('input', this._onPagePropertyChange, this);
		},
		
		/**
		 * On property change update tree node
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		'_onPagePropertyChange': function (e) {
			var input = e.target,
				id    = input.get('id'),
				value = input.get('value');
			
			// Create path from title
			if (id === 'title') {
				var input_path = this._widgets.form.getInput('path');
				input_path.set('value', Y.Lang.toPath(value));
			}
		},
		
		/**
		 * When title is focused remove default value
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		'_onTitleFocus': function (e) {
			var input  = e.target,
				value  = input.get('value'),
				placeholder_value = this._defaultTitle;
			
			if (value === placeholder_value) {
				input.set('value', '');
			}
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		/**
		 * Set form values
		 * 
		 * @param {Object} data Form values
		 */
		'setFormValues': function (data) {
			var form = this._widgets.form,
				input = null,
				title = Supra.Intl.get(['blog', 'posts', 'new_post_value_title']),
				path = Supra.Intl.get(['blog', 'posts', 'new_post_value_path']);
		
			form.getInput('title').set('label', Supra.Intl.get(['blog', 'posts', 'new_post_label_title']));
			
			//Find unique title and path which doesn't exist for any of the siblings
			title = data.title || title;
			path = data.path || path;
			
			input = form.getInput('title');
			input.set('value', title);
			
			this._defaultTitle = title;
			
			input = form.getInput('path');
			input.set('value', path);
		},
		
		/**
		 * Create a page
		 */
		'createPage': function () {
			var form     = this._widgets.form,
				host     = this.get('host'),
				deferred = null;
			
			deferred = host.addBlogPost({
				'title': form.getInput('title').get('value'),
				'path': form.getInput('path').get('value')
			});
			
			//Disable form
			form.set('disabled', true);
			this._widgets.buttonCreate.set('loading', true);
			this._widgets.buttonCancel.set('disabled', true);
			
			//Create
			deferred.done(function (data) {
				form.set('disabled', false);
				
				this._widgets.buttonCreate.set('loading', false);
				this._widgets.buttonCancel.set('disabled', false);
				
				this.hide();
			}, this);
			deferred.fail(function () {
				form.set('disabled', false);
				
				this._widgets.buttonCreate.set('loading', false);
				this._widgets.buttonCancel.set('disabled', false);
			}, this);
		},
		
		/**
		 * Show popup for editing
		 */
		'show': function () {
			if (!this._widgets.panel) {
				this._createPanel();
			}
			
			var host = this.get('host'),
				panel = this._widgets.panel;
			
			//Set form values
			this.setFormValues({
				'title': '',
				'path': ''
			});
			
			panel.fadeIn();
		},
		
		/**
		 * Hide edit panel
		 */
		'hide': function () {
			if (this._widgets.panel) {
				this._widgets.panel.hide();
			}
		}
	});
	
	Action.PluginPostAdd = Plugin;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.input', 'transition']});