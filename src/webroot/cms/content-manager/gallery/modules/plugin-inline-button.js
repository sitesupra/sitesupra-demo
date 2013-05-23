YUI.add('gallery.plugin-inline-button', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Folder rename plugin
	 * Saves item properties when they change
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'plugin-inline-button';
	Plugin.NS = 'inline';
	
	Plugin.ATTRS = {
		// Node inside which should be placed buttons
		'targetNode': {
			value: null
		},
		// Item template
		'template': {
			value: Supra.Template.compile('<a class="button">{{ title|e }}</a>')
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Last known list of button titles, nodes which were used to render buttons
		 * @type {Array}
		 * @private
		 */
		_buttons: null,
		
		/**
		 * Initialize plugin
		 * 
		 * @constructor
		 */
		initializer: function () {
			var input = this.get('host');
			
			this._buttons = [];
			
			input.after('valueChange', this._updateUI, this);
			this.after('targetNodeChange', this._updateUI, this);
			input.on('input', this._updateButtonTitle, this);
			
			this._updateUI();
		},
		
		/**
		 * End of life cycle
		 */
		destructor: function () {
			var buttons = this._buttons,
				i = 0,
				ii = buttons.length;
			
			for (; i<ii; i++) {
				buttons[i].node.remove(true);
			}
			
			this._buttons = [];
		},
		
		/**
		 * Update buttons
		 * 
		 * @private
		 */
		_updateUI: function () {
			var target = this.get('targetNode');
			if (!target) {
				this._buttons = [];
				return;
			}
			
			var buttons = this._buttons,
				values = this.get('host').get('value'),
				i = 0,
				ii = Math.max(buttons.length, values.length),
				index = 0,
				template = this.get('template');
			
			for (; i<ii; i++) {
				if (!buttons[index]) {
					// Add button
					buttons[index] = {
						'node': Y.Node.create(template(values[i])),
						'title': values[i].title
					};
					target.append(buttons[index].node);
					index++;
				} else if (!values[i]) {
					// Remove button
					buttons[index].node.remove(true);
					buttons.splice(index, 1);
				} else {
					// Update button
					if (values[i].title != buttons[index].title) {
						buttons[index].node.set('text', values[i].title);
						buttons[index].title = values[i].title;
					}
					index++;
				}
			}
		},
		
		/**
		 * On input 'input' event udpate button title
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_updateButtonTitle: function (event) {
			if (event.property === 'title') {
				var index = event.index,
					title = event.value,
					buttons = this._buttons;
				
				if (index >= 0 && index < buttons.length) {
					buttons[index].node.set('text', title);
				}
			}
		}
		
	});
	
	Supra.GalleryViewButton = Plugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'plugin', 'supra.template']});