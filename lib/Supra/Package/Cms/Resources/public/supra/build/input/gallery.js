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
			var data = [{
				'image': {
					'image': {"id":"018f8hl3t004kgsgw40s","filename":"background-9.jpg","type":2,"created":"2015-01-08T16:34:17+02:00","modified":"2015-01-08T16:34:17+02:00","size":171646,"sizes":{"30x30cropped":{"id":"30x30cropped","width":30,"height":30,"external_path":"\/files\/Images\/_size\/30x30\/background-9.jpg"},"200x200":{"id":"200x200","width":200,"height":125,"external_path":"\/files\/Images\/_size\/200x125\/background-9.jpg"},"original":{"id":"original","width":1920,"height":1200,"external_path":"\/files\/Images\/background-9.jpg"}},"file_web_path":"\/files\/Images\/background-9.jpg","exists":true,"path":[0,"018f8hh2e00cowsosco0"],"preview":"\/files\/Images\/_size\/200x125\/background-9.jpg","thumbnail":"\/files\/Images\/_size\/30x30\/background-9.jpg","broken":false,"timestamp":1420727657,"metaData":[]},
					'crop_left': 100, 'crop_top': 100,
					'crop_width': 276, 'crop_height': 276,
					'size_width': 640, 'size_height': 400
				},
				'title': 'Lorem'
			}, {
				'image': {
					'image': {"id":"018f8hkm600ggkw84ocs","filename":"background-7.jpg","type":2,"created":"2015-01-08T16:34:17+02:00","modified":"2015-01-08T16:34:17+02:00","size":150680,"sizes":{"30x30cropped":{"id":"30x30cropped","width":30,"height":30,"external_path":"\/files\/Images\/_size\/30x30\/background-7.jpg"},"200x200":{"id":"200x200","width":200,"height":125,"external_path":"\/files\/Images\/_size\/200x125\/background-7.jpg"},"original":{"id":"original","width":1920,"height":1200,"external_path":"\/files\/Images\/background-7.jpg"}},"file_web_path":"\/files\/Images\/background-7.jpg","exists":true,"path":[0,"018f8hh2e00cowsosco0"],"preview":"\/files\/Images\/_size\/200x125\/background-7.jpg","thumbnail":"\/files\/Images\/_size\/30x30\/background-7.jpg","broken":false,"timestamp":1420727657,"metaData":[]},
					'crop_left': 200, 'crop_top': 100,
					'crop_width': 276, 'crop_height': 276,
					'size_width': 640, 'size_height': 400
				},
				'title': 'Ipsum'
			}];
			
			Supra.Manager.executeAction('ItemManager', {
				'host': this,
				'contentElement': this.get('targetNode'),
				
				'itemTemplate': this.getGalleryItemTemplate(),
				'wrapperTemplate': this.getGalleryWrapperTemplate(),
				'properties': this.getGalleryItemProperties(),
				
				'callback': Y.bind(this.onGalleryManagerClose, this),
				
				'data': data //this.get('value')
			});
		},
		
		/**
		 * Returns template for gallery items while rendered in Gallery manager
		 *
		 * @returns {String} Gallery item template
		 */
		getGalleryItemTemplate: function () {
			var targetNode = this.get('targetNode');
			return targetNode.getAttribute('data-item-template');
		},
		
		/**
		 * Returns template for gallery wrapper while rendered in Gallery manager
		 *
		 * @returns {String} Gallery wrapper template
		 */
		getGalleryWrapperTemplate: function () {
			var targetNode = this.get('targetNode');
			return targetNode.getAttribute('data-wrapper-template');
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
				}, {
					'id': 'description',
					'name': 'description',
					'type': 'InlineText',
					'label': 'Description'
				}
			];
		},
		
		/**
		 * Gallery managaer closed
		 * 
		 * @param {Array} data
		 * @protected
		 */
		onGalleryManagerClose: function (data) {
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
			var value = Supra.mix([], this.get('value'), true), // deep clone
				i = 0,
				ii = value.length;
			
			// Extract images
			for (; i < ii; i++) {
				if (value[i].image) {
					value[i] = Y.DataType.Image.format(value[i]);
				}
			}
			
			return value;
		}
		
	});
	
	Supra.Input.Gallery = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
