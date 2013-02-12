//Invoke strict mode
YUI.add('dashboard.stats-summary', function (Y) {
	'use strict';
	
	/**
	 * Statistics module
	 */
	function Summary (config) {
		Summary.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Summary.NAME = 'summary';
	Summary.CSS_PREFIX = 'su-' + Summary.NAME;
	Summary.CLASS_NAME = Y.ClassNameManager.getClassName(Summary.NAME);
 
	Summary.ATTRS = {
		//Data
		'data': {
			'value': null,
			'setter': 'renderData'
		}
	};
	
	Y.extend(Summary, Y.Widget, {
		
		_nodes: {
			'profileId': null
		},
		
		// Widgets
		_widgets: {
			'profilesButton': null,
			'unauthorizeButton': null
		},
		
		//Data
		data: null,
		
 
		/**
		 * Create/add nodes, render widgets
		 *
		 * @private
		 */
		renderUI: function () {
			Summary.superclass.renderUI.apply(this, arguments);
			
			this._widgets = {
				'profilesButton': null,
				'unauthorizeButton': null
			};
			this._nodes = {
				'profileId': null
			};
			
			if (!this.get('visible')) {
				this.get('boundingBox').addClass("hidden");
			}
			
			if (this.get('data')) {
				this.renderData(this.get('data'));
			}
		},
		
		
		/**
		 * ---------------------------- LIST -------------------------
		 */
		
		
		/**
		 * Render data
		 * 
		 * @param {Object} data Profile data
		 */
		renderData: function (data) {
			if (data) {
				var profiles_button = this._widgets.profilesButton,
					unauthorize_button = this._widgets.unauthorizeButton,
					node_info = this._nodes.profileId,
					node = this.get('contentBox'),
					title = (data.profile_title || '') + (data.ua_code ? ' (' + data.ua_code + ')' : ''),
					info = Supra.Intl.get(['dashboard', 'settings', 'profile']).replace('%s', title);
				
				if (!profiles_button) {
					this._nodes.profileId = node_info = Y.Node.create('<p class="profile-info"></p>');
					node_info.set('text', info);
					node.append(node_info);
					
					this._widgets.profilesButton = profiles_button = new Supra.Button({
						'label': Supra.Intl.get(['dashboard', 'settings', 'change_website']),
						'style': 'small'
					});
					profiles_button.render(node);
					profiles_button.addClass(profiles_button.getClassName('fill'));
					profiles_button.on('click', this._fireProfilesEvent, this);
					
					this._widgets.unauthorizeButton = unauthorize_button = new Supra.Button({
						'label': Supra.Intl.get(['dashboard', 'settings', 'remove_analytics']),
						'style': 'small-red'
					});
					unauthorize_button.render(node);
					unauthorize_button.addClass(unauthorize_button.getClassName('fill'));
					unauthorize_button.on('click', this._fireUnauthorizeEvent, this);
				} else {
					node_info.set('text', info);
				}
				
				profiles_button.set('loading', false);
				profiles_button.set('disabled', false);
				
				unauthorize_button.set('loading', false);
				unauthorize_button.set('disabled', false);
			}
			
			return data;
		},
		
		
		/**
		 * ---------------------------- SETTINGS -------------------------
		 */
		
		
		/**
		 * Fire profile button click event
		 * 
		 * @private
		 */
		_fireProfilesEvent: function () {
			this._widgets.profilesButton.set('loading', true);
			this._widgets.unauthorizeButton.set('disabled', true);
			this.fire('profilesListClick');
		},
		
		/**
		 * Fire unauthorize button click event
		 * 
		 * @private
		 */
		_fireUnauthorizeEvent: function () {
			this._widgets.unauthorizeButton.set('loading', true);
			this._widgets.profilesButton.set('disabled', true);
			this.fire('unauthorizeClick');
		},
 
 
		/**
		 * ---------------------------- ATTRIBUTES -------------------------
		 */
 
 
		/**
		 * Visibility attribute setter
		 * 
		 * @param {Boolean} visible
		 * @private
		 */
		_uiSetVisible: function (visible) {
			if (!this.get('rendered')) return !!visible;
			var node = this.get('boundingBox'),
				hidden = node.hasClass('hidden');
			
			if (visible && hidden) {
				node.setStyles({'opacity': 0})
					.removeClass('hidden')
					.transition({'opacity': 1, 'duration': 0.35});
			} else if (!visible && !hidden) {
				node.transition({'opacity': 0, 'duration': 0.35}, Y.bind(function () {
					node.addClass('hidden');
				}, this));
			}
			
			return !!visible;
		}
	});
 
	Supra.DashboardStatsSummary = Summary;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:['widget']});