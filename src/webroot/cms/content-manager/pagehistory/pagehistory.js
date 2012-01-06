//Invoke strict mode
"use strict";

SU(function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Add as right bar child
	Manager.getAction('LayoutRightContainer').addChildAction('PageHistory');
	
	//Create Action class
	new Action(Supra.Manager.Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageHistory',
		
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
		 * Page data
		 * @type {Object}
		 * @private
		 */
		history_data: {},
		
		/**
		 * List element, Y.Node instance
		 * @type {Object}
		 * @private
		 */
		element_list: null,
		
		/**
		 * Currently previewed version
		 * @type {String}
		 * @private
		 */
		current_version: null,
		
		/**
		 * "Restore" button
		 * @type {Array}
		 * @private
		 */
		button: null,
		
		/**
		 * History is loading
		 * @type {Boolean}
		 * @private
		 */
		loading: false,
		
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': this.hide
			}]);
			
			this.element_list = this.one('ul.history-list');
			this.element_list.delegate('click', this.showVersionPreview, 'li', this);
		},
		
		/**
		 * Show version preview
		 *
		 * @param {Event} e Event
		 * @private
		 */
		showVersionPreview: function (e) {
			if (this.loading) return;
			
			var target = e.target.closest('li'),
				version_id = target.getAttribute('data-id');
			
			if (this.current_version != version_id) {
				this.current_version = version_id;
				
				target.siblings().removeClass('active');
				target.addClass('loading');
				target.addClass('active');
				
				this.disableList();
				
				Manager.getAction('PageContent').getIframeHandler().showVersionPreview(version_id, function () {
					target.removeClass('loading');
					this.enableList();
				}, this);
				
				//Create button
				if (!this.button) {
					this.button = new Supra.Button({'label': Supra.Intl.get(['history', 'restore']), 'style': 'small'});
					this.button.render(target.one('span'));
					this.button.on('click', function () {
						this.restoreVersionConfirm(this.current_version);
					}, this);
				}
				
				//Move button to correct place
				target.one('span').append(this.button.get('boundingBox'));
			}
		},
		
		/**
		 * Disable list
		 */
		enableList: function () {
			this.loading = false;
			this.one('.history-list').removeClass('history-list-disabled');
		},
		
		/**
		 * Disable list
		 */
		disableList: function () {
			this.loading = true;
			this.one('.history-list').addClass('history-list-disabled');
		},
		
		/**
		 * Restore specific version and hide PageHistory block
		 *
		 * @param {String} version_id
		 */
		restoreVersion: function (version_id) {
			//Disable elements
			this.disableList();
			this.button.set('loading', true);
			Manager.PageButtons.buttons[this.NAME][0].set('disabled', true);
			
			Supra.io(this.getDataPath('restore'), {
				'method': 'post',
				'data': {
					'page_id': Manager.Page.getPageData().id,
					'version_id': version_id,
					'locale': Supra.data.get('locale')
				},
				'context': this,
				'on': {
					'success': function () {
						//Re-enable elements
						this.enableList();
						this.button.set('loading', false);
						Manager.PageButtons.buttons[this.NAME][0].set('disabled', false);
						
						//Reload page
						this.reloadPage();
						this.hide();
					}
				}
			});
		},
		
		/**
		 * Restore specific version and confirm before doing so
		 *
		 * @param {String} version_id
		 */
		restoreVersionConfirm: function (version_id) {
			
			Supra.Manager.executeAction('Confirmation', {
				'message': SU.Intl.get(['history', 'restore_message']),
				'useMask': true,
				'buttons': [{
						'id': 'yes',
						'context': this,
						'click': function () {
							this.restoreVersion(version_id);
						}
					},
					{'id': 'no'}
				]
			});
			
		},
		
		/**
		 * Restore page view, reload page data
		 *
		 * @param 
		 */
		reloadPage: function () {
			var iframe = Manager.getAction('PageContent').iframe_handler;
			var data = Manager.Page.getPageData();
			
			//When iframe will be ready
			iframe.once('ready', function () {
				//Show editable areas
				iframe.contents.set('highlight', false);
			});
			
			//Reload page data
			Manager.Page.loadPage(data.id);
			
			this.current_version = null;
		},
		
		/**
		 * Reload data
		 */
		reloadList: function () {
			
			Supra.io(this.getDataPath('load'), {
				'data': {
					'page_id': Manager.getAction('Page').getPageData().id,
					'locale': Supra.data.get('locale')
				},
				'context': this,
				'on': {
					'success': this.renderData
				}
			});
			
		},
		
		/**
		 * Draw data
		 */
		renderData: function (data, status) {
			this.element_list.set('innerHTML', Supra.Template('pageHistoryListItem', {'items': data}));
			this.element_list.removeClass('loading');
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			//Restore original content
			if (this.current_version) {
				this.reloadPage();
			}
			
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Unset active and loading, so that next time PageHistory is shown
			//there wouldn't be any items with selected or loading styles
			this.loading = false;
			this.element_list.all('.loading, .active').removeClass('loading').removeClass('active')
			
			//Hide buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Hide action
			Manager.getAction('LayoutRightContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			//Unset version
			this.current_version = null;
			
			//Show buttons
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			//Show content
			Manager.getAction('LayoutRightContainer').setActiveAction(this.NAME);
			
			this.reloadList();
		}
	});
	
});