YUI.add('supra.crud-plugin-edit', function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	/**
	 * Crud record editing
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = "edit";
	Plugin.NS = "edit";
	
	Plugin.ATTRS = {
		'formNode': {
			value: null
		},
		'toolbarNode': {
			value: null
		},
		'configuration': {
			value: null
		}
	};
	
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			var host = this.get('host'),
				configuration = host.get('configuration');
			
			this.widgets = {};
			
			if (configuration) {
				this._ready({'newVal': configuration});
			} else {
				host.once('configurationChange', this._ready, this);
			}
		},
		
		/**
		 * Teardown plugin
		 */
		destructor: function () {
			var widgets = this.widgets,
				key;
			
			// Destroy all widgets
			for (key in widgets) {
				if (widgets[key].destroy) {
					widgets[key].destroy();
				}
			}
			
			this.widgets = null;
		},
		
		
		/* ------------------------------ API ------------------------------ */
		
		
		/**
		 * Show form with specific record data
		 *
		 * @param {String|Null} recordId Record ID
		 * @param {Object|Null} values Form values
		 */
		open: function (recordId, values) {
			this.widgets.form.resetValues();
			
			if (values) {
				this.widgets.form.setValuesObject(values, 'id');
			}
			
			this.has_changes = false;
		},
		
		/**
		 * Close form
		 * If there are changes show confirmation window whether user wants
		 * to save them
		 */
		close: function () {
			// Hide media sidebar, etc.
			this._closeSubManagers();
			
			if (this.has_changes) {
				this.has_changes = false;
				
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['crud', 'edit', 'confirmation']),
					'useMask': true,
					'buttons': [
						{
							'id': 'yes',
							'label': Supra.Intl.get(['buttons', 'yes']),
							'click': this.save,
							'context': this
						},
						{
							'id': 'no',
							'label': Supra.Intl.get(['buttons', 'no']),
							'click': this.close,
							'context': this
						}
					]
				}); 
			} else {
				this.fire('close');
			}
		},
		
		/**
		 * Save and close form
		 */
		save: function () {
			var button_save = this.widgets.button_save,
				button_back = this.widgets.button_back,
				form = this.widgets.form;
			
			form.save(function (data, status) {
				if (status) {
					this.has_changes = false;
					this.fire('save');
					this.fire('close');
				}
				
				form.set('disabled', false);
				button_back.set('disabled', false);
				button_save.set('loading', false);
				
			}, this);
			
			form.set('disabled', true);
			button_back.set('disabled', true);
			button_save.set('loading', true);
			
			// Hide media sidebar, etc.
			this._closeSubManagers();
		},
		
		
		/* ----------------------------- Events ----------------------------- */
		
		
		_closeSubManagers: function () {
			Supra.Manager.getAction('MediaSidebar').hide();
		},
		
		_handleInputChange: function () {
			this.has_changes = true;
		},
		
		
		/* ----------------------------- Render ----------------------------- */
		
		
		/**
		 * Configuration has been loaded and UI can be set up
		 *
		 * @private
		 */
		_ready: function (e) {
			// Render
			var host     = this.get('host'),
				config   = e.newVal,
				view;
			
			this.set('configuration', config);
			this._renderToolbar();
			this._renderForm();
		},
		
		_renderToolbar: function () {
			var button_save,
				button_back,
				container = this.get('toolbarNode');
			
			button_back = new Supra.Button({
				'srcNode': container.one('button[data-action="back"]')
			});
			
			button_save = new Supra.Button({
				'srcNode': container.one('button[data-action="save"]')
			});
			
			button_back.render();
			button_save.render();
			
			
			this.widgets.button_save = button_save;
			this.widgets.button_back = button_back;
			
			button_back.on('click', this.close, this);
			button_save.on('click', this.save, this);
		},
		
		/**
		 * Render form
		 */
		_renderForm: function () {
			var form,
				scrollable,
				container = this.get('formNode'),
				config = this.get('configuration'),
				fields = config.getConfigFields('ui_edit.fields'),
				i = 0,
				ii = fields.length,
				input;
			
			scrollable = new Supra.Scrollable({
				'srcNode': container
			});
			
			form = new Supra.Form({
				'urlSave': config.getDataPath('save'),
				'srcNode': container,
				'inputs': fields,
				'style': 'vertical'
			});
			
			scrollable.render();
			form.render();
			
			this.widgets.scrollable = scrollable;
			this.widgets.form = form;
			
			// Bind to input change events
			for (; i<ii; i++) {
				input = form.getInput(fields[i].id);
				input.on('change', this._handleInputChange, this);
			}
		},
		
	});
	
	(Supra.Crud || (Supra.Crud = {})).PluginEdit = Plugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: [
	'plugin',
	'supra.template',
	'supra.form'
]});
