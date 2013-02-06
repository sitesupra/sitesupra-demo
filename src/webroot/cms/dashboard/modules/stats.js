/**
 * Handles file upload process
 */
YUI.add('dashboard.stats', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Google analytics helper for stats
	 */
	function Stats (config) {
		Stats.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Stats.NAME = 'analytics';
	
	Stats.ATTRS = {
		// URI for requesting statistics data
		'statsRequestUri': {
			value: null
		},
		
		// URI for requesting profile list
		'profilesRequestUri': {
			value: null
		},
		
		// URI to save selected profile
		'saveRequestUri': {
			value: null
		},
		
		// Stats container node
		'srcNode': {
			value: null
		},
		
		// Loading state
		'loading': {
			value: false,
			setter: '_setLoading'
		}
	};
	
	Y.extend(Stats, Y.Base, {
		
		
		/**
		 * Widgets
		 * @type {Object}
		 * @private
		 */
		_widgets: null,
		
		/**
		 * Authorization request uri
		 * @type {String}
		 * @private
		 */
		_authorization_url: null,
		
		
		/**
		 * @constructor
		 * @private
		 */
		initializer: function () {
			this._widgets = {
				'authorizationButton': null,
				'profilesForm': null,
				'profilesInput': null,
				'profilesButton': null,
				
				'keywordsStats': null,
				'visitorsStats': null,
				'sourcesStats': null
			};
			
			this._loadStats();
			//Y.later(750, this, this.test);
		},
		
		/*
		test: function () {
			this._handleStatsData({
				'authorization_url': 'http://www.google.com',
				'is_authenticated': false,
				'profile_id': null
			});
			
			this._widgets.authorizationButton.detach('click');
			this._widgets.authorizationButton.on('click', function () {
				this.set('loading', true);
				
				Y.later(750, this, function () {
					this._renderProfilesData([
						{'id': 'test1', 'title': 'Test profile #1'},
						{'id': 'test2', 'title': 'Test profile #2'},
						{'id': 'test3', 'title': 'Test profile #3'}
					]);
					
					this._widgets.profilesButton.detach('click');
					this._widgets.profilesButton.on('click', function () {
						this.set('loading', true);
						this.set('statsRequestUri', Supra.Manager.Applications.getDataPath('dev/stats'));
						this._loadStats();
					}, this);
				})
				
			}, this);
		},
		*/
		
		
		/* --------------------------- RENDER STATISTICS DATA --------------------------- */
		
		
		/**
		 * Render statistics data
		 * 
		 * @private
		 */
		_renderStats: function (data) {
			var node_src       = this.get('srcNode'),
				node_keywords  = node_src.one('div.dashboard-keywords'),
				node_referrers = node_src.one('div.dashboard-referrers'),
				node_visitors  = node_src.one('div.dashboard-visitors'),
				node_auth      = node_src.one('.dashboard-authorization'),
				
				keywords       = null,
				referrers      = null,
				visitors       = null;
			
			keywords = this._widgets.keywordsStats = new Supra.DashboardStatsList({
				'srcNode': node_keywords
			});
			referrers = this._widgets.sourcesStats = new Supra.DashboardStatsList({
				'srcNode': node_referrers
			});
			visitors = this._widgets.visitorsStats = new Supra.DashboardStatsVisitors({
				'srcNode': node_visitors
			});
			
			this._widgets.keywordsStats.render();
			this._widgets.sourcesStats.render();
			this._widgets.visitorsStats.render();
			
			if (data.stats) {
				// Render chart with "0" as data
				if (!Y.Object.size(data.stats.visitors)) {
					data.stats.visitors = {
						'monthly': {
							'pageviews': 0, 'visits': 0, 'visitors': 0
						},
						'daily': Y.Array.map([-13, -12, -11, -10, -9, -8, -7, -6, -5, -4, -3, -2, -1, 0], function (days) {
							return {
								'date': Y.DataType.Date.reformat(new Date(Date.now() - 86400000 * days), 'raw', '%Y/%m/%d'),
								'pageviews': 0, 'visits': 0, 'visitors': 0
							};
						})
					};
				}
				
				this._widgets.keywordsStats.set('data', data.stats.keywords);
				this._widgets.sourcesStats.set('data',  data.stats.sources);
				this._widgets.visitorsStats.set('data', data.stats.visitors);
			}
			
			
			if (!node_auth.hasClass('hidden')) {
				keywords.get('boundingBox').addClass('hidden');
				referrers.get('boundingBox').addClass('hidden');
				visitors.get('boundingBox').addClass('hidden');
				
				node_auth.transition({
					'opacity': 0,
					'duration': 0.35
				}, function () {
					node_auth.addClass('hidden');
					keywords.get('boundingBox').removeClass('hidden').setStyles({'opacity': 0}).transition({'opacity': 1, 'duration': 0.35});
					referrers.get('boundingBox').removeClass('hidden').setStyles({'opacity': 0}).transition({'opacity': 1, 'duration': 0.35});
					visitors.get('boundingBox').removeClass('hidden').setStyles({'opacity': 0}).transition({'opacity': 1, 'duration': 0.35});
				});
				
				this.set('loading', false);
			} else {
				this.set('loading', false);
			}
		},
		
		
		/* --------------------------- STATISTICS DATA --------------------------- */
		
		
		/**
		 * Get statistics
		 * 
		 * @private
		 */
		_loadStats: function () {
			var uri = this.get('statsRequestUri');
			
			this.set('loading', true);
			
			Supra.io(uri)
				.done(this._handleStatsData, this)
				.fail(this._handleStatsFailure, this);
		},
		
		/**
		 * Handle statistics data loading
		 * 
		 * @param {Object} data Statistics response
		 * @private
		 */
		_handleStatsData: function (data) {
			if (data.profile_id && data.is_authenticated) {
				// Statistics data was received
				this._renderStats(data);
			} else if (!data.is_authenticated) {
				// User not authenticated, show button
				this._authorization_url = data.authorization_url;
				this._renderAuthorization(data);
			} else if (!data.profile_id) {
				// User must choose profile, show profile list
				this._loadProfilesList();
			}
		},
		
		/**
		 * Handle total failure
		 * 
		 * @private
		 */
		_handleStatsFailure: function () {
			this._deferred_stats.rejectWith(this);
			this.set('loading', false);
		},
		
		
		/* --------------------------- AUTHORIZATION --------------------------- */
		
		
		/**
		 * Render authorization button
		 * 
		 * @private
		 */
		_renderAuthorization: function () {
			var node = this.get('srcNode').one('.dashboard-authorization'),
				button = this._widgets.authorizationButton;
			
			if (!button) {
				button = this._widgets.authorizationButton = new Supra.Button({
					'srcNode': node.one('button').removeClass('hidden')
				});
				button.render();
				button.on('click', this._authorizeAccess, this);
			}
			
			node.removeClass('hidden');
			node.setStyles({
				'opacity': 0
			}).transition({
				'opacity': 1,
				'duration': 0.35
			});
			
			this.set('loading', false);
		},
		
		/**
		 * Open authorization popup
		 * 
		 * @private
		 */
		_authorizeAccess: function () {
			this.set('loading', true);
			
			var url = this._authorization_url,
				win = null;
			
			if (url) {
				
				Supra.data.set('authorizationCallback', Y.bind(this._autorizationConfirmed, this));
				
				win = window.open(url, '_blank', 'fullscreen=no,width=900,height=600,status=no,menubar=no,toolbar=no');
				window.tmp_win = win;
				
				if (!win) {
					// Popup blocked for some reason stoped this?
					this._authorizationAfter();
				} else {
					Y.later(600, this, this._authorizationAfter);
				}
				
			}
		},
		
		/**
		 * Acces was authorized by user
		 * 
		 * @private
		 */
		_autorizationConfirmed: function () {
			// Reload statistics to check if it was successful
			// and get stats if profile already exists
			this._loadStats();
		},
		
		/**
		 * 
		 * @private
		 */
		_authorizationAfter: function () {
			this.set('loading', false);
		},
		
		
		/* --------------------------- PROFILE CHOICE --------------------------- */
		
		
		/**
		 * Load statistics profile list
		 * 
		 * @private
		 */
		_loadProfilesList: function () {
			var uri = this.get('profilesRequestUri');
			
			Supra.io(uri)
				.done(this._renderProfilesData, this)
				.fail(this._handleProfilesFailure, this);
		},
		
		/**
		 * Handle request failure
		 * 
		 * @private
		 */
		_handleProfilesFailure: function () {
			// Is there anything we can do?
			this.set('loading', false);
		},
		
		/**
		 * Render profile selection
		 * 
		 * @param {Array} profiles Profile list
		 * @private
		 */
		_renderProfilesData: function (profiles) {
			var node = this.get('srcNode').one('.dashboard-authorization'),
				form = this._widgets.profilesForm,
				authorizationButton = this._widgets.authorizationButton,
				profilesButton = this._widgets.profilesButton,
				input = this._widgets.profilesInput;
			
			if (!form) {
				// Submit button
				profilesButton = this._widgets.profilesButton = new Supra.Button({
					'srcNode': node.one('form button').removeClass('hidden')
				});
				
				profilesButton.render();
				profilesButton.on('click', this._handleProfileSelection, this);
				profilesButton.hide();
				
				// Form
				form = this._widgets.profilesForm = new Supra.Form({
					'srcNode': node.one('form').removeClass('hidden')
				});
				
				form.render();
				input = this._widgets.profilesInput = form.getInput('profiles');
				input.on('valueChange', this._handleProfilesChange, this);
				
			} else {
				profilesButton.hide();
				form.show();
			}
			
			if (authorizationButton) {
				authorizationButton.hide();
			}
			
			// Set profile list
			profiles.unshift({
				'id': '',
				'title': Supra.Intl.get(['dashboard', 'authorization', 'select_profile'])
			});
			
			input.set('showEmptyValue', false);
			input.set('values', profiles);
			
			// Animate form fade-in
			if (node.hasClass('hidden')) {
				node.removeClass('hidden');
				node.setStyles({
					'opacity': 0
				}).transition({
					'opacity': 1,
					'duration': 0.35
				});
			}
			
			this.set('loading', false);
		},
		
		/**
		 * On profile change show/hide button
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_handleProfilesChange: function (event) {
			if (event.newVal) {
				this._widgets.profilesButton.show();
			} else {
				this._widgets.profilesButton.hide();
			}
		},
		
		/**
		 * Handle profile selection (button click)
		 * 
		 * @private
		 */
		_handleProfileSelection: function () {
			var input = this._widgets.profilesInput,
				profile_id = input.get('value');
			
			this._saveProfileChoice(profile_id);
		},
		
		/**
		 * Save choosen profile
		 * 
		 * @private
		 */
		_saveProfileChoice: function (profile_id) {
			this.set('loading', true);
			
			var uri = this.get('saveRequestUri');
			
			Supra.io(uri, {
				'method': 'post',
				'data': {
					'profile_id': profile_id
				}
			})
				.done(this._loadStats, this)
				.fail(this._handleProfilesFailure, this);
		},
		
		
		/* --------------------------- ATTRIBUTES --------------------------- */
		
		
		/**
		 * Loading attribute setter
		 * 
		 * @param {Boolean} loading Attribute value
		 * @returns {Boolean} New attribute value
		 */
		_setLoading: function (loading) {
			var srcNode = this.get('srcNode'),
				authorization_button = this._widgets.authorizationButton,
				input = this._widgets.profilesInput,
				profiles_button = this._widgets.profilesButton;
			
			if (loading) {
				if (authorization_button || input || profiles_button) {
					if (authorization_button) authorization_button.set('loading', true);
					if (profiles_button) profiles_button.set('loading', true);
					if (input) input.set('disabled', true);
				} else if (srcNode) {
					srcNode.addClass('loading', true);
				}
			} else {
				if (authorization_button) authorization_button.set('loading', false);
				if (profiles_button) profiles_button.set('loading', false);
				if (input) input.set('disabled', false);
				if (srcNode) srcNode.removeClass('loading', false);
			}
			
			return !!loading;
		}
		
	});
	
	Supra.DashboardStats = Stats;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.io', 'supra.deferred', 'dashboard.stats-list', 'dashboard.stats-visitors']});