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
		'locale': null,
		
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
	
	
	//When BannerEdit action is hidden, hide also sidebar actions
	Manager.getAction('BannerEdit').addChildAction('LinkManager');
	Manager.getAction('BannerEdit').addChildAction('Schedule');
	Manager.getAction('BannerEdit').addChildAction('MediaSidebar');
	
	
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
		 * @type {Object}
		 * @private
		 */
		button_schedule: null,
		
		/**
		 * Delete button
		 * @type {Object}
		 * @private
		 */
		button_delete: null,
		
		
		
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
				button.addClass('su-button-first').addClass('su-button-last');
				button.render();
				button.on('click', this.openLinkManager, this);
				this.button_target = button;
			
			//Schedule button
				button = new Supra.Button({
					'srcNode': this.one('fieldset.schedule button'),
					'style': 'group'
				});
				button.addClass('su-button-first').addClass('su-button-last');
				button.render();
				button.on('click', this.openScheduler, this);
				this.button_schedule = button;
			
			//On target and schedule value changes update button labels
				this.form.getInput('target').on('valueChange', this.updateLinkUI, this);
				this.form.getInput('schedule').on('valueChange', this.updateScheduleUI, this);
			
			//When link manager and schedule manager closes unset button down state
				Supra.Manager.getAction('LinkManager').on('visibleChange', function (e) {
					this.button_target.set('down', e.newVal);
				}, this);
				
				Supra.Manager.getAction('Schedule').on('visibleChange', function (e) {
					this.button_schedule.set('down', e.newVal);
				}, this);
			
			//Delete button
				button = new Supra.Button({
					'srcNode': this.one('fieldset.delete-group button'),
					'style': 'small-red'
				});
				button.on('click', this.deleteBanner, this);
				button.render();
				this.button_delete = button;
			
			//When form is disabled/enabled do the same for buttons
				this.form.on('disabledChange', function (evt) {
					this.button_target.set('disabled', evt.newVal);
					this.button_delete.set('disabled', evt.newVal);
					this.button_schedule.set('disabled', evt.newVal);
					Manager.PageToolbar.buttons.change.set('disabled', evt.newVal);
					Manager.PageButtons.buttons[this.NAME][0].set('disabled', evt.newVal);
				}, this);
		},
		
		/**
		 * Open link manager
		 * 
		 * @private
		 */
		openLinkManager: function () {
			var value = this.form.getInput('target').getValue();
			
			Supra.Manager.executeAction('LinkManager', value, {
				'mode': 'page'
			}, this.onLinkChange, this);
			
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
				'from': data.from ? Y.DataType.Date.reformat(data.from, 'out_date', 'in_date') : null,
				'to': data.to ? Y.DataType.Date.reformat(data.to, 'out_date', 'in_date') : null
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
					'item': item,
					
					//Display type, 0 - all
					'displayType': 0
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
				
				if (data.image.sizes) {
					//Image
					this.data.image.external_path = data.image.sizes.original.external_path;
					this.data.image.width = data.image.sizes.original.width;
					this.data.image.height = data.image.sizes.original.height;
				} else {
					//Flash
					this.data.image.external_path = '/cms/lib/supra/img/medialibrary/icon-file-swf-large.png';
					this.data.image.width = -1;
					this.data.image.height = -1;
				}
				
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
						'banner_id': banner_id,
						'locale': Supra.data.get('locale')
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
			
			//Show/hide delete button
			if (data.banner_id) {
				//Existing banner
				this.button_delete.show();
			} else {
				//New banner
				this.button_delete.hide();
			}
			
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
				from = Y.DataType.Date.reformat(data.from, 'in_date', 'out_date');
				to = Y.DataType.Date.reformat(data.to, 'in_date', 'out_date');
				
				title = SU.Intl.get(['edit', 'schedule_from']) + from + SU.Intl.get(['edit', 'schedule_to']) + to;
			} else {
				title = SU.Intl.get(['edit', 'schedule_set']);
			}
			
			this.button_schedule.set('label', title);
		},
		
		/**
		 * Save banner data
		 */
		save: function (callback, context) {
			var data = Supra.mix({},
				//Data which was loaded
				this.data,
				//Values using 'name' as key and for save
				this.form.getValues('name', true),
				//Locale
				{'locale': Supra.data.get('locale')}
			);
			
			var uri = data.banner_id ? this.getDataPath('save') : this.getDataPath('insert');
			
			//Add loading indicator to button
			var button = Manager.getAction('PageButtons').buttons[this.NAME][0];
			button.set('loading', true);
			
			//Disable form
			this.form.set('disabled', true);
			
			//Don't send stats
			delete(data.stats);
			
			//Need to send only ID
			data.image = data.image.id;
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'complete': function (data, status) {
						if (status) {
							Manager.getAction('BannerList').load();
							Manager.executeAction('BannerList');
						}
						
						//Enable form form
						this.form.set('disabled', false);
						
						//Remove loading indicator
						button.set('loading', false);
						
						//Callback
						if (Y.Lang.isFunction(callback)) {
							callback.apply(context || this, arguments);
						}
					}
				}
			});
		},
		
		/**
		 * Delete banner
		 */
		deleteBanner: function () {
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['edit', 'delete_message']),
				'useMask': true,
				'buttons': [
					{
						'id': 'delete',
						'label': Supra.Intl.get(['buttons', 'yes']),
						'click': this.deleteConfirmed,
						'context': this
					},
					{
						'id': 'no',
						'label': Supra.Intl.get(['buttons', 'no'])
					}
				]
			});
		},
		
		/**
		 * When user confirms banner delete close banner edit
		 * and send request to server if this is not new banner
		 * 
		 * @private
		 */
		deleteConfirmed: function () {
			if (!this.data.banner_id) {
				//New banner delete should cancel editing
				this.openBannerList(false);
				return;
			}
			
			//Set loading indicator
			this.button_delete.set('loading', true);
			
			//Disable form and buttons
			this.form.set('disabled', true);
			
			Supra.io(this.getDataPath('delete'), {
				'method': 'post',
				'data': {
					'banner_id': this.data.banner_id,
					'locale': Supra.data.get('locale')
				},
				'context': this,
				'on': {
					'success': this.openBannerList,
					'complete': function () {
						this.form.set('disabled', false);
						this.button_delete.set('loading', false);
					}
				}
			});
		},
		
		/**
		 * On hide save all data
		 */
		hide: function () {
			if (this.get('visible')) {
				this.save(function (data, status) {
					if (status) {
						this.set('visible', false);
					}
				});
			}
			
			return this;
		},
		
		/**
		 * Open banner list without saving current banner
		 */
		openBannerList: function (reload) {
			if (this.get('visible')) {
				this.set('visible', false);
				
				//Banner list needs to be reloaded only if banner was deleted
				if (reload !== false) Manager.getAction('BannerList').load();
				
				Manager.executeAction('BannerList');
			}
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