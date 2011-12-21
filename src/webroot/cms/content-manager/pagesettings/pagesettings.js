//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.template-list', {
	path: 'pagesettings/modules/template-list.js',
	requires: ['widget', 'website.template-list-css']
});
SU.addModule('website.template-list-css', {
	path: 'pagesettings/modules/template-list.css',
	type: 'css'
});

/*
SU.addModule('website.version-list', {
	path: 'pagesettings/modules/version-list.js',
	requires: ['widget', 'website.version-list-css']
});
SU.addModule('website.version-list-css', {
	path: 'pagesettings/modules/version-list.css',
	type: 'css'
});
*/


SU('website.template-list', /*'website.version-list',*/ 'supra.input', 'supra.calendar', 'supra.slideshow', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Calendar dates
	var DEFAULT_DATES = [
		{
			'date': Y.DataType.Date.reformat(new Date(), 'raw', 'in_date'),
			'title': Supra.Intl.get(['settings', 'select_today'])
		},
		{
			'date': Y.DataType.Date.reformat(new Date(+new Date() + 86400000), 'raw', 'in_date'),
			'title': Supra.Intl.get(['settings', 'select_tomorrow'])
		}
	];
	
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
		button_cancel: null,
		button_back: null,
		button_delete: null,
		
		/**
		 * Redirect button
		 * @type {Object}
		 */
		button_redirect: null,
		
		/**
		 * Template list object
		 * @type {Object}
		 */
		template_list: null,
		
		/**
		 * Version list object
		 * @type {Object}
		 */
		/*
		version_list: null,
		*/
		
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
			
			if (evt.newVal == 'slideMain') {
				//this.button_cancel.show();
				this.button_back.hide();
				this.one('div.yui3-sidebar-buttons').addClass('hidden');
				this.one('div.yui3-sidebar-content').removeClass('has-buttons');
			} else {
				//this.button_cancel.hide();
				this.button_back.show();
				this.one('div.yui3-sidebar-buttons').removeClass('hidden');
				this.one('div.yui3-sidebar-content').addClass('has-buttons');
			}
			
			//Call "onSlide..." callback function
			var new_item = (slide_id ? Y.one('#' + slide_id) : null),
				fn = slide_id ? 'on' + (slide_id.substr(0,1).toUpperCase() + slide_id.substr(1)) : null;
			
			if (fn && fn in this) {
				this[fn](new_item, !!(fn in this.called));
				this.called[fn] = true;
			}
		},
		
		/**
		 * When schedule slide is shown create widget, bind listeners
		 */
		onSlideSchedule: function (node) {
			var date = this.page_data.scheduled_date;
			
			//Create calendar if it doesn't exist
			if (!this.calendar_schedule) {
				//Create calendar
				var calendar = this.calendar_schedule = new Supra.Calendar({
					'srcNode': node.one('.calendar'),
					'date': date,
					'dates': DEFAULT_DATES
				});
				calendar.render();
				
				//Create apply button
				var btn = new Supra.Button({srcNode: node.one('button')});
				btn.render();
				btn.on('click', this.onSlideScheduleApply, this);
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
		 * On "slideSchedule" slide Apply button click save calendar values
		 */
		onSlideScheduleApply: function () {
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
			this.slideshow.scrollBack();
		},
		
		/**
		 * When created slide is shown create widget, bind listeners
		 */
		onSlideCreated: function (node) {
			var date = this.page_data.created_date;
			
			//Create calendar if it doesn't exist
			if (!this.calendar_created) {
				//Create calendar
				var calendar = this.calendar_created = new Supra.Calendar({
					'srcNode': node.one('.calendar'),
					'date': date,
					'dates': DEFAULT_DATES
				});
				calendar.render();
				
				//Create apply button
				var btn = new Supra.Button({srcNode: node.one('button')});
				btn.render();
				btn.on('click', this.onSlideCreatedApply, this);
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
		 * On "slideCreated" slide Apply button click save calendar values
		 */
		onSlideCreatedApply: function () {
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
		
		/**
		 * When version slide is shown create widget, bind listeners
		 */
		/*
		onSlideVersion: function (node) {
			if (!this.version_list) {
				this.version_list = new Supra.VersionList({
					'srcNode': node.one('div.version-list'),
					'requestUri': this.getActionPath() + 'versions' + Loader.EXTENSION_DATA
				});
				
				this.version_list.render();
				
				this.version_list.on('change', function (e) {
					this.page_data.version = e.version;
					this.setFormValue('version', this.page_data);
					this.slideshow.scrollBack();
				}, this);
			}
		},
		*/
		
		onSlideAdvanced: function (node, init) {
			//Update button label
			var node = this.one('div.button-created p');
			
			if (this.page_data.created_date) {
				var date = SU.Y.DataType.Date.reformat(this.page_data.created_date + ' ' + this.page_data.created_time, 'in_datetime', 'out_datetime_short');
				node.set('text', Supra.Intl.get(['settings', 'advanced_create']) + date);
			} else {
				node.set('text', Supra.Intl.get(['settings', 'advanced_unknown']));
			}
		},
	
		/**
		 * Open link manager for redirect
		 */
		openLinkManager: function () {
			this.set('toolbarButtonsFrozen', true);
			
			var value = this.form.getInput('redirect').getValue();
			
			if (value && value.resource == "relative") {
				value = null;
			}
			
			var callback = Y.bind(this.onLinkManagerClose, this);
			
			//Disable editing for everything else
			Supra.Manager.PageContent.getContent().set('disabled', true);
			
			//Open link manager
			Supra.Manager.executeAction('PageLinkManager', value, {
				'callback': callback,
				'hideToolbar': true
			});
		},
		
		/**
		 * Update input value on change
		 *
		 * @param {Object} data
		 */
		onLinkManagerClose: function (data) {
			this.set('toolbarButtonsFrozen', false);
			
			//Re-enable editing
			Supra.Manager.PageContent.getContent().set('disabled', false);
			
			this.form.getInput('redirect').setValue(data);
			this.setFormValue('redirect', {'redirect': data});
			this.execute(true);
		},
		
		/**
		 * Delete page
		 */
		deletePage: function () {
			if (!Supra.Authorization.isAllowed(['page', 'delete'], true)){
				return false;
			}
			
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['settings', 'delete_message']),
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
			Supra.Manager.PageButtons.buttons[this.NAME][0].set('disabled', true);
			
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
			Supra.Manager.PageButtons.buttons[this.NAME][0].set('disabled', false);
			
			if (success) {
				//Hide page settings
				this.hide();
			}
		},
		
		/**
		 * Create form
		 */
		createForm: function () {
			
			var buttons = this.all('button');
			
			//Close button
			/*
			this.button_cancel = new Supra.Button({'srcNode': buttons.filter('.button-cancel').item(0)});
			this.button_cancel.render().on('click', this.cancelSettingsChanges, this);
			*/
			
			//Back button
			this.button_back = new Supra.Button({'srcNode': buttons.filter('.button-back').item(0)});
			this.button_back.render().hide().on('click', function () { this.slideshow.scrollBack(); }, this);
			
			//Delete button
			this.button_delete = new Supra.Button({'srcNode': buttons.filter('.button-delete').item(0), 'style': 'mid-red'});
			this.button_delete.render().on('click', this.deletePage, this);
				
			if (!Supra.Authorization.isAllowed(['page', 'delete'], true)) {
				this.button_delete.hide();
			}
			
			//Meta button
			var button_meta = new Supra.Button({'srcNode': buttons.filter('.button-meta').item(0)});
			button_meta.render().on('click', function () { this.slideshow.set('slide', 'slideMeta'); }, this);
			
			//Version button
			/*
			var button_version = new Supra.Button({'srcNode': buttons.filter('.button-version').item(0), 'style': 'large'});
			button_version.render().on('click', function () { this.slideshow.set('slide', 'slideVersion'); }, this);
			*/
			
			//Template button
			var button_template = new Supra.Button({'srcNode': buttons.filter('.button-template').item(0), 'style': 'template'});
			button_template.render().on('click', function () { this.slideshow.set('slide', 'slideTemplate'); }, this);
			
			//Schedule button
			var button_schedule = new Supra.Button({'srcNode': buttons.filter('.button-schedule').item(0)});
			button_schedule.render().on('click', function () { this.slideshow.set('slide', 'slideSchedule'); }, this);
			
			//Redirect button
			//this.button_redirect = new Supra.Button({'srcNode': buttons.filter('.button-redirect').item(0)});
			//this.button_redirect.render().on('click', function () { this.openLinkManager(); }, this);
			this.button_redirect = new Supra.Button({'srcNode': buttons.filter('.button-redirect').item(0)});
			this.button_redirect.render().on('click', function () { this.slideshow.set('slide', 'slideRedirect'); }, this);	
			
			// Redirect select list
			this.redirect_select = new Supra.Input.SelectList({'srcNode': this.one('#redirect_type')});
			this.redirect_select.render();
				// Redirect "Off" button
				this.redirect_select.buttons.off.on('click', function () { this.onRedirectClick(); }, this);
				// Redirect "Relative" button
				this.redirect_select.buttons.relative.on('click', function () {	this.onRedirectClick(); }, this);
				// Redirect "Fixed" button
				this.redirect_select.buttons.fixed.on('click', function () { this.onRedirectClick(); }, this);
			
			// Relative redirect select list
			this.relative_redirect_select = new Supra.Input.SelectList({'srcNode': this.one('#relative_redirect')});
			this.relative_redirect_select.render();
				// Redirect -> Relative "First child" button
				this.relative_redirect_select.buttons.first.on('click', function() { this.onRelativeRedirectClick(); }, this);
				// Redirect -> Relative "Last child" button
				this.relative_redirect_select.buttons.last.on('click', function() { this.onRelativeRedirectClick(); }, this);
			
			//Advanced settings button
			var button_advanced = new Supra.Button({'srcNode': buttons.filter('.button-advanced').item(0)});
			button_advanced.render().on('click', function () { this.slideshow.set('slide', 'slideAdvanced'); }, this);
				
			//Created settings button
			var button_settings = new Supra.Button({'srcNode': buttons.filter('.button-created').item(0)});
			button_settings.render().on('click', function () { this.slideshow.set('slide', 'slideCreated'); }, this);
				
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
			form.on('disabledChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					this.button_back.set('disabled', evt.newVal);
					this.button_delete.set('disabled', evt.newVal);
					this.button_redirect.set('disabled', evt.newVal);
					button_meta.set('disabled', evt.newVal);
					//button_version.set('disabled', evt.newVal);
					button_template.set('disabled', evt.newVal);
					button_schedule.set('disabled', evt.newVal);
					button_advanced.set('disabled', evt.newVal);
					button_settings.set('disabled', evt.newVal);
				}
			}, this);
			
			//When layout position/size changes update slide
			Manager.LayoutRightContainer.layout.on('sync', this.slideshow.syncUI, this.slideshow);
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
					this.slideshow.scrollTo('slideMain');
					//this.setFormValue('redirect', {'redirect': null});
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
			var label = 'Rel: ' + value + ' child';
			this.redirect_select.buttons.relative.set('label', label);

			var redirect = {
				'href': value,
				'resource': "relative",
				'page_id': this.page_data['id'],
				'title': (value == "first" ? 'First child' : 'Last child')
			};
			
			this.form.getInput('redirect').setValue(redirect);
			this.setFormValue('redirect', {'redirect': redirect});
			this.page_data.redirect = redirect;

			this.slideshow.scrollTo('slideMain');
		},
		
		/**
		 * Set form values
		 */
		setFormValues: function () {
			var page_data = this.page_data;
			this.form.setValues(page_data, 'id');
			
			//Set version info
			/*
			this.setFormValue('version', page_data);
			*/
			
			//Set redirect value
			this.form.getInput('redirect').setValue(page_data.redirect);
			
			//Set template info
			this.setFormValue('template', page_data);
			
			//Set redirect info
			this.setFormValue('redirect', page_data);
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
					var node = this.one('.button-template small');
					node.one('span').set('text', page_data.template.title);
					node.one('img').set('src', page_data.template.img);
					break;
				case 'redirect':
					//Update button label
					var data = page_data.redirect;
					var title = (data && data.href ? SU.Intl.get(['settings', 'redirect_to']) + data.title || data.href : SU.Intl.get(['settings', 'redirect']));
					this.button_redirect.set('label', title);
					
					// Trying to set correct titles for redirect selects buttons 
					// or reset them to defaults if redirect is empty
					if (data && data.href) {
						if (data.resource == "relative") {
							var label = 'Rel: ' + data.title;
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
				/*case 'version':
					var node = this.one('.button-version small');
					node.one('b').set('text', page_data.version.title);
					node.one('span').set('text', page_data.version.author + ', ' + page_data.version.date);
					break;*/
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
			var page_data = this.page_data,
				form_data = this.form.getValuesObject();
			
			//Remove unneeded form data for save request
			delete(form_data.template);
			delete(form_data.schedule_hours);
			delete(form_data.schedule_minutes);
			delete(form_data.created_hours);
			delete(form_data.created_minutes);
			
			form_data.scheduled_time = page_data.scheduled_time;
			form_data.scheduled_date = page_data.scheduled_date;
			
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
				
			} else { //Template
				//Remove template, path, meta, status
				delete(post_data.template);
				delete(post_data.path);
				delete(post_data.path_prefix);
				delete(post_data.description);
				delete(post_data.keywords);
				delete(post_data.active);
				delete(post_data.redirect);
			}
			
			delete(post_data.type);
			delete(post_data.id);
			delete(post_data.path_prefix);
			delete(post_data.internal_html);
			delete(post_data.contents);
			
			post_data.locale = Supra.data.get('locale');
			
			//Save data
			var url = this.getDataPath('save');
			Supra.io(url, {
				'data': post_data,
				'method': 'POST',
				'on': {
					'success': function () {
						Manager.Page.setPageData(page_data);
					}
				}
			}, this);
			
			this.hide();
		},
		
		/**
		 * CancelSave changes
		 */
		cancelSettingsChanges: function () {
			this.hide();
		},
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': function () {
					this.saveSettingsChanges();
				}
			}]);
			
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
					
					['template', '.button-template'],
					['template', form.getInput('template[id]')],
					['template', form.getInput('template[img]')],
					['template', form.getInput('template[title]')],
					
					['template', '.button-redirect'],
					['page', '.template-hint']
				];
			
			for(var i=inputs.length - 1; i>=0; i--) {
				if (typeof inputs[i][1] == 'string') {
					if (inputs[i][0] == type) {
						this.one(inputs[i][1]).ancestor().addClass('hidden');
					} else {
						this.one(inputs[i][1]).ancestor().removeClass('hidden');
					}
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
			
			this.one('h2.yui3-sidebar-header').set('text', label_header);
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