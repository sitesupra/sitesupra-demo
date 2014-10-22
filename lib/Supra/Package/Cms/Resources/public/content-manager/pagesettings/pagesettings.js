//Add module definitions
Supra.addModule('website.template-list', {
	path: 'pagesettings/modules/template-list.js',
	requires: ['widget', 'website.template-list-css', 'supra.template']
});
Supra.addModule('website.template-list-css', {
	path: 'pagesettings/modules/template-list.css',
	type: 'css'
});


Supra('website.template-list', 'supra.input', 'supra.calendar', 'supra.slideshow', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;

	var SLIDE_ROOT = 'slideMain';


	//Create Action class
	new Action(Action.PluginLayoutSidebar, {

		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageSettings',

		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,

		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,

		/**
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		LAYOUT_CONTAINER: 'LayoutRightContainer',




		/**
		 * Form instance
		 * @type {Object}
		 */
		form: null,

		/**
		 * Slideshow instance
		 * @type {Object}
		 */
		slideshow: null,
		
		/**
		 * Main slide buttons
		 * @type {Object}
		 */
		buttons: {},

		/**
		 * Delete button
		 * @type {Object}
		 */
		button_delete: null,

		/**
		 * Template section button
		 * @type {Object}
		 */
		button_template: null,

		/**
		 * Layout section button
		 * @type {Object}
		 */
		button_layout: null,

		/**
		 * "Created" button
		 * @type {Object}
		 */
		button_created: null,

		/**
		 * Template list object
		 * @type {Object}
		 */
		template_list: null,
		
		/**
		 * Layout list object
		 * @type {Object}
		 */
		layout_list: null,

		/**
		 * Page data
		 * @type {Object}
		 */
		page_data: {},

		/**
		 * Slides which onSlide... function has been called
		 * @type {Object}
		 */
		called: {},

		/**
		 * Disabled state of page content before settings was shown
		 * @type {Boolean}
		 */
		last_content_disabled_state: false,
		
		/**
		 * Highlight mode of page content before settings was shown
		 * @type {String}
		 */
		last_content_highlight_state: 'edit',
		
		/**
		 * Update path when title changes
		 * @type {Boolean}
		 */
		auto_update_path_from_title: false,



		/**
		 * On slide change show/hide buttons and call callback function
		 * 
		 * @param {Object} evt
		 */
		onSlideChange: function (evt) {
			var slide_id = evt.newVal;

			if (evt.newVal == SLIDE_ROOT) {
				this.get('backButton').hide();
			} else {
				this.get('backButton').show();
			}

			//Call "onSlide..." callback function
			var new_item = (slide_id ? Y.one('#' + slide_id) : null),
				fn = slide_id ? 'on' + (slide_id.substr(0,1).toUpperCase() + slide_id.substr(1)) : null;

			if (fn && fn in this) {
				this[fn](new_item, !!(fn in this.called));
				this.called[fn] = true;
			}

			//Update header title and icon
			if (new_item) {
				var label = '';

				if (evt.newVal == SLIDE_ROOT) {
					if (this.getType() != 'template') {
						label = Supra.Intl.get(['settings', 'title_page']);
					} else {
						label = Supra.Intl.get(['settings', 'title_template']);
					}
				} else {
					label = new_item.getAttribute('data-title');
				}

				this.set('title', label);
				this.set('icon', new_item.getAttribute('data-icon'));
			}
		},

		/**
		 * When schedule slide is shown create widget, bind listeners
		 */
		onSlideSchedule: function (node) {
			var date = Y.DataType.Date.reformat(this.page_data.scheduled_date, 'in_date', 'raw');

			//Create calendar if it doesn't exist
			if (!this.calendar_schedule) {
				//Create calendar
				var calendar = this.calendar_schedule = new Supra.Calendar({
					'srcNode': node.one('.calendar'),
					'date': date,
					'dates': [],
					'minDate': new Date()
				});
				calendar.render();

				//Create "Clear" button
				var btn = new Supra.Button({srcNode: node.one('button')});
				btn.render();
				btn.on('click', this.onSlideScheduleClear, this);
			} else {
				//Set date
				this.calendar_schedule.set('date', date);
				this.calendar_schedule.set('displayDate', date);
			}

			//Set time
			if (this.page_data.scheduled_time) {
				var time = Y.DataType.Date.reformat(this.page_data.scheduled_time, 'in_time', 'raw'),
					hours = (time ? time.getHours() : 0),
					minutes = (time ? time.getMinutes() : 0);
			} else {
				var hours = 0,
					minutes = 0;
			}

			this.form.getInput('schedule_hours').set('value', hours < 10 ? '0' + hours : hours);
			this.form.getInput('schedule_minutes').set('value', minutes < 10 ? '0' + minutes : minutes);
		},

		/**
		 * On "slideSchedule" slide close save calendar values
		 */
		onSlideScheduleClose: function () {
			//Save date
			var date = Y.DataType.Date.reformat(this.calendar_schedule.get('date'), 'out_date', 'raw'),
				inp_h = this.form.getInput('schedule_hours'),
				inp_m = this.form.getInput('schedule_minutes'),
				min = this.calendar_schedule.get('minDate');
			
			date.setHours(parseInt(inp_h.getValue(), 10) || 0);
			date.setMinutes(parseInt(inp_m.getValue(), 10) || 0);
			date.setSeconds(0);
			
			if (min.getTime() >= date.getTime()) {
				// Date is in the past, remove schedule
				this.page_data.scheduled_date = "";
				this.page_data.scheduled_time = "";
			} else {
				this.page_data.scheduled_date = Y.DataType.Date.reformat(date, 'raw', 'in_date');
				this.page_data.scheduled_time = Y.DataType.Date.reformat(date, 'raw', 'out_time');
			}			
		},

		/**
		 * On "Clear all" button click reset date and time values
		 */
		onSlideScheduleClear: function () {
			//Save date and time
			this.calendar_schedule.set('date', new Date());
			this.calendar_schedule.set('displayDate', new Date());
			this.page_data.scheduled_date = '';
			this.page_data.scheduled_time = '';

			this.slideshow.scrollBack();
		},

		/**
		 * When created slide is shown create widget, bind listeners
		 */
		onSlideCreated: function (node) {
			var date = Y.DataType.Date.reformat(this.page_data.created_date, 'in_date', 'raw');

			//Create calendar if it doesn't exist
			if (!this.calendar_created) {
				//Create calendar
				var calendar = this.calendar_created = new Supra.Calendar({
					'srcNode': node.one('.calendar'),
					'date': date,
					'dates': []
				});
				calendar.render();

				//Create "Clear" button
				var btn = new Supra.Button({srcNode: node.one('button')});
				btn.render();
				btn.on('click', this.onSlideCreatedClear, this);
			} else {
				//Set date
				this.calendar_created.set('date', date);
				this.calendar_created.set('displayDate', date);
			}

			//Set time
			if (this.page_data.created_time) {
				var time = Y.DataType.Date.reformat(this.page_data.created_time, 'in_time', 'raw'),
					hours = (time ? time.getHours() : 0),
					minutes = (time ? time.getMinutes() : 0);
			} else {
				var hours = 0,
					minutes = 0;
			}

			this.form.getInput('created_hours').set('value', hours < 10 ? '0' + hours : hours);
			this.form.getInput('created_minutes').set('value', minutes < 10 ? '0' + minutes : minutes);
		},

		/**
		 * On "slideSchedule" slide close save calendar values
		 */
		onSlideCreatedClose: function () {
			//Save date
			this.page_data.created_date = Y.DataType.Date.reformat(this.calendar_created.get('date'), 'out_date', 'in_date');

			//Save time
			var inp_h = this.form.getInput('created_hours'),
				inp_m = this.form.getInput('created_minutes'),
				date = new Date();

			date.setHours(parseInt(inp_h.getValue(), 10) || 0);
			date.setMinutes(parseInt(inp_m.getValue(), 10) || 0);
			date.setSeconds(0);

			this.page_data.created_time = Y.DataType.Date.reformat(date, 'raw', 'out_time');
		},

		/**
		 * On "Clear all" button click reset date and time values
		 */
		onSlideCreatedClear: function () {
			//Save date and time
			this.calendar_created.set('date', new Date());
			this.calendar_created.set('displayDate', new Date());
			this.page_data.created_date = '';
			this.page_data.created_time = '';

			this.slideshow.scrollBack();
		},


		/**
		 * When "slideTemplate" slide is shown create widget, bind listeners
		 */
		onSlideTemplate: function (node) {
			if (!this.template_list) {
				this.template_list = new Supra.Input.SelectList({
					'value': this.page_data.template.id,
					'style': 'items'
				});
				
				this.template_list.render('div.template-list');
				this.template_list.set('loading', true);
				this.loadTemplateList();
				
				this.template_list.on('change', function (e) {
					var data = this.template_list.getValueData(e.value);
					if (data) {
						this.page_data.template = data;
						
						this.setFormValue('template', this.page_data);
						this.slideshow.scrollBack();
						this.saveSettingsChanges(); // save immediatelly to show template change
					}
				}, this);
			} else {
				this.template_list.set('value', this.page_data.template.id);
			}
		},
		
		/**
		 * When "slideLayout" slide is shown create widget, bind listeners
		 */
		onSlideLayout: function (node) {
			if (!this.layout_list) {
				var layouts = this.page_data.layouts,
					select_layout_title = Supra.Intl.get(['settings', 'use_parent_layout']);
				
				layouts.unshift({
					'id': '',
					'title': select_layout_title,
					'icon': '/public/cms/supra/img/sitemap/preview/layout.png'
				});
				
				this.layout_list = new Supra.Input.SelectList({
					'value': this.page_data.layout.id,
					'values': layouts,
					'style': 'items'
				});
				
				this.layout_list.render('div.layout-list');
				
				this.layout_list.on('change', function (e) {
					var data = this.layout_list.getValueData(e.value);
					if (data && (!this.page_data.layout || this.page_data.layout.id)) {
						this.page_data.layout = data;
						
						this.setFormValue('layout', this.page_data);
						this.slideshow.scrollBack();
						this.saveSettingsChanges(); // save immediatelly to show layout change
					}
				}, this);
			} else {
				this.layout_list.set('value', this.page_data.layout.id);
			}
		},

		onSlideAdvanced: function (node, init) {
			//Update button label
			var node = this.one('div.button-created p');

			if (this.page_data.created_date) {
				var date = Supra.Y.DataType.Date.reformat(this.page_data.created_date + ' ' + this.page_data.created_time, 'in_datetime', 'out_datetime_short');
				node.set('text', date);
			} else {
				node.set('text', Supra.Intl.get(['settings', 'advanced_unknown']));
			}
		},
		
		/**
		 * Load template list
		 * 
		 * @private
		 */
		loadTemplateList: function () {
			var uri = this.getDataPath('templates');
			
			Supra.io(uri).always(function (templates) {
				this.template_list.set('values', templates || []);
				this.template_list.set('loading', false);
			}, this);
		},

		/**
		 * Open link manager for redirect
		 */
		openLinkManager: function () {
			var value = this.form.getInput('redirect').getValue();

			if (value && value.resource == "relative") {
				value = null;
			}

			//Open link manager
			Supra.Manager.executeAction('LinkManager', value, {
				'mode': 'page',
				//Open in slide instead of LinkManager action
				'container': this.slideshow.getSlide('slideRedirectFixed').one('.su-slide-content')
			});

			this.slideshow.set('slide', 'slideRedirectFixed');
		},

		/**
		 * Update data on link manager close
		 */
		onSlideRedirectFixedClose: function () {
			//Update data
			var data = Manager.LinkManager.getData();

			this.form.getInput('redirect').setValue(data);
			this.setFormValue('redirect', {'redirect': data});

			//Close link manager to keep visible state correct
			Supra.Manager.getAction('LinkManager').hide();
		},

		/**
		 * Delete page
		 */
		deletePage: function () {
			if (this.page_data.localization_count > 1) {
				var message_id = 'delete_message_all';
			} else {
				var message_id = 'delete_message';
			}

			var current_type = (this.page_data.type == 'template' ? 'template' : 'page');
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['settings', message_id]).replace('{label}', current_type),
				'useMask': true,
				'buttons': [
					{
						'id': 'delete',
						'label': Supra.Intl.get(['buttons', 'yes']),
						'click': this.deletePageConfirmed,
						'context': this
					},
					{
						'id': 'no',
						'label': Supra.Intl.get(['buttons', 'no'])
					}
				]
			});

			return true;
		},

		/**
		 * Page delete is confirmed, now actually delete page 
		 */
		deletePageConfirmed: function () {
			//Disable form
			this.form.set('disabled', true);
			this.button_delete.set('loading', true);
			this.get('controlButton').set('disabled', true);

			//Delete page
			Manager.Page.deleteCurrentPage(this.afterDeletePage, this);
		},

		/**
		 * After page delete enable buttons
		 */
		afterDeletePage: function (data, success) {
			//Enable form
			this.form.set('disabled', false);
			this.button_delete.set('loading', false);
			this.get('controlButton').set('disabled', false);

			if (success) {
				//Hide page settings
				this.hide();
			}
		},

		/**
		 * Create form
		 */
		createForm: function () {

			//Buttons
			var buttons = this.all('button');

			//Back button
			this.get('backButton').on('click', this.onBackButton, this);

			//Control button
			this.get('controlButton').on('click', this.onDoneButton, this);
			
			//Buttons
			buttons.filter('[data-target]').each(function (node) {
				var button = new Supra.Button({'srcNode': node}),
					target = node.getAttribute('data-target');
				
				button.render();
				button.on('click', function () {
					this.slideshow.set('slide', target);
				}, this);
				
				this.buttons[target] = button;
			}, this);

			//Delete button
			this.button_delete = new Supra.Button({'srcNode': buttons.filter('.button-delete').item(0), 'style': 'small-red'});
			this.button_delete.render().on('click', this.deletePage, this);

			//Template button
			this.button_template = new Supra.Button({'srcNode': buttons.filter('.button-template').item(0), 'style': 'small-gray'});
			this.button_template.render().on('click', function () { this.slideshow.set('slide', 'slideTemplate'); }, this);
			
			//Template button
			this.button_layout = new Supra.Button({'srcNode': buttons.filter('.button-layout').item(0), 'style': 'small-gray'});
			this.button_layout.render().on('click', function () { this.slideshow.set('slide', 'slideLayout'); }, this);

			// Redirect section button value
			this.redirect_title = this.one('span.redirect-target');
			this.redirect_title.one('.remove').on('click', this.removeRedirect, this);

			//Created button
			this.button_created = new Supra.Button({'srcNode': buttons.filter('.button-created').item(0), 'style': 'small-gray'});
			this.button_created.render().on('click', function () { this.slideshow.set('slide', 'slideCreated'); }, this);
			
			//Slideshow
			var slideshow = this.slideshow = new Supra.Slideshow({
				'srcNode': this.one('div.slideshow')
			});
			slideshow.render();
			slideshow.on('slideChange', this.onSlideChange, this);

			//Form
			var form = this.form = new Supra.Form({
				'srcNode': this.one('form')
			});
			form.render();

			//When form is disabled/enabled take care of buttons
			form.on('disabledChange', this.onFormDisableChange, this);

			// Redirect select lists
			this.redirect_select = form.getInput('redirect_type');
				// Redirect "Off" button
				this.redirect_select.buttons.off.on('click', function () { this.onRedirectClick(); }, this);
				// Redirect "Relative" button
				this.redirect_select.buttons.relative.on('click', function () {	this.onRedirectClick(); }, this);
				// Redirect "Fixed" button
				this.redirect_select.buttons.fixed.on('click', function () { this.onRedirectClick(); }, this);
				this.redirect_select.buttons.fixed.addClass('page-settings-button-fixed');

			// Relative redirect select list
			this.relative_redirect_select = form.getInput('relative_redirect');
				// Redirect -> Relative "First child" button
				this.relative_redirect_select.buttons.first.on('click', function() { this.onRelativeRedirectClick(); }, this);
				// Redirect -> Relative "Last child" button
				this.relative_redirect_select.buttons.last.on('click', function() { this.onRelativeRedirectClick(); }, this);
			
			// Global or local page check-box
			this.global_checkbox = form.getInput('global');
				this.global_checkbox.render();
				this.global_checkbox.on('valueChange', function (evt) {
					if (evt.newVal != evt.prevVal) {
						var page = Manager.Page.getPageData();
						Manager.getAction('PageHeader').setAvailableLocalizations(page.localizations, evt.newVal);
					}
				});
				
				if (!Supra.data.get('languageFeaturesEnabled')) {
					this.global_checkbox.hide();
				}
			
			// When title changes path may need to be updated
			form.getInput('title').on('input', this.onTitleInput, this);
			form.getInput('title').after('valueChange',  this.onTitleChange, this);
		},
		
		/**
		 * Check if page path will need to be update
		 * 
		 * @private
		 */
		onTitleChange: function () {
			this.onTitleInput();
			
			var value = this.form.getInput('path').get('value');
			if (value && value.indexOf('new-page') == 0) {
				this.auto_update_path_from_title = true;
			} else {
				this.auto_update_path_from_title = false;
			}
		},
		
		/**
		 * On title input event update path if needed
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		onTitleInput: function (e) {
			if (this.auto_update_path_from_title) {
				var title = e ? e.value : this.form.getInput('title').get('value'),
					path  = Y.Lang.toPath(title);
				
				this.form.getInput('path').set('value', path);
			}
		},

		/**
		 * Handle form disable state change
		 * 
		 * @param {Event} evt Event facade object
		 * @private
		 */
		onFormDisableChange: function (evt) {
			if (evt.newVal != evt.prevVal) {
				this.get('backButton').set('disabled', evt.newVal);
				this.button_delete.set('disabled', evt.newVal);
				this.button_template.set('disabled', evt.newVal);
				this.button_layout.set('disabled', evt.newVal);
				this.button_created.set('disabled', evt.newVal);
			}
		},

		/**
		 * Remove redirect
		 */
		removeRedirect: function (evt) {
			this.redirect_select.set('value', 'off');
			this.onRedirectClick();

			if (evt) evt.halt();
		},

		/**
		 * Handle redirect list button clicks
		 */
		onRedirectClick: function () {
			var value = this.redirect_select.get('value');
			switch (value) {
				case 'off':
					this.page_data.redirect = null;
					this.setFormValue('redirect', {'redirect': null});
					this.slideshow.scrollTo(SLIDE_ROOT);
					this.form.getInput('redirect').setValue(null);										
					break;
				case 'relative':
					var redirect = this.form.getInput('redirect').getValue()
					this.setFormValue('redirect', {'redirect': redirect});
					this.slideshow.set('slide', 'slideRedirectRelative');
					break;
				case 'fixed':
					this.openLinkManager();
					break;
			}

			return false;
		},

		/**
		 * Handle relative redirect list button clicks
		 */
		onRelativeRedirectClick: function() {
			var value = this.relative_redirect_select.get('value');

			var redirect = {
				'href': value,
				'resource': "relative",
				'page_id': this.page_data['id'],
				'page_master_id': this.page_data['master_id'],
				'title': (value == "first" ? 'First child' : 'Last child')
			};

			this.form.getInput('redirect').setValue(redirect);
			this.setFormValue('redirect', {'redirect': redirect});
			this.page_data.redirect = redirect;

			this.slideshow.scrollTo(SLIDE_ROOT);
		},

		/**
		 * Scroll back
		 */
		onBackButton: function () {
			//Callback
			var slide_id = this.slideshow.get('slide'),
				fn = slide_id ? 'on' + (slide_id.substr(0,1).toUpperCase() + slide_id.substr(1)) + 'Close' : null;

			if (fn && this[fn]) {
				this[fn]();
			}

			//Scroll
			this.slideshow.scrollBack();
		},
		
		/**
		 * Handle "Done" button click
		 */
		onDoneButton: function () {
			//In case of Schedule slide, done will act as Back button
			if (this.slideshow.get('slide') == 'slideSchedule') {
				this.onBackButton();
				return;
			}
			
			//Scroll to first slide
			this.onBackButton();
			this.slideshow.set('slide', SLIDE_ROOT);
			
			this.saveSettingsChanges();
			this.hide();
		},

		/**
		 * Set form values
		 */
		setFormValues: function () {
			var page_data = this.page_data,
				schedule_button = this.buttons['slideSchedule'];
			
			this.form.resetValues();
			
			this.form.setValues(page_data, 'id');
			
			if (this.getType() == 'page') {
				//Templates doesn't have 'redirect' or 'template'

				//Set redirect value
				this.form.getInput('redirect').setValue(page_data.redirect);

				//Set template info
				this.setFormValue('template', page_data);

				//Set redirect info
				this.setFormValue('redirect', page_data);
				
				//Update page path when title changes?
				this.auto_update_path_from_title = (page_data.path && page_data.path.indexOf('new-page') == 0);
			} else {
				this.auto_update_path_from_title = false;

				//Set template info
				this.setFormValue('layout', page_data);
			}

			//Keywords
			this.form.getInput('keywords')
					.set('suggestionsEnabled', Supra.data.get('keywordSuggestionEnabled'))
					.set('suggestionRequestUri', this.getDataPath('suggestions'))
			;

			//If user doesn't have publish rights, then disable "Schedule publish" button 
			if (!Supra.Permission.get('page', page_data.id, 'supervise_page', false)) {
				schedule_button.set('disabled', true);
			} else {
				schedule_button.set('disabled', false);
			}
			
		},

		/**
		 * Set form value
		 * 
		 * @param {Object} key
		 * @param {Object} value
		 */
		setFormValue: function (key, page_data) {
			switch(key) {
				case 'template':
					//Set template info
					this.button_template.set('label', page_data.template.title);
					break;
				case 'layout':
					//Set layout info
					this.button_layout.set('label', page_data.layout.title);
					break;
				case 'redirect':
					//Update button label
					var data = page_data.redirect;

					//Redirect title
					if (data && data.href) {
						this.redirect_title.one('a.title').set('text', data.title || data.href);
					}

					this.redirect_title.toggleClass('hidden', !data || !data.href);

					// Trying to set correct titles for redirect selects buttons 
					// or reset them to defaults if redirect is empty
					if (data && data.href) {
						if (data.resource == "relative") {
							var label = data.title;
							this.redirect_select.buttons.relative.set('label', label);
							this.redirect_select.buttons.fixed.set('label', 'Fixed');
							this.relative_redirect_select._setValue(data.href);
						} else {
							this.redirect_select.buttons.relative.set('label', 'Relative');
							this.redirect_select.buttons.fixed.set('label', data.title);
							this.relative_redirect_select._setValue('');
						}
					} else {
						this.redirect_select.buttons.fixed.set('label', 'Fixed');
						this.redirect_select.buttons.relative.set('label', 'Relative');
						this.relative_redirect_select._setValue('');
					}

					var redirect_value = 'off';
					if (data && data.resource) {
						switch (data.resource) {
							case 'relative':
								redirect_value = 'relative';
								break;
							case 'page': 
							case 'link':
								if (data.href) {
									redirect_value = 'fixed';
								}
								break;
						}
					}
					this.redirect_select._setValue(redirect_value);

					break;
				default:
					var obj = {};
					obj[key] = page_data[key];
					this.form.setValues(obj, 'id');
					break;
			}
		},

		/**
		 * Save changes
		 */
		saveSettingsChanges: function () {
			//Get data
			var page_data = this.page_data,
				form_data = this.form.getValuesObject(),
				template_changed = false,
				layout_changed = false;
			
			if (this.getType() == 'page') {	//Page
				if (Manager.Page.getPageData().template.id != page_data.template.id) {
					template_changed = true;
				}
			} else { //Template
				if (Manager.Page.getPageData().layout.id != page_data.layout.id) {
					layout_changed = true;
				}
			}

			//Remove unneeded form data for save request
			//Scheduled and created date/time are in page_data
			delete(form_data.template);
			delete(form_data.layout);
			delete(form_data.scheduled_time);
			delete(form_data.scheduled_date);
			delete(form_data.schedule_hours);
			delete(form_data.schedule_minutes);
			delete(form_data.created_time);
			delete(form_data.created_date);
			delete(form_data.created_hours);
			delete(form_data.created_minutes);

			Supra.mix(page_data, form_data);

			//Remove unneeded data for save request
			var post_data = Supra.mix({}, page_data);
			post_data.page_id = post_data.id;

			if (this.getType() == 'page') {	//Page

				post_data.template = post_data.template.id;

				//If there is no redirect URL, then send empty value
				if (!post_data.redirect || (!post_data.redirect.href && !post_data.redirect.page_id)) {
					post_data.redirect = '';
				}

				if (page_data.has_limited_parent) {
					delete(post_data.is_limited);
				}
				
				delete(post_data.layout);

			} else { //Template
				post_data.layout = post_data.layout.id;
				//Remove template, path, meta, status
				delete(post_data.template);
				delete(post_data.path);
				delete(post_data.path_prefix);
				delete(post_data.description);
				delete(post_data.keywords);
				delete(post_data.page_change_frequency);
				delete(post_data.page_priority);
				delete(post_data.active);
				delete(post_data.redirect);
				delete(post_data.is_limited);
			}

			delete(post_data.localization_count);
			delete(post_data.type);
			delete(post_data.id);
			delete(post_data.path_prefix);
			delete(post_data.internal_html);
			delete(post_data.contents);
			delete(post_data.has_limited_parent);
			delete(post_data.layouts);

			post_data.locale = Supra.data.get('locale');

			//Save data
			var url = (this.getType() === 'template')
					? Supra.Url.generate('cms_pages_template_settings_save')
					: Supra.Url.generate('cms_pages_page_settings_save')

 			Supra.io(url, {
				'data': post_data,
				'method': 'POST',
				'on': {
					'success': function () {
						Manager.Page.setPageData(page_data);

						if (template_changed || layout_changed) {
							//Reload page content
							Manager.Page.reloadPage();
						} else {
							//Update title in page content
							this.updatePageContentData(page_data);
						}
						
						//Change page title and version title
						Manager.getAction('PageHeader').setTitle('page', page_data.title);
						Manager.getAction('PageHeader').setVersionTitle('autosaved');
					}
				}
			}, this);
		},

		/**
		 * Update content texts
		 * 
		 * @param {Object} page_data Page data
		 * @private
		 */
		updatePageContentData: function (page_data) {
			var doc = Supra.Manager.PageContent.getContent().get('doc');
			if (doc) {
				doc = new Y.Node(doc);
				if (doc) {
					doc.all('.yui3-settings-title, .su-settings-title').set('text', page_data.title);
				}
			}
		},

		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);

			if (!this.form) this.createForm();
		},

		/**
		 * Returns type
		 *
		 * @return Type 'page' or 'template'
		 * @type {String}
		 */
		getType: function () {
			return this.page_data.type;
		},

		/**
		 * Update page/template UI, show/hide fields for template
		 */
		updateTypeUI: function () {
			var type = this.getType(),
				form = this.form,
				buttons = this.buttons,
				inputs = [
					['template', form.getInput('path')],
					['template', form.getInput('active')],

					['template', this.buttons.slideMeta],
					['template', form.getInput('description')],
					['template', form.getInput('keywords')],

					['page', '.layout-section'],
					['page', form.getInput('layout[id]')],
					['page', form.getInput('layout[img]')],
					['page', form.getInput('layout[title]')],

					['template', '.template-section'],
					['template', form.getInput('template[id]')],
					['template', form.getInput('template[img]')],
					['template', form.getInput('template[title]')],

					['template', this.buttons.slideRedirect],
					['page', '.template-hint']
				];

			for(var i=inputs.length - 1; i>=0; i--) {
				if (typeof inputs[i][1] == 'string') {
					this.one(inputs[i][1]).toggleClass('hidden', inputs[i][0] == type);
				} else {
					if (inputs[i][0] == type) {
						inputs[i][1].hide();
						inputs[i][1].set('disabled', true);
					} else {
						inputs[i][1].set('disabled', false);
						inputs[i][1].show();
					}
				}
			}

			// `is_limited` input is handled outside main loop
			var input = this.form.getInput('is_limited');
			var input_description = this.one('.isLimited-description');
			var allowLimitedToggle = Supra.data.get('allowLimitedAccessPages');
			if (allowLimitedToggle) {
				if (type == 'page') {
					input.show();
					this.one('.isLimited-description').removeClass('hidden');

					if (this.page_data.has_limited_parent) {
						input.set('disabled', true).set('value', 1);
					}
				} else {
					input.hide();
					input_description.addClass('hidden');
				}
			} else {
				input.hide();
				input_description.addClass('hidden');
			}

			//Update labels
			var label_header = '',
				label_title = '';

			if (type != 'template') {
				label_header = Supra.Intl.get(['settings', 'title_page']);
				label_title = Supra.Intl.get(['settings', 'page_title']);
			} else {
				label_header = Supra.Intl.get(['settings', 'title_template']);
				label_title = Supra.Intl.get(['settings', 'page_title_template']);
			}
			
			if (type != 'template') {
				this.button_delete.set('label', Supra.Intl.get(['settings', 'delete_page']));
			} else {
				this.button_delete.set('label', Supra.Intl.get(['settings', 'delete_template']));
			}

			this.set('title', label_header)
			form.getInput('title').set('label', label_title);

			//Layout input
			/*
			if (type == 'template') {
				form.getInput('layout').hide();
				form.getInput('layout').set('disabled', true);
			} else {
				var values = form.getInput('layout').get('values');
				if(values && values.length) {
					form.getInput('layout').set('disabled', false);
				} else {
					var select_layout_title = Supra.Intl.get(['settings', 'use_parent_layout']);
					var layouts = this.page_data.layouts;

					layouts.unshift({id:'', title: select_layout_title});

					form.getInput('layout').set('values', layouts);
					form.getInput('layout').set('value', this.page_data.layout);
				}
			}
			*/
		},

		/**
		 * On settings block hiden re-enable content editing
		 */
		hide: function () {
			if (this.get('visible')) {
				Action.Base.prototype.hide.apply(this, arguments);
	
				//Restore disabled state as it was before PageSettings was shown
				var content = Manager.PageContent.getContent();
				content.set('disabled', this.last_content_disabled_state);
				content.set('highlightMode', this.last_content_highlight_state);
			}
		},

		/**
		 * Execute action
		 */
		execute: function (dont_update_data) {
			this.show();

			if (dont_update_data !== true) {
				this.page_data = Supra.mix({}, Manager.Page.getPageData());
				this.setFormValues();
				this.updateTypeUI();
			}

			this.slideshow.set('noAnimation', true);
			this.slideshow.scrollBack();
			this.slideshow.set('noAnimation', false);

			//Disable content editing
			var content = Manager.PageContent.getContent();
			
			this.last_content_disabled_state = content.get('disabled');
			this.last_content_highlight_state = content.get('highlightMode');
			
			content.set('disabled', true);
			content.set('highlightMode', 'disabled');
		}
	});

});