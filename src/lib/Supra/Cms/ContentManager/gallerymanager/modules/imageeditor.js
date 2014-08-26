YUI.add('gallerymanager.imageeditor', function (Y) {
	//Invoke strict mode
	"use strict";
	
	// Shortcuts
	var Manager = Supra.Manager;
	
	
	
	function ImageEditor () {
		ImageEditor.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	ImageEditor.MODE_IMAGE = 1;
	ImageEditor.MODE_ICON = 2;
	
	ImageEditor.NAME = 'imageeditor';
	ImageEditor.CLASS_NAME = Y.ClassNameManager.getClassName(ImageEditor.NAME);
	ImageEditor.ATTRS = {
		// Disabled state
		'disabled': {
			value: false,
			setter: '_setDisabled'
		},
		// Editing state
		'editing': {
			value: false,
			setter: '_setEditing'
		},
		// Value
		'value': {
			value: null,
			setter: '_setValue',
			getter: '_getValue'
		},
		// Alias of value
		'saveValue': {
			value: null,
			getter: '_getSaveValue'
		},
		// Image resizer object
		'imageResizer': {
			value: null
		},
		// Blank image URI or data URI
		'blankImageUrl': {
			value: "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
		},
		// Mode: image or icon
		'mode': {
			value: ImageEditor.MODE_IMAGE
		}
	};
	
	Y.extend(ImageEditor, Y.Widget, {
		
		CONTENT_TEMPLATE: '<img />',
		
		/**
		 * Value change trigerred by updateImageSize
		 * @private
		 */
		silentUpdateImageSize: false,
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		renderUI: function () {
			this.get('boundingBox').addClass('yui3-inline-reset');
			
			var value = this.get('value');
			if (value) {
				this._applyStyle(value);
				this.updateImageSize();
			}
			
			this.after('valueChange', this.updateImageSize, this);
		},
		
		/**
		 * Bind event listeners
		 */
		bindUI: function () {
			// or on focus event
			this.on('focusedChange', function (e) {
				if (e.newVal) this.edit();
			}, this);
		},
		
		
		/* ------------------------------ API -------------------------------- */
		
		
		/**
		 * Start editing image
		 */
		edit: function (e) {
			if (!this.get('disabled')) {
				this.set('editing', true);
			}
		},
		
		/**
		 * Close image editor
		 */
		close: function () {
			if (this.get('editing')) {
				this.set('editing', false);
			}
		},
		
		/**
		 * Update image size to fill container
		 */
		updateImageSize: function () {
			if (this.silentUpdateImageSize) return;
			this.silentUpdateImageSize = true;
			
			var node = this.get('srcNode'),
				old_val = this.get('value'),
				new_val = null,
				mode = this.get('mode');
			
			if (node && old_val) {
				
				if (mode == ImageEditor.MODE_IMAGE) {
					// Image
					new_val = Y.DataType.Image.resize(old_val, {
						'node': node.ancestor(),
						'scale': true
					});
					
					if (
						new_val.crop_width  != old_val.crop_width ||
						new_val.crop_height != old_val.crop_height ||
						new_val.crop_left   != old_val.crop_left ||
						new_val.crop_top    != old_val.crop_top ||
						new_val.size_width  != old_val.size_width ||
						new_val.size_height != old_val.size_height)
					{
						this.set('value', new_val);
						this.fire('change');
						this.fire('resize');
					}
				} else {
					// Icon
					
					// Process like image
					new_val = Y.DataType.Image.resize({
						'image': {'sizes': {'original': {'width': 940, 'height': 940}}},
						'crop_width': old_val.width || 940,
						'crop_height': old_val.height || 940,
						'crop_left': 0,
						'crop_top': 0,
						'size_width': old_val.width || 940,
						'size_height': old_val.height || 940,
					}, {
						'node': node.ancestor(),
						'scale': true
					});
					
					if (
						new_val.crop_width  != old_val.width ||
						new_val.crop_height != old_val.height)
					{
						old_val.width = new_val.crop_width;
						old_val.height = new_val.crop_height;
						
						this.set('value', old_val);
						this.fire('change');
						this.fire('resize');
					}
				}
			}
			
			this.silentUpdateImageSize = false;
		},
		
		
		/* ------------------------------ PRIVATE -------------------------------- */
		
		
		/**
		 * Start editing image
		 * 
		 * @private
		 */
		_startEditing: function () {
			var imageResizer = this.get('imageResizer'),
				node = this.get('srcNode'),
				wrap = (node.closest('.supra-image, .supra-icon') || node),
				ancestor = wrap.ancestor(),
				width = null,
				size = null,
				mode = this.get('mode'),
				
				resizer_mode = 0,
				allow_zoom_resize = false;
			
			if (mode == ImageEditor.MODE_IMAGE) {
				size = this.get('value').image.sizes.original;
				resizer_mode = Supra.ImageResizer.MODE_IMAGE;
				allow_zoom_resize = false;
			} else {
				//size = this.get('value');
				//resizer_mode = Supra.ImageResizer.MODE_ICON;
				//allow_zoom_resize = true;
				
				// Icon size is defined by theme, not by user!!!
				return;
			}
			
			if (ancestor.test('.yui3-widget')) {
				ancestor = ancestor.ancestor();
			}
			
			width = ancestor.get('offsetWidth') || wrap.getAttribute('width') || wrap.get('offsetWidth');
			
			if (!imageResizer) {
				imageResizer = new Supra.ImageResizer({
					'mode': resizer_mode,
					'allowZoomResize': allow_zoom_resize,
					'autoClose': false,
					'maxCropWidth': Math.min(width, size.width),
					'minCropWidth': Math.min(width, size.width)
				});
				imageResizer.on('resize', this._editingUpdate, this);
				
				this.set('imageResizer', imageResizer);
			}
			
			imageResizer.set('maxCropWidth', Math.min(width, size.width));
			imageResizer.set('minCropWidth', Math.min(width, size.width));
			
			if (mode == ImageEditor.MODE_IMAGE) {
				imageResizer.set('maxImageWidth', size.width);
				imageResizer.set('maxImageHeight', size.height);
			} else {
				// Same as crop
				imageResizer.set('maxImageWidth', Math.min(width, size.width));
				imageResizer.set('maxImageHeight', Math.min(width, size.width));
			}
			
			imageResizer.set('image', node);
		},
		
		/**
		 * Stop editing image
		 * 
		 * @private
		 */
		_stopEditing: function () {
			var imageResizer = this.get('imageResizer');
			if (imageResizer) {
				imageResizer.set('image', null);
			}
		},
		
		_editingUpdate: function (event) {
			var image = this.get('value'),
				mode  = this.get('mode');
			
			if (mode == ImageEditor.MODE_IMAGE) {
				//Update crop, etc.
				image.crop_top = event.cropTop;
				image.crop_left = event.cropLeft;
				image.crop_width = event.cropWidth;
				image.crop_height = event.cropHeight;
				image.size_width = event.imageWidth;
				image.size_height = event.imageHeight;
			} else {
				image.width = event.imageWidth;
				image.height = event.imageHeight;
			}
			
			this.set('value', image);
			this.fire('change');
		},
		
		/**
		 * Apply style
		 * 
		 * @private
		 */
		_applyStyle: function (value) {
			var node = this.get('srcNode'),
				container = null;
			
			if (!node) return;
			
			container = node.ancestor();
			
			if (this.get('mode') == ImageEditor.MODE_IMAGE) {
				// Image
				
				if (value) {
					if (!container.hasClass('supra-image')) {
						var doc = node.getDOMNode().ownerDocument;
						container = Y.Node(doc.createElement('span'));
						
						node.insert(container, 'after');
						container.addClass('supra-image');
						container.append(node);
					}
					
					node.setStyle('margin', -value.crop_top + 'px 0 0 -' + value.crop_left + 'px');
					
					// none is invalid, but it removes previous size
					node.setStyle('width', value.size_width ? value.size_width + 'px' : 'none');
					node.setStyle('height', value.size_height ? value.size_height + 'px' : 'none');
					
					node.setAttribute('width', value.size_width);
					node.setAttribute('height', value.size_height);
					node.setAttribute('src', value.image.sizes.original.external_path);
					container.setStyles({
						'width': value.crop_width,
						'height': value.crop_height
					});
				} else {
					node.setStyles({
						'margin': '0'
					});
					
					node.setAttribute('src', this.get('blankImageUrl'));
					node.removeAttribute('width');
					node.removeAttribute('height');
					
					if (container) {
						container.setStyles({
							'width': 'auto',
							'height': 'auto'
						});
					}
				}
			} else {
				// Icon
				if (value) {
					if (!container.hasClass("supra-icon")) {
						var doc = node.getDOMNode().ownerDocument;
						container = Y.Node(doc.createElement("span"));
						
						node.insert(container, "after");
						container.addClass("supra-icon");
						container.append(node);
					}
					
					value.render(node);
					
					container.setStyle('display', '');
					
					container.setStyles({
						"width": value.width,
						"height": value.height
					});
					
				} else {
					container.setStyle('display', 'none');
				}
			}
		},
		
		
		/* ------------------------------ ATTRIBUTES -------------------------------- */
		
		
		/**
		 * Disabled attribute setter
		 * 
		 * @param {Boolean} value New disabled attribute value
		 * @returns {Boolean} New disabled attribute value
		 */
		_setDisabled: function (value) {
			value = !!value;
			
			if (value && this.get('focused')) {
				this.blur()
			}
			if (value && this.get('editing')) {
				this.set('editing', false);
			}
			
			return value;
		},
		
		/**
		 * Editing attribute setter
		 * 
		 * @param {Boolean} value New editing attribute value
		 * @returns {Boolean} New editing attribute value
		 */
		_setEditing: function (value) {
			value = !!value;
			
			if (value) {
				this._startEditing();
			} else {
				this._stopEditing();
			}
			
			return value;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value New value attribute value
		 * @returns {Object} New value attribute value
		 */
		_setValue: function (value) {
			// Convert to Icon object
			if (value && this.get('mode') == ImageEditor.MODE_ICON) {
				if (!(value instanceof Y.DataType.Icon)) {
					value = new Y.DataType.Icon(value);
				}
			}
			
			if (value) {
				this._applyStyle(value);
			}
			
			return value;
		},
		
		/**
		 * saveValue attribute getter, which is alias of value
		 * 
		 * @private
		 */
		_getSaveValue: function () {
			return this.get('value');
		}
		
	});
	
	Supra.GalleryManagerImageEditor = ImageEditor;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.imageresizer', 'widget']});