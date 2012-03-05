//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.template-list', {
	path: 'pagesettings/modules/template-list.js',
	requires: ['widget', 'website.template-list-css', 'supra.template']
});
SU.addModule('website.template-list-css', {
	path: 'pagesettings/modules/template-list.css',
	type: 'css'
});
SU.addModule('website.input-keywords', {
	path: 'pagesettings/modules/input-keywords.js',
	requires: ['supra.input-proto']
});


SU('website.template-list', 'website.input-keywords', 'supra.input', 'supra.calendar', 'supra.slideshow', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	var SLIDE_ROOT = 'slideMain';
	
	
	//Add as right bar child
	Manager.getAction('LayoutRightContainer').addChildAction('PageSettings');
	
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
		 * Buttons
		 * @type {Object}
		 */
		button_delete: null,
		
		/**
		 * 
		 */
		button_template: null,
		
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
						label = SU.Intl.get(['settings', 'title_page']);
					} else {
						label = SU.Intl.get(['settings', 'title_template']);
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
					'dates': []
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
			this.page_data.scheduled_date = Y.DataType.Date.reformat(this.calendar_schedule.get('date'), 'out_date', 'in_date');
			
			//Save time
			var inp_h = this.form.getInput('schedule_hours'),
				inp_m = this.form.getInput('schedule_minutes'),
				date = new Date();
			
			date.setHours(parseInt(inp_h.getValue(), 10) || 0);
			date.setMinutes(parseInt(inp_m.getValue(), 10) || 0);
			date.setSeconds(0);
			
			this.page_data.scheduled_time = Y.DataType.Date.reformat(date, 'raw', 'out_time');
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
				this.template_list = new Supra.TemplateList({
					'srcNode': node.one('ul.template-list'),
					'requestUri': this.getActionPath() + 'templates' + Loader.EXTENSION_DATA,
					'template': this.page_data.template.id
				});
				
				this.template_list.render();
				
				this.template_list.on('change', function (e) {
					this.page_data.template = e.template;
					
					this.setFormValue('template', this.page_data);
					this.slideshow.scrollBack();
				}, this);
			} else {
				this.template_list.set('template', this.page_data.template.id);
			}
		},
		
		onSlideAdvanced: function (node, init) {
			//Update button label
			var node = this.one('div.button-created p');
			
			if (this.page_data.created_date) {
				var date = SU.Y.DataType.Date.reformat(this.page_data.created_date + ' ' + this.page_data.created_time, 'in_datetime', 'out_datetime_short');
				node.set('text', date);
			} else {
				node.set('text', Supra.Intl.get(['settings', 'advanced_unknown']));
			}
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
			if (this.page_data.global_disabled) {
				//Global_disabled is true if page has more than one localization
				var message_id = 'delete_message_all';
			} else {
				var message_id = 'delete_message';
			}
			
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['settings', message_id]),
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
			
			//Section buttons
			var buttons = this.all('a[data-target]');
			
			buttons.on('click', function (event) {
				var node = event.target.closest('a');
				if (!node.hasClass('disabled')) {
					this.slideshow.set('slide', node.getAttribute('data-target'));
				}
			}, this);
			buttons.on('keyup', function (event) {
				if (event.keyCode == 13 || event.keyCode == 39) { //Return key or arrow right
					var node = event.target.closest('a');
					this.slideshow.set('slide', node.getAttribute('data-target'));
				}
			}, this);
			
			//Normal buttons
			var buttons = this.all('button');
			
			//Back button
			this.get('backButton').on('click', this.onBackButton, this);
			
			//Control button
			this.get('controlButton').on('click', this.saveSettingsChanges, this);
			
			//Delete button
			this.button_delete = new Supra.Button({'srcNode': buttons.filter('.button-delete').item(0), 'style': 'small-red'});
			this.button_delete.render().on('click', this.deletePage, this);
			
			//Template button
			this.button_template = new Supra.Button({'srcNode': buttons.filter('.button-template').item(0), 'style': 'small-gray'});
			this.button_template.render().on('click', function () { this.slideshow.set('slide', 'slideTemplate'); }, this);
			
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
		 * Set form values
		 */
		setFormValues: function () {
			var page_data = this.page_data;
			this.form.setValues(page_data, 'id');
			
			if (this.getType() == 'page') {
				//Templates doesn't have 'redirect' or 'template'
				
				//Set redirect value
				this.form.getInput('redirect').setValue(page_data.redirect);
				
				//Set template info
				this.setFormValue('template', page_data);
				
				//Set redirect info
				this.setFormValue('redirect', page_data);
			}
			
			//Keywords
			this.form.getInput('keywords').set('keywordRequestUri', this.getDataPath('dev/suggestions'));
			
			//If user doesn't have publish rights, then disable "Schedule publish" button 
			if (!Supra.Permission.get('page', page_data.id, 'supervise_page', false)) {
				this.one('a[data-target="slideSchedule"]').addClass('disabled');
			} else {
				this.one('a[data-target="slideSchedule"]').removeClass('disabled');
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
				case 'redirect':
					//Update button label
					var data = page_data.redirect;
					
					//Redirect title
					if (data && data.href) {
						this.redirect_title.one('a.title').set('text', data.title || data.href);
					}
					
					this.redirect_title.setClass('hidden', !data || !data.href);
					
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
			//Scroll to first slide
			this.onBackButton();
			this.slideshow.set('slide', SLIDE_ROOT)
			
			//Get data
			var page_data = this.page_data,
				form_data = this.form.getValuesObject();
			
			//Remove unneeded form data for save request
			//Scheduled and created date/time are in page_data
			delete(form_data.template);
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
			post_data.template = post_data.template.id;
			
			if (this.getType() == 'page') {	//Page
				
				//If there is no redirect URL, then send empty value
				if (!post_data.redirect || (!post_data.redirect.href && !post_data.redirect.page_id)) {
					post_data.redirect = '';
				}
				
				if (page_data.has_limited_parent) {
					delete(post_data.is_limited);
				}
				
			} else { //Template
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
			
			delete(post_data.global_disabled);
			delete(post_data.type);
			delete(post_data.id);
			delete(post_data.path_prefix);
			delete(post_data.internal_html);
			delete(post_data.contents);
			delete(post_data.has_limited_parent);
			
			post_data.locale = Supra.data.get('locale');
			
			//Save data
			var url = this.getDataPath('save');
			Supra.io(url, {
				'data': post_data,
				'method': 'POST',
				'on': {
					'success': function () {
						Manager.Page.setPageData(page_data);
						
						//Update title in page content
						this.updatePageContentData(page_data);
						
						//Change page version title
						Manager.getAction('PageHeader').setVersionTitle('autosaved');
					}
				}
			}, this);
			
			this.hide();
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
					doc.all('.yui3-settings-title').set('text', page_data.title);
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
				inputs = [
					['template', form.getInput('path')],
					['template', form.getInput('active')],
					
					['template', '.button-meta'],
					['template', form.getInput('description')],
					['template', form.getInput('keywords')],
					
					['template', '.template-section'],
					['template', form.getInput('template[id]')],
					['template', form.getInput('template[img]')],
					['template', form.getInput('template[title]')],
					
					['template', '.button-redirect'],
					['page', '.template-hint']
				];
			
			for(var i=inputs.length - 1; i>=0; i--) {
				if (typeof inputs[i][1] == 'string') {
					this.one(inputs[i][1]).ancestor().setClass('hidden', inputs[i][0] == type);
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
				label_header = SU.Intl.get(['settings', 'title_page']);
				label_title = SU.Intl.get(['settings', 'page_title']);
			} else {
				label_header = SU.Intl.get(['settings', 'title_template']);
				label_title = SU.Intl.get(['settings', 'page_title_template']);
			}
			
			this.set('title', label_header)
			form.getInput('title').set('label', label_title);
			
			//Layout input
			if (type != 'template') {
				form.getInput('layout').hide();
				form.getInput('layout').set('disabled', true);
			} else {
				form.getInput('layout').show();
				form.getInput('layout').set('disabled', false);
			}
		},
		
		/**
		 * On settings block hiden re-enable content editing
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Restore disabled state as it was before PageSettings was shown
			Manager.PageContent.getContent().set('disabled', this.last_content_disabled_state);
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
			content.set('disabled', true);
		}
	});
	
});