/**
 * Confirmation dialog
 * 
 * @example
 * 		//Simple string
 * 		Supra.Manager.executeAction('Notification', 'Information is successfully saved!');
 * 
 * 		//Localized string
 * 		Supra.Manager.executeAction('Notification', '{# page.notification_saved #}');
 * 
 * 		//Set duration and don't escape string
 * 		Supra.Manager.executeAction('Notification', {
 * 			'message': 'Success <b>story</b>!',
 * 			'duration': 2000,						// 2000ms == 2s
 * 			'escape': false							// don't escape text
 * 		});
 */
Supra('anim', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var DEFAULT_CONFIG = {
		'message': '',
		'escape': true,
		'duration': 3000
	};
	
	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Notification',
		
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
		 * Message queue
		 * @type {Array}
		 * @private
		 */
		queue: [],
		
		/**
		 * Show/hide animations
		 * @type {Object}
		 * @private
		 */
		anim_show: null,
		anim_hide: null,
		
		
		
		/**
		 * On render bind listeners to prevent 'click' event propagation
		 */
		render: function () {
			
		},
		
		/**
		 * Show next mesasge
		 */
		showNextMessage: function () {
			//Remove old item
			var old = this.queue.shift();
			
			if (this.queue.length) {
				var anim = new Y.Anim({
					'node': old.node,
					'duration': 0.25,
					'to': { 'marginTop': -30 }
				});
				anim.on('end', function () { this.node.remove(); }, old);
				anim.run();
				
				Y.later(this.queue[0].duration, this, this.showNextMessage);
			} else {
				this.hide();
			}
		},
		
		/**
		 * Render message
		 * 
		 * @param {Object} config
		 * @private
		 */
		addMessage: function (config) {
			var message = config.message || '';
			if (message) {
				//Replace all constants with internationalized strings
				message = Supra.Intl.replace(message);
			}
			if (config.escape) {
				//Escape
				message = Y.Escape.html(message);
			}
			
			if (!this.queue.length) {
				//Remove old P
				var old_p = this.one('p');
				if (old_p) old_p.remove();
				
				//Schedule next
				Y.later(config.duration, this, this.showNextMessage);
			}
			
			//If hide animation is running then stop it
			if (this.anim_hide && this.anim_hide.get('running')) {
				//Stop "hide" animation
				this.show(true);
			}
			
			var p = config.node = Y.Node.create('<p>' + message + '</p>');
			this.one().append(p);
			this.queue.push(config);
		},
		
		/**
		 * Hide action using fade-out animation
		 */
		hide: function (callback) {
			if (this.get('visible')) {
				
				if (!this.anim_hide) {
					var anim = new Y.Anim({
						'node': this.one(),
						'duration': 0.35,
						'to': {
							'opacity': 0
						},
						'from': {
							'opacity': 1
						}
					});
					
					//On animation end call original hide function
					anim.on('end', this.onHideAnimEnd, this);
					
					this.anim_hide = anim;
				}
				
				if (this.anim_show.get('running')) {
					this.anim_show.stop();
				}
				
				this.anim_hide.reset();
				this.anim_hide.run();
			}
		},
		
		/**
		 * When hide animation ends hide action and remove all P tags
		 */
		onHideAnimEnd: function () {
			Action.Base.prototype.hide.call(this);
			
			//Remove all P tags
			this.all('p').remove();
		},
		
		/**
		 * Show action using fade-in animation
		 */
		show: function (force) {
			if (!this.get('visible') || force === true) {
				this.one().setStyle('opacity', '0');
				Action.Base.prototype.show.apply(this, arguments);
				
				if (!this.anim_show) {
					var anim = new Y.Anim({
						'node': this.one(),
						'duration': 0.35,
						'to': {
							'opacity': 1
						},
						'from': {
							'opacity': 0
						}
					});
					
					this.anim_show = anim;
				}
				
				if (this.anim_hide && this.anim_hide.get('running')) {
					//Remove item which was not hidden
					this.all('p').remove();
					this.anim_hide.stop();
				}
				
				this.anim_show.reset();
				this.anim_show.run();
			}
		},
		
		execute: function (config) {
			if (typeof config == 'string') {
				config = {'message': config};
			}
			config = Supra.mix({}, DEFAULT_CONFIG, config || {});
			
			this.addMessage(config);
			
			this.show();
		}
	});
	
});