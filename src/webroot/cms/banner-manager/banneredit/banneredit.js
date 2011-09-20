//Invoke strict mode
"use strict";


/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	var NEW_BANNER_DATA = {
		'banner_id': null,
		'group_id': null,
		
		'image': {
			'id': null,
			'path': [],
			'external_path': null,
			'widtht': 0,
			'height': 0
		},
		
		'target': {
			'resource': 'internal',
			'page_id': null,
			'title': '',
			'href': ''
		},
		
		'schedule': {
			'from': null,
			'to': null
		},
		
		'stats': {
			'exposures': 0,
			'cte': 0,
			'average_ctr': 0
		}
	};
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, Action.PluginForm, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'BannerEdit',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
		/**
		 * Banner group and banner data
		 * @type {Array}
		 * @private
		 */
		data: null,
		
		/**
		 * Target input button
		 * @type {Object}
		 * @private
		 */
		button_target: null,
		
		/**
		 * Schedule input button
		 * 
		 * @type {Object}
		 * @private
		 */
		button_schedule: null,
		
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			//Set default buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [
				{
					'id': 'change',
					'title': SU.Intl.get(['edit', 'change']),
					'icon': this.getActionPath() + 'images/icon-change.png',
					'action': 'BannerEdit',
					'actionFunction': 'showMediaSidebar',
					'type': 'button'
				}
			]);
			
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [
				{
					'id': 'done',
					'callback': this.hide,
					'context': this
				}
			]);
			
			this.addChildAction('MediaSidebar');
			
			//Target button
			var button = new Supra.Button({
				'srcNode': this.one('fieldset.target button'),
				'style': 'group'
			});
			button.addClass('yui3-button-first').addClass('yui3-button-last');
			button.render();
			button.on('click', this.openLinkManager, this);
			this.button_target = button;
			
			//Schedule button
			var button = new Supra.Button({
				'srcNode': this.one('fieldset.schedule button'),
				'style': 'group'
			});
			button.addClass('yui3-button-first').addClass('yui3-button-last');
			button.render();
			button.on('click', this.openScheduler, this);
			this.button_schedule = button;
			
			//On target value change update button label
			this.form.getInput('target').on('valueChange', this.updateLinkUI, this);
			this.form.getInput('schedule').on('valueChange', this.updateScheduleUI, this);
			
			//When link manager and schedule manager closes unset button down state
			Supra.Manager.getAction('PageLinkManager').on('visibleChange', function (e) {
				this.button_target.set('down', e.newVal);
			}, this);
			
			Supra.Manager.getAction('Schedule').on('visibleChange', function (e) {
				this.button_schedule.set('down', e.newVal);
			}, this);
		},
		
		/**
		 * Open link manager
		 * 
		 * @private
		 */
		openLinkManager: function () {
			var value = this.form.getInput('target').getValue();
			var callback = Y.bind(this.onLinkChange, this);
			Supra.Manager.executeAction('PageLinkManager', value, callback);
			
			this.button_target.set('down', true);
		},
		
		/**
		 * When link is changed using link manager update data
		 * 
		 * @param {Object} data Link data
		 * @private
		 */
		onLinkChange: function (data) {
			this.form.getInput('target').setValue(data);
		},
		
		/**
		 * Open scheduler sidebar
		 * 
		 * @private
		 */
		openScheduler: function () {
			var value = this.form.getInput('schedule').getValue();
			var callback = Y.bind(this.onScheduleChange, this);
			Supra.Manager.executeAction('Schedule', value, callback);
			
			this.button_schedule.set('down', true);
		},
		
		/**
		 * When schedule is changed using Scheduler update data
		 * 
		 * @param {Object} data Data
		 * @private
		 */
		onScheduleChange: function (data) {
			var internal_dates = {
				'from': data.from ? Y.DataType.Date.reformat(data.from, null, 'internal') : null,
				'to': data.to ? Y.DataType.Date.reformat(data.to, null, 'internal') : null
			};
			this.form.getInput('schedule').setValue(internal_dates);
		},
		
		/**
		 * Toggle media sidebar
		 */
		showMediaSidebar: function () {
			Manager.getAction('PageToolbar').buttons.change.set('down', true);
			
			var action = Manager.getAction('MediaSidebar');
			if (!action.get('visible')) {
				
				var item = null;
				if (this.data.image.id) {
					item = [].concat(this.data.image.path || []);
					
					//Add image ID
					item.push(this.data.image.id);
				}
				
				action.execute({
					//When new image is selected update data
					'onselect': Y.bind(this.onImageSelect, this),
					
					//Open current item
					'item': item
				});
				
				action.once('hide', function () {
					Manager.getAction('PageToolbar').buttons.change.set('down', false);
				});
				
			}
		},
		
		/**
		 * On image select udpate data
		 */
		onImageSelect: function (data) {
			Manager.getAction('MediaSidebar').hide();
			
			if (data.image) {
				this.data.image.id = data.image.id;
				this.data.image.path = data.image.path;
				this.data.image.external_path = data.image.sizes.original.external_path;
				this.data.image.width = data.image.sizes.original.width;
				this.data.image.height = data.image.sizes.original.height;
				
				this.updatePreview(this.data.image);
			}
		},
		
		/**
		 * Set banner
		 * 
		 * @param {String} banner_id Banner ID
		 * @param {String} group_id Group ID
		 */
		setBanner: function (banner_id /* Banner ID */, group_id /* Group ID */) {
			
			if (banner_id) {
				this.one().addClass('loading');
				
				Supra.io(this.getDataPath('load'), {
					'data': {
						'banner_id': banner_id
					},
					'context': this,
					'on': {'success': this.setData}
				});
			} else {
				var data = Supra.mix({}, NEW_BANNER_DATA, {'group_id': group_id}, true);
				this.setData(data);
			}
		},
		
		/**
		 * Set banner data
		 */
		setData: function (data, status) {
			this.data = data;
			
			//Set form data (no encoding)
			this.form.setValues(data, 'name', true);
			
			//Set stats
			var template = Supra.Template('bannerStats'),
				node = this.one('div.stats');
				
			node.set('innerHTML', template(data));
			
			//Update page target
			this.updateLinkUI(data.target);
			
			//Set schedule
			this.updateScheduleUI(data.schedule);
			
			//Set image
			this.updatePreview(data.image);
			
			//Remove loading animation
			this.one().removeClass('loading');
		},
		
		/**
		 * Update banner preview
		 */
		updatePreview: function (image) {
			var container = this.one('div.preview'),
				img = container.one('img');
			
			if (image.id) {
				img.setAttribute('src', image.external_path);
				container.removeClass('hidden');
				
				if (image.width > image.height) {
					container.removeClass('preview-vertical');
				} else {
					container.addClass('preview-vertical');
				}
			} else {
				container.addClass('hidden');
			}
		},
		
		/**
		 * After link value change update button label
		 * 
		 * @private
		 */
		updateLinkUI: function (e) {
			var data = ('newVal' in e ? e.newVal : e);
			var title = (data && data.href ? data.title || data.href : SU.Intl.get(['edit', 'target_set']));
			this.button_target.set('label', title);
		},
		
		/**
		 * After schedule value change update button label
		 * 
		 * @param {Object} e Event or data object
		 * @private
		 */
		updateScheduleUI: function (e) {
			var data = ('newVal' in e ? e.newVal : e),
				title = '',
				from = '',
				to = '';
			
			if (data.from && data.to) {
				//Change from output format to internal
				from = Y.DataType.Date.reformat(data.from, 'internal', null);
				to = Y.DataType.Date.reformat(data.to, 'internal', null);
				
				title = SU.Intl.get(['edit', 'schedule_from']) + from + SU.Intl.get(['edit', 'schedule_to']) + to;
			} else {
				title = SU.Intl.get(['edit', 'schedule_set']);
			}
			
			this.button_schedule.set('label', title);
		},
		
		/**
		 * Save banner data
		 */
		save: function () {
			var data = Supra.mix(
				{},
				//Data which was loaded
				this.data,
				//Values using 'name' as key and for save
				this.form.getValues('name', true)
			);
			
			var uri = data.banner_id ? this.getDataPath('save') : this.getDataPath('insert');
			
			//Don't send stats
			delete(data.stats);
			
			//Need to send only ID
			data.image = data.image.id;
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'on': {
					'success': function () {
						Manager.getAction('BannerList').load();
						Manager.executeAction('BannerList');
					}
				}
			});
		},
		
		/**
		 * On hide save all data
		 */
		hide: function () {
			if (this.get('visible')) {
				this.set('visible', false);
				
				this.save();
			}
			
			return this;
		},
		
		/**
		 * Execute action
		 */
		execute: function (banner_id, group_id) {
			//Change toolbar buttons
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons');
			
			if (toolbar.get('created')) {
				toolbar.setActiveAction(this.NAME);
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(this.NAME);
			}
			
			if (banner_id !== undefined) {
				//null is used for new banner
				this.setBanner(banner_id, group_id);
			}
			
			this.show();
		}
	});
	
});