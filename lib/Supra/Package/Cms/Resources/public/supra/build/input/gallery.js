YUI.add('supra.input-gallery', function (Y) {
	//Invoke strict mode
	'use strict';
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-gallery';
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		// Image node which is edited
		'targetNode': {
			value: null
		},
		// Default value
		'defaultValue': {
			value: ''
		}
		
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		/**
		 * We don't need input, so we unset template
		 */
		INPUT_TEMPLATE: null,
		
		/**
		 * Label template, but since we don't want to render label we unset it
		 */
		LABEL_TEMPLATE: null,
		
		/**
		 * Render needed widgets
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var renderTarget = this.get('contentBox'),
				buttonManage;
			
			// Button 'Manage ...'
			buttonManage = new Supra.Button({
				'label': Supra.Intl.get(['form', 'block', 'gallery_manager']) + this.get('label'),
				'style': 'mid-blue'
			});
			buttonManage.on('click', this.openGalleryManager, this);
			buttonManage.render(renderTarget);
			buttonManage.addClass('su-button-fill');
			
			this.widgets = {
				'buttonManage': buttonManage
			};
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var targetNode = this.get('targetNode');
			if (targetNode) {
				targetNode.on('click', this.openGalleryManager, this);
			}
		},
		
		/**
		 * Clean up
		 * 
		 * @private
		 */
		destructor: function () {
			var widgets = this.widgets,
				key;
			
			for (key in widgets) widgets[key].destroy(true);
			
			this.widgets = null;
		},
		
		
		/* ------------------------- Gallery manager ------------------------ */
		
		
		/**
		 * Open gallery manager
		 */
		openGalleryManager: function () { 
			Supra.Manager.executeAction('ItemManager', {
				'host': this,
				'contentElement': this.get('targetNode'),
				
				'template': this.getGalleryItemTemplate(),
				'properties': this.getGalleryItemProperties(),
				
				'callback': Y.bind(this.onGalleryManagerClose, this)
			});
		},
		
		/**
		 * Returns template for gallery items while rendered in Gallery manager
		 *
		 * @returns {String} Gallery item template
		 */
		getGalleryItemTemplate: function () {
			var targetNode = this.get('targetNode');
			return targetNode.getAttribute('data-prototype') || '<li>{{ image }}<h4>{{ title }}</h4></li>';
		},
		
		/**
		 * Returns list of properties for gallery items
		 *
		 * @returns {Array} Gallery item properties
		 */
		getGalleryItemProperties: function () {
			// Properties are hardcoded for now
			return [
				{
					'id': 'image',
					'name': 'image',
					'type': 'InlineImage',
					'label': 'Image'
				}, {
					'id': 'title',
					'name': 'title',
					'type': 'InlineString',
					'label': 'Title'
				}
			];
		},
		
		/**
		 * Gallery managaer closed
		 * 
		 * @protected
		 */
		onGalleryManagerClose: function (data) {
			// @TODO
			this.set('value', data);
		},
		
		
		/* -------------------------- Image edit ---------------------------- */
		
		
		/**
		 * Start image editing
		 */
		startEditing: function () {
			// @TODO Do we need to do anything here?
			return true;
		},
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value Value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_setValue: function (value) {
			return value;
		},
		
		/**
		 * Value attribute getter
		 * Returns input value
		 * 
		 * @return {Object}
		 * @private
		 */
		_getValue: function (value) {
			return value;
		},
		
		/**
		 * Returns value for saving
		 * 
		 * @return {Object}
		 * @private
		 */
		_getSaveValue: function () {
			//return Y.DataType.Image.format(this.get('value'));
			return this.get('value');
		}
		
	});
	
	Supra.Input.Gallery = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
