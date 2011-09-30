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
	new Action({
		
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
		 */
		called: {},
		
		
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
			var time = Y.DataType.Date.reformat(this.page_data.scheduled_time, 'in_time', 'raw'),
				hours = (time ? time.getHours() : 0),
				minutes = (time ? time.getMinutes() : 0);
			
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
			var time = Y.DataType.Date.reformat(this.page_data.created_time, 'in_time', 'raw'),
				hours = (time ? time.getHours() : 0),
				minutes = (time ? time.getMinutes() : 0);
			
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
		
		/**
		 * Render all block list
		 */
		onSlideBlocks: function () {
			var blocks = Manager.PageContent.getContent().getAllChildren(),
				block = null,
				block_type = null,
				block_definition = null,
				container = this.one('ul.block-list'),
				item = null;
			
			container.all('li').remove();
			
			for(var id in blocks) {
				//If not locked and is not list
				if (!blocks[id].isLocked() && !blocks[id].isInstanceOf('page-content-list')) {
					block = blocks[id];
					block_definition = block.getBlockInfo();
					
					item = Y.Node.create('<li class="clearfix"><div><img src="' + block_definition.icon + '" alt="" /></div><p>' + Y.Escape.html(block_definition.title) + '</p></li>');
					item.setData('content_id', id);
					
					container.append(item);
				}
			}
			
			var li = container.all('li');
			li.on('mouseenter', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getData('content_id');
					
				blocks[content_id].set('highlightOverlay', true);
			});
			li.on('mouseleave', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getData('content_id');
				
				blocks[content_id].set('highlightOverlay', false);
			});
			li.on('click', function (evt) {
				this.hide();
				
				var target = evt.target.closest('LI'),
					content_id = target.getData('content_id'),
					contents = null;
				
				//Start editing content
				contents = Manager.PageContent.getContent();
				contents.set('activeChild', blocks[content_id]);
				
				//Show properties form
				if (blocks[content_id].properties) {
					blocks[content_id].properties.showPropertiesForm();
				}
			}, this);
		},
		
		onSlideAdvanced: function (node, init) {
			//Update label
			var date = SU.Y.DataType.Date.reformat(this.page_data.created_date + ' ' + this.page_data.created_time, 'in_datetime', 'out_datetime_short');
			this.one('#slideAdvancedCreated').set('text', date || Supra.Intl.get(['settings', 'advanced_unknown']));
			
			//
			if (init) {
				this.one('#slideAdvancedChange').on('click', function () {
					this.slideshow.set('slide', 'slideCreated');
				}, this);
			}
		},
		
		/**
		 * Open link manager for redirect
		 */
		openLinkManager: function () {
			var value = this.form.getInput('redirect').getValue();
			var callback = Y.bind(this.onLinkManagerClose, this);
			Supra.Manager.executeAction('PageLinkManager', value, callback);
		},
		
		/**
		 * Update input value on change
		 *
		 * @param {Object} data
		 */
		onLinkManagerClose: function (data) {
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
						'click': function () { Manager.Page.deleteCurrentPage(); this.hide(); },
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
			var button_delete = new Supra.Button({'srcNode': buttons.filter('.button-delete').item(0), 'style': 'mid-red'});
				button_delete.render().on('click', this.deletePage, this);
				
			if (!Supra.Authorization.isAllowed(['page', 'delete'], true)) {
				button_delete.hide();
			}
			
			//Meta button
			(new Supra.Button({'srcNode': buttons.filter('.button-meta').item(0)}))
				.render().on('click', function () { this.slideshow.set('slide', 'slideMeta'); }, this);
			
			//Version button
			/*
			(new Supra.Button({'srcNode': buttons.filter('.button-version').item(0), 'style': 'large'}))
				.render().on('click', function () { this.slideshow.set('slide', 'slideVersion'); }, this);
			*/
			
			//Template button
			(new Supra.Button({'srcNode': buttons.filter('.button-template').item(0), 'style': 'template'}))
				.render().on('click', function () { this.slideshow.set('slide', 'slideTemplate'); }, this);
			
			//Schedule button
			(new Supra.Button({'srcNode': buttons.filter('.button-schedule').item(0)}))
				.render().on('click', function () { this.slideshow.set('slide', 'slideSchedule'); }, this);
			
			//Redirect button
			this.button_redirect = (new Supra.Button({'srcNode': buttons.filter('.button-redirect').item(0)}));
			this.button_redirect
				.render().on('click', function () { this.openLinkManager(); }, this);
			
			//Blocks button
			(new Supra.Button({'srcNode': buttons.filter('.button-blocks').item(0)}))
				.render().on('click', function () { this.slideshow.set('slide', 'slideBlocks'); }, this);
			
			//Advanced settings button
			(new Supra.Button({'srcNode': buttons.filter('.button-advanced').item(0)}))
				.render().on('click', function () { this.slideshow.set('slide', 'slideAdvanced'); }, this);
				
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
			
			//Disable inputs if form is not editable
			if (!Supra.Authorization.isAllowed(['page', 'edit'], true)) {
				var inputs = form.getInputs();
				for(var id in inputs) inputs[id].set('disabled', true);
			}
			
			//When layout position/size changes update slide
			Manager.LayoutRightContainer.layout.on('sync', this.slideshow.syncUI, this.slideshow);
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
			
			this.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					if (evt.newVal) {
						this.one().removeClass('hidden');
					} else {
						this.slideshow.set('noAnimation', true);
						this.slideshow.scrollBack();
						this.slideshow.set('noAnimation', false);
						
						this.one().addClass('hidden');
					}
				}
			}, this);
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
					
					['template', '.button-redirect']
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
			if (type != 'template' || !this.page_data.root) {
				form.getInput('layout').hide();
				form.getInput('layout').set('disabled', true);
			} else {
				form.getInput('layout').show();
				form.getInput('layout').set('disabled', false);
			}
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Hide buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Hide action
			Manager.getAction('LayoutRightContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function (dont_update_data) {
			//Show buttons
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			//Show content
			Manager.getAction('LayoutRightContainer').setActiveAction(this.NAME);
			
			if (!this.form) this.createForm();
			if (dont_update_data !== true) {
				this.page_data = Supra.mix({}, Manager.Page.getPageData());
				this.setFormValues();
				this.updateTypeUI();
			}
			
			this.slideshow.set('noAnimation', true);
			this.slideshow.scrollBack();
			this.slideshow.set('noAnimation', false);
		}
	});
	
});