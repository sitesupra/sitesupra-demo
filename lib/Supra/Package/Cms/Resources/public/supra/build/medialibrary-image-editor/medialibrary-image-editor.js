/**
 * Plugin to add editing functionality for MediaList
 */
YUI.add('supra.medialibrary-image-editor', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var List = Supra.MediaLibraryList,
		Template = Supra.Template;
		
	/*
	 * Constants
	 */
	var SLIDE_ID = 'imageEditor';
	
	var TEMPLATE = Template.compile('<div class="yui3-imageeditor ui-light-background loading">\
						<span class="v-center"></span>\
						<div class="yui3-imageeditor-content">\
							<div class="overlay-t"></div><div class="overlay-b"></div><div class="overlay-l"></div><div class="overlay-r"></div>\
							<div class="overlay-c">\
								<span class="drag-lt"></span><span class="drag-rt"></span><span class="drag-lb"></span><span class="drag-rb"></span>\
							</div>\
							<img src="{{ external_path }}?r={{ Math.random() }}" alt="" />\
							<span class="loading-icon"></span>\
						</div>\
					</div>');
	
	/**
	 * File upload
	 * Handles standard file upload, HTML5 drag & drop, simple input fallback
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'medialist-image-editor';
	Plugin.NS = 'imageeditor';
	
	Plugin.ATTRS = {
		/**
		 * Media library data object, Supra.DataObject.Data instance
		 * @type {Object}
		 */
		'data': {
			value: null
		},
		
		/**
		 * Image data
		 * @type {Object}
		 */
		'imageData': {
			value: null
		},
		
		/**
		 * Request URI for image rotate
		 * @type {String}
		 */
		'rotateURI': {
			value: null
		},
		/**
		 * Request URI for image crop
		 * @type {String}
		 */
		'cropURI': {
			value: null
		},
		
		/**
		 * Editing mode
		 * @type {String}
		 */
		'mode': {
			value: ''
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Edit button
		 * @type {Object}
		 * @private
		 */
		button_edit: null,
		
		/**
		 * Container node
		 * @type {Object}
		 * @private
		 */
		node: null,
		
		/**
		 * Crop selection
		 * @type {Object}
		 * @private
		 */
		crop: {},
		
		/**
		 * Image width
		 * @type {Number}
		 * @private
		 */
		width: null,
		
		/**
		 * Image height
		 * @type {Number}
		 * @private
		 */
		height: null,
		
		/**
		 * Crop overlay nodes
		 * @type {Object}
		 * @private
		 */
		nodes: {},
		
		/**
		 * Moving image
		 * @type {Boolean}
		 * @private
		 */
		moving: false,
		
		/**
		 * Resizing crop area
		 * @type {Boolean}
		 * @private
		 */
		resizing: false,
		
		/**
		 * Last known mouse position
		 * @type {Number}
		 * @private
		 */
		startX: null,
		startY: null,
		startCrop: null,
		
		/**
		 * Rotation is deg
		 * @type {Number}
		 * @private
		 */
		rotation: 0,
		
		/**
		 * Image editor state
		 * @type {Boolean}
		 * @private
		 */
		opened: false,
		
		/**
		 * Command is beeing executed, don't issue another request
		 * until previous one has finished
		 * @type {Boolean}
		 * @private
		 */
		loading: false,
		
		
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			//Reset crop
			this.crop = [0,0,0,0];
			
			//On item render bind button
			this.get('host').on('itemRender', this.bindToEditButton, this);
			
			//On close event hide image editor
			this.get('host').on(Plugin.NS + ':close', this.close, this);
			
			//On mode change enable/disable crop
			this.on('modeChange', this.onModeChange, this);
			
			//On document resize update layout
			Y.on('resize', Y.throttle(Y.bind(this.syncUI, this)));
		},
		
		/**
		 * On mode change show/hide crop tool
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		onModeChange: function (event) {
			if (event.prevVal != event.newVal) {
				this.node.removeClass(event.prevVal);
				this.node.addClass(event.newVal);
			}
		},
		
		/**
		 * Bind listener to button click
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		bindToEditButton: function (event /* Event */) {
			//Destroy button if it already exists
			if (this.button_edit) {
				this.button_edit.destroy();
				this.button_edit = null;
			}
			
			if (event.type == List.TYPE_IMAGE) {
				var node = event.node;
				
				//Create button
				var button = node.one('button.edit');
				if (!button) return;
				
				button = this.button_edit = new Supra.Button({'srcNode': button});
				button.render();
				
				//Attach listener
				button.on('click', this.open, this);
				
				//Save data
				this.set('imageData', event.data);
			}
		},
		
		command: function (command) {
			switch(command) {
				case 'rotateleft':
					this.cmdRotate(1); break;
				case 'rotateright':
					this.cmdRotate(-1); break;
				case 'crop':
					this.cmdCrop(); break;
			}
		},
		
		/**
		 * Rotate image in a direction
		 */
		cmdRotate: function (direction) {
			if (this.loading) return;
			this.loading = true;
			this.node.addClass('loading');
			
			this.rotation = (this.rotation + 90 * direction);
			
			//Save image data
			var image_data = this.get('imageData'),
				uri = this.get('rotateURI');
			
			Supra.io(uri, {
				'data': {
					'id': image_data.id,
					'rotate': (this.rotation % 360),
				},
				'context': this,
				'method': 'post',
				'on': {
					'complete': function (data, status) {
						
						//Reset rotation
						this.rotation = 0;
						this.loading = false;
						
						if (status) {
							//Update image data
							var latest_data = this.get('host').get('data').cache.one(image_data.id);
							latest_data.sizes = data.sizes;
							image_data.sizes = data.sizes;
							
							this.set('imageData', image_data);
							
							//Update image
							var timestamp = +new Date(),
								src = latest_data.sizes.original.external_path + '?r=' + timestamp;
							
							this.node.one('img').setAttribute('src', src);
						}
						
						this.node.removeClass('loading');
						
						//Fire event on media list
						this.get('host').fire('rotate', {'file_id': image_data.id});
					}
				}
			});
		},
		
		/**
		 * Crop image
		 */
		cmdCrop: function () {
			if (this.loading) return;
			this.loading = true;
			this.node.addClass('loading');
			
			this.set('mode', '');
			
			//Save image data
			var image_data = this.get('imageData'),
				uri = this.get('cropURI');
			
			if (this.crop.left == 0 && this.crop.top == 0 && this.crop.width == this.width && this.crop.height == this.height) {
				this.node.removeClass('loading');
				return;
			}
			
			Supra.io(uri, {
				'data': {
					'id': image_data.id,
					'crop': this.crop,
				},
				'context': this,
				'method': 'post',
				'on': {
					'complete': function (data, status) {
						this.loading = false;
						
						if (status) {
							//Update image data
							var latest_data = this.get('host').get('data').cache.one(image_data.id);
							latest_data.sizes = data.sizes;
							image_data.sizes = data.sizes;
							
							this.set('imageData', image_data);
							
							//Update image
							var timestamp = +new Date(),
								src = latest_data.sizes.original.external_path + '?r=' + timestamp;
							
							this.node.one('img').setAttribute('src', src);
							
							//Fire event on media list
							this.get('host').fire('crop', {'file_id': image_data.id});
						}
						
						this.node.removeClass('loading');
						
					}
				}
			});
		},
		
		/**
		 * Open image editor
		 */
		open: function () {
			var host = this.get('host'),
				container_node = host.get('boundingBox'),
				width = container_node.get('offsetWidth'),
				image_data = this.get('imageData');
			
			//Reset
			this.rotation = 0;
			
			//Trigger event
			host.fire(Plugin.NS + ':open');
			
			//Create container node
			if (!this.node) {
				this.node = Y.Node.create(TEMPLATE(image_data.sizes.original));
				container_node.insert(this.node, 'after');
				
				//Set nodes
				this.nodes = {
					'left': this.node.one('.overlay-l'),
					'right': this.node.one('.overlay-r'),
					'top': this.node.one('.overlay-t'),
					'bottom':this.node.one('.overlay-b'),
					'center': this.node.one('.overlay-c')
				};
				
				this.nodes.center.on('mousedown', this.startDrag, this);
				
				//Reset crop guides when image is reloaded
				this.node.one('img').on('load', this.onOpenComplete, this);
			}
			
			//Set loading icon
			this.node.addClass('loading');
			
			//Set image 
			this.node.one('img').setAttribute('src', image_data.sizes.original.external_path + '?r=' + (+ new Date()));
			
			//Update node width to match full width of media library
			this.node.setStyles({'display': 'block', 'width': width + 'px', 'left': width + 'px'});
			
			//Animate
			this.node.transition({
			    easing: 'ease-out',
			    duration: 0.5, // seconds,
			    left: '0px'
			});
			
			//Update state
			this.opened = true;
		},
		
		/**
		 * When image is loaded save its data
		 * Resize crop guides
		 * 
		 * @private
		 */
		onOpenComplete: function () {
			var node_img = this.node.one('img');
			
			this.node.removeClass('loading');
			
			//Save image properties
			this.width = node_img.get('offsetWidth');
			this.height = node_img.get('offsetHeight');
			this.crop = {'left': 0, 'top': 0, 'width': this.width, 'height': this.height};
			
			this.syncCropGuides();
		},
		
		/**
		 * Close image editor
		 */
		close: function (event) {
			if (this.node) {
				var width = this.get('host').get('boundingBox').get('offsetWidth');
				
				//Animate
				this.node.transition({
				    easing: 'ease-out',
				    duration: 0.5, // seconds,
				    left: width + 'px'
				}, function () {
					this.setStyle('display', 'none');
				});
				
				//Reload image preview source
				this.get('host').reloadImageSource(this.get('imageData'));
			}
			
			//Update state
			this.opened = false;
		},
		
		/**
		 * Update container width
		 */
		syncUI: function () {
			if (this.opened && this.node) {
				var container_node = this.get('host').get('boundingBox'),
					width = container_node.get('offsetWidth');
				
				this.node.setStyle('width', width + 'px');
			}
		},
		
		/**
		 * Update crop guides
		 */
		syncCropGuides: function () {
			var nodes = this.nodes,
				crop = this.crop,
				width = this.width,
				height = this.height;
			
			nodes.top.setStyle('height', crop.top + 'px');
			nodes.bottom.setStyle('height', height - crop.height - crop.top + 'px');
			
			nodes.left.setStyles({
				'height': crop.height + 'px',
				'top': crop.top + 'px',
				'width': crop.left + 'px'
			});
			nodes.right.setStyles({
				'height': crop.height + 'px',
				'top': crop.top + 'px',
				'width': width - crop.left - crop.width + 'px'
			});
			
			nodes.center.setStyles({
				'left': crop.left - 1 + 'px',
				'top': crop.top - 1 + 'px',
				'width': crop.width + 'px',
				'height': crop.height + 'px'
			});
		},
		
		/**
		 * Set initial state for 'move'
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		startMove: function (event) {
			this.moving = true;
			this.startX = event.clientX;
			this.startY = event.clientY;
			this.startCrop = Supra.mix({}, this.crop);
			
			var doc = Y.one(document);
			doc.on('mousemove', this.onMove, this);
			doc.once('mouseup', this.endMove, this);
			
			event.halt();
		},
		
		/**
		 * Handle mouse move while moving selected region
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		onMove: function (event) {
			var deltaX = event.clientX - this.startX,
				deltaY = event.clientY - this.startY;
			
			this.crop.left = Math.min(this.width - this.crop.width, Math.max(0, this.startCrop.left + deltaX));
			this.crop.top = Math.min(this.height - this.crop.height, Math.max(0, this.startCrop.top + deltaY));
			
			this.syncCropGuides();
		},
		
		/**
		 * Handle mouseup while moving selected region
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		endMove: function (event) {
			this.moving = false;
			this.startCrop = null;
			
			Y.one(document).unsubscribe('mousemove', this.onMove, this);
		},
		
		/**
		 * Start resizing
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		startResize: function (event) {
			this.startX = event.clientX;
			this.startY = event.clientY;
			this.startCrop = Supra.mix({}, this.crop);
			
			var doc = Y.one(document);
			doc.on('mousemove', this.onResize, this);
			doc.once('mouseup', this.endResize, this);
			
			event.halt();
		},
		
		/**
		 * Handle mouse move while resizing
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		onResize: function (event) {
			var deltaX = event.clientX - this.startX,
				deltaY = event.clientY - this.startY,
				val = null,
				delta = null;
			
			if (this.resizing.l) {
				val = Math.min(this.startCrop.left + this.startCrop.width - 1, Math.max(0, this.startCrop.left + deltaX));
				delta = this.crop.left - val;
				this.crop.left = val;
				this.crop.width += delta;
			}
			if (this.resizing.t) {
				val = Math.min(this.startCrop.top + this.startCrop.height - 1, Math.max(0, this.startCrop.top + deltaY));
				delta = this.crop.top - val;
				this.crop.top = val;
				this.crop.height += delta;
			}
			if (this.resizing.w) {
				val = Math.min(this.width - this.startCrop.left, Math.max(1, this.startCrop.width + deltaX));
				this.crop.width = val;
			}
			if (this.resizing.h) {
				val = Math.min(this.height - this.startCrop.top, Math.max(1, this.startCrop.height + deltaY));
				this.crop.height = val;
			}
			
			this.syncCropGuides();
		},
		
		/**
		 * Handle mouse up while resizing
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		endResize: function (event) {
			this.resizing = false;
			this.startCrop = null;
			
			Y.one(document).unsubscribe('mousemove', this.onResize, this);
		},
		
		/**
		 * On drag start check if user is moving or resizing image
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		startDrag: function (event) {
			var target = event.target,
				direction = 'lt';
			
			if (target.test('span')) {
				//Resizing
				if (target.test('.drag-lt')) {
					this.resizing = {'l': true, 't': true, 'w': false, 'h': false};
				} else if (target.test('.drag-rt')) {
					this.resizing = {'l': false, 't': true, 'w': true, 'h': false};
				} else if (target.test('.drag-rb')) {
					this.resizing = {'l': false, 't': false, 'w': true, 'h': true};
				} else if (target.test('.drag-lb')) {
					this.resizing = {'l': true, 't': false, 'w': false, 'h': true};
				}
				this.startResize(event);
			} else {
				//Moving
				this.startMove(event);
			}
		}
		
	});
	
	List.ImageEditor = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['plugin', 'transition', 'supra.template']});