/**
 * Confirmation dialog
 * 
 * @example
 * 		Supra.Manager.executeAction('Confirmation', {
 * 			'message': 'Are you sure?',
 * 			'buttons': [
 * 				{'id': 'yes', 'click': function () { alert('Yes'); }, 'context': this},
 * 				{'id': 'no', 'label': 'No, some other time'},
 * 			]
 * 		});
 */
Supra('supra.input', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var KEY_ESCAPE = 27,
		KEY_RETURN = 13;
	
	var DEFAULT_CONFIG = {
		'message': '',
		'escape': false,
		'useMask': true,
		'align': 'center',
		'buttons': []
	};
	
	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Confirmation',
		
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
		 * On render bind listeners to prevent 'click' event propagation
		 */
		render: function () {
			
			var panel = this.panel;
			
			panel.get('boundingBox').on('click', this.stopPropagation);
			panel.get('boundingBox').on('keydown', this.handleKeyDown, this);
			
			//When clicking on mask prevent propagation
			panel.once('maskNodeChange', function (event) {
				//Panel changed to use mask
				if (event.newVal) {
					event.newVal.on('click', this.stopPropagation);
				}
			}, this);
		},
		
		stopPropagation: function (event) {
			event.stopPropagation();
		},
		
		/**
		 * Render message
		 * 
		 * @param {Object} config
		 * @private
		 */
		renderMessage: function (config) {
			var message = config.message || '';
			if (message) {
				//Replace all constants with internationalized strings
				message = Supra.Intl.replace(message);
			}
			if (config.escape) {
				message = Y.Escape.html(message);
			}
			
			this.one('p')
					.removeClass('align-left')
					.removeClass('align-right')
					.removeClass('align-center')
					.addClass('align-' + config.align)
					.set('innerHTML', message);
		},
		
		/**
		 * Create buttons
		 * 
		 * @param {Object} config
		 * @private
		 */
		renderButtons: function (config) {
			var footer = this.getPluginWidgets('PluginFooter', true)[0],
				buttons = footer.getButtons(),
				button = null;
			
			//Remove old buttons
			for(var id in buttons) footer.removeButton(id);
			
			//Add new buttons
			buttons = config.buttons;
			for(var i=0,ii=buttons.length; i<ii; i++) {
				footer.addButton(buttons[i]);
				button = footer.getButton(buttons[i].id);
				button.on('click', this.hide, this);
				
				if (i == 0) {
					//Focus first button
					button.focus();
				}
				if ('click' in buttons[i]) {
					button.on('click', buttons[i].click, buttons[i].context || null, buttons[i].args);
				}
			}
		},
		
		handleKeyDown: function (event) {
			var close = false,
				search = null;
			
			if (event.keyCode == KEY_RETURN) {
				// Confirm and hide
				search = ['save', 'delete', 'ok', 'apply', 'yes', 'done'];
				close = true;
			} else if (event.keyCode == KEY_ESCAPE) {
				// Cancel and hide
				search = ['cancel', 'no'];
				close = true;
			}
			
			if (close) {
				var footer = this.getPluginWidgets('PluginFooter', true)[0],
					buttons = buttons = this.config.buttons,
					button = null;
				
				if (buttons.length == 1) {
					button = buttons[0];
				} else {
					for (var i=0,ii=buttons.length; i<ii; i++) {
						if (Y.Array.indexOf(search, buttons[i].id) !== -1) {
							button = buttons[i]; break;
						}
					}
				}
				
				if (button) {
					if (button.click) {
						button.click.call(button.context || this, event, button.args);
					}
					this.hide();
				}
			}
		},
		
		execute: function (config) {
			this.config = config = Supra.mix({}, DEFAULT_CONFIG, config || {});
			
			this.renderMessage(config);
			this.renderButtons(config);
			 
			this.panel.set('useMask', config.useMask);
			
			//Show in the middle of the screen
			this.panel.set('zIndex', 105);
			this.panel.centered();
		}
	});
	
});