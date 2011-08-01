//Invoke strict mode
"use strict";

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
SU('supra.form', function (Y) {
	
	var DEFAULT_CONFIG = {
		'message': '',
		'useMask': true,
		'buttons': []
	};
	
	//Shortcut
	var Action = SU.Manager.Action;
	
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
			
			this.one('p').set('innerHTML', Y.Lang.escapeHTML(message));
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
				
				if ('click' in buttons[i]) {
					button.on('click', buttons[i].click, buttons[i].context || null);
				}
			}
		},
		
		execute: function (config) {
			this.config = SU.mix({}, DEFAULT_CONFIG || {}, config);
			
			this.renderMessage(config);
			this.renderButtons(this.config);
			 
			this.panel.set('useMask', config.useMask);
			
			//Show in the middle of the screen
			this.panel.set('zIndex', 100);
			this.panel.centered();
		}
	});
	
});