YUI().add('supra.htmleditor-plugin-image', function (Y) {
	
	var defaultConfiguration = {
		'size': '200x200'
	};
	
	var defaultProps = {
		'type': null,
		'title': '',
		'description': '',
		'align': 'right',
		'style': '',
		'image': {}
	};
	
	var Manager = Supra.Manager;
	
	Supra.HTMLEditor.addPlugin('image', defaultConfiguration, {
		
		settings_form: null,
		selected_image: null,
		selected_image_id: null,
		original_data: null,
		silent: false,
		
		/**
		 * Image which is beeing dragged
		 * @type {Object}
		 */
		drag_image: null,
		
		/**
		 * Drag end event listener attach point
		 * @type {Object}
		 */
		fn_drag_end: null,
		
		/**
		 * Drag & drop mouse up event listener attach point
		 * @type {Object}
		 */
		fn_mouse_up: null,
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').one();
			if (!content) return;
			
			//Properties form
			var form_config = {
				'inputs': [
					{'id': 'title', 'type': 'String', 'label': 'Image title', 'value': ''},
					{'id': 'description', 'type': 'String', 'label': 'Alt title', 'value': ''},
					{'id': 'align', 'type': 'SelectList', 'label': 'Alignment', 'value': 'right', 'values': [
						{'id': 'left', 'title': 'Left', 'icon': '/cms/supra/img/htmleditor/align-left.png'},
						{'id': 'middle', 'title': 'Center', 'icon': '/cms/supra/img/htmleditor/align-center.png'},
						{'id': 'right', 'title': 'Right', 'icon': '/cms/supra/img/htmleditor/align-right.png'}
					]},
					{'id': 'style', 'type': 'SelectList', 'label': 'Style', 'value': 'default', 'values': [
						{'id': '', 'title': 'Normal', 'icon': '/cms/supra/img/htmleditor/image-style-normal.png'},
						{'id': 'border', 'title': 'Border', 'icon': '/cms/supra/img/htmleditor/image-style-border.png'},
						{'id': 'lightbox', 'title': 'Lightbox', 'icon': '/cms/supra/img/htmleditor/image-style-lightbox.png'}
					]}
				],
				'style': 'vertical'
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.get('boundingBox').addClass('yui3-form-properties');
				form.hide();
			
			//On title, description, etc. change update image data
			for(var i=0,ii=form_config.inputs.length; i<ii; i++) {
				form.getInput(form_config.inputs[i].id).on('change', this.onPropertyChange, this);
			}
			
			//Form heading
			var heading = Y.Node.create('<h2>Image properties</h2>');
			form.get('contentBox').insert(heading, 'before');
			
			//Buttons
			var buttons = Y.Node.create('<div class="yui3-form-buttons"></div>');
			form.get('contentBox').insert(buttons, 'before');
			
			//Save button
			var btn = new Supra.Button({'label': 'Apply', 'style': 'mid-blue'});
				btn.render(buttons).on('click', this.settingsFormApply, this);
			
			//Cancel button
			var btn = new Supra.Button({'label': 'Close', 'style': 'mid'});
				btn.render(buttons).on('click', this.settingsFormCancel, this);
			
			
			//Add 'Delete' and 'Replace buttons'
			//Replace button
			var btn = new Supra.Button({'label': 'Replace', 'style': 'mid'});
				btn.render(form.get('contentBox'));
				btn.addClass('yui3-button-edit');
				btn.on('click', function () {
					//Open Media library on 'Replace'
					if (this.selected_image) {
						//Open settings form and open MediaSidebar
						this.hideSettingsForm();
						Manager.getAction('MediaSidebar').execute({
							onselect: Y.bind(this.insertImage, this)
						});
					}
				}, this);
			
			//Delete button
			var btn = new Supra.Button({'label': 'Delete', 'style': 'mid-red'});
				btn.render(form.get('contentBox'));
				btn.addClass('yui3-button-delete');
				btn.on('click', this.removeSelectedImage, this);
			
			this.settings_form = form;
			return form;
		},
		
		/**
		 * Returns true if form is visible, otherwise false
		 */
		hideSettingsForm: function () {
			if (this.settings_form && this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * Apply settings changes
		 */
		settingsFormApply: function () {
			if (this.selected_image) {
				this.selected_image.removeClass('yui3-image-selected');
				this.selected_image = null;
				this.selected_image_id = null;
				this.original_data = null;
				
				this.hideSettingsForm();
				this.hideMediaSidebar();
				
				//Property changed, update editor 'changed' state
				this.htmleditor._changed();
			}
		},
		
		/**
		 * Cancel settings changes
		 */
		settingsFormCancel: function () {
			if (this.selected_image) {
				
				var imageId = this.selected_image_id,
					oldData = this.htmleditor.getData(imageId),
					data = this.original_data;
				
				//Restore old data
				this.htmleditor.setData(imageId, data);
				
				//Apply original properties to image if changed
				if (data.image.id != oldData.image.id) {
					this.setImageProperty('image', data.image);
				}
				for(var i in data) {
					if (typeof data[i] == 'string' && oldData[i] != data[i]) {
						this.setImageProperty(i, data[i]);
					}
				}
				
				this.selected_image.removeClass('yui3-image-selected');
				this.selected_image = null;
				this.selected_image_id = null;
				this.original_data = null;
				
				this.hideSettingsForm();
				this.hideMediaSidebar();
			}
		},
		
		/**
		 * Remove selected image
		 */
		removeSelectedImage: function () {
			if (this.selected_image) {
				this.selected_image.remove();
				this.selected_image = null;
				this.selected_image_id = null;
				this.original_data = null;
				this.htmleditor.refresh(true);
				this.hideSettingsForm();
			}
		},
		
		/**
		 * Handle property input value change
		 * Save data and update UI
		 * 
		 * @param {Object} event Event
		 */
		onPropertyChange: function (event) {
			if (this.silent || !this.selected_image) return;
			
			var target = event.target,
				id = target.get('id'),
				imageId = this.selected_image_id,
				data = this.htmleditor.getData(imageId),
				value = target.getValue();
			
			//Update image data
			if (imageId) {
				data[id] = value;
				this.htmleditor.setData(imageId, data);
			}
			
			this.setImageProperty(id, value);
		},
		
		/**
		 * Update image tag property
		 * 
		 * @param {String} id Property ID
		 * @param {String} value Property value
		 */
		setImageProperty: function (id, value, image) {
			if (!image) image = this.selected_image;
			
			if (id == 'title') {
				image.setAttribute('title', value);
			} else if (id == 'description') {
				image.setAttribute('alt', value);
			} else if (id == 'align') {
				image.setAttribute('align', value);
				image.removeClass('align-left').removeClass('align-right').removeClass('align-middle');
				if (value) image.addClass('align-' + value);
			} else if (id == 'style') {
				image.removeClass('border').removeClass('lightbox');
				if (value) image.addClass(value);
				
				var ancestor = image.ancestor();
				if (!ancestor) {
					Y.log('Missing ancestor for selected image. Image not in DOM anymore?');
					return;
				}
				
				//Update link
				if (value == 'lightbox') {
					//Get all image data (including all sizes and paths)
					var data = this.htmleditor.getData(this.selected_image_id);
					
					if (ancestor.test('a')) {
						//If parent is link then update href and rel attributes
						ancestor.setAttribute('href', this.getImageURLBySize(data.image, 'original'));
						ancestor.setAttribute('rel', 'lightbox');
						ancestor.addClass('lightbox');
					} else {
						//Create link
						var link = Y.Node.create('<a class="lightbox" href="' + this.getImageURLBySize(data.image, 'original') + '" rel="lightbox"></a>');
						image.insert(link, 'before');
						link.append(image);
					}
				} else if (ancestor.test('a.lightbox')) {
					//Remove link
					ancestor.insert(image, 'before');
					ancestor.remove();
				}
			} else if (id == 'image') {
				image.setAttribute('src', this.getImageURLBySize(value));
				
				//If lightbox then also update link
				if (this.getImageProperty('style') == 'lightbox') {
					this.setImageProperty('style', 'lightbox', image);
				}
			}
		},
		
		/**
		 * Returns image property value
		 * 
		 * @param {String} id
		 */
		getImageProperty: function (id) {
			if (this.selected_image) {
				var data = this.htmleditor.getData(this.selected_image_id);
				return id in data ? data[id] : null;
			}
			return null;
		},
		
		/**
		 * Show image settings bar
		 */
		showImageSettings: function (event) {
			if (event.target.test('.gallery')) return;
			
			var data = this.htmleditor.getData(event.target);
			if (!data) {
				Y.log('Missing image data for image ' + event.target.getAttribute('src'));
				return;
			}
			
			Manager.executeAction('PageContentSettings', this.settings_form || this.createSettingsForm());
			
			this.selected_image = event.target;
			this.selected_image.addClass('yui3-image-selected');
			this.selected_image_id = this.selected_image.getAttribute('id');
			
			this.silent = true;
			this.settings_form.resetValues()
							  .setValues(data, 'id');
			this.silent = false;
			
			//Clone data because data properties will change and orginal properties should stay intact
			this.original_data = Supra.mix({}, data);
			
			event.halt();
		},
		
		/**
		 * Show/hide media library bar
		 */
		toggleMediaSidebar: function () {
			var button = this.htmleditor.get('toolbar').getButton('insertimage');
			if (button.get('down')) {
				Manager.executeAction('MediaSidebar', {
					onselect: Y.bind(this.insertImage, this)
				});
			} else {
				this.hideMediaSidebar();
			}
		},
		
		/**
		 * Hide media library bar
		 */
		hideMediaSidebar: function () {
			Manager.getAction('MediaSidebar').hide();
		},
		
		/**
		 * Insert image into HTMLEditor content
		 * 
		 * @param {Object} event
		 */
		insertImage: function (event) {
			var htmleditor = this.htmleditor;
			
			if (!htmleditor.get('disabled') && htmleditor.isSelectionEditable(htmleditor.getSelection())) {
				var data = event.image;
				
				if (this.selected_image) {
					//If image in content is already selected, then replace
					var imageId = this.selected_image_id,
						imageData = this.htmleditor.getData(imageId);
					
					var data = Supra.mix({}, defaultProps, {
						'type': this.NAME,
						'title': data.title,
						'description': data.description,
						'align': imageData.align,
						'style': imageData.style,
						'image': data	//Original image data
					});
					
					//Preserve image data
					this.htmleditor.setData(imageId, data);
					
					//Update image attributes
					this.setImageProperty('image', data.image);
					this.setImageProperty('title', data.title);
					this.setImageProperty('description', data.description);
					
					//Update form input values
					this.settings_form.getInput('title').setValue(data.title);
					this.settings_form.getInput('description').setValue(data.description);
				} else {
					//Find image by size and set initial image properties
					var src = this.getImageURLBySize(data);
					var data = Supra.mix({}, defaultProps, {
						'type': this.NAME,
						'title': data.title,
						'description': data.description,
						'image': data	//Original image data
					});
					
					//Generate unique ID for image element, to which data will be attached
					var uid = htmleditor.generateDataUID();
					
					htmleditor.replaceSelection('<img id="' + uid + '" src="' + src + '" title="' + Y.Lang.escapeHTML(data.title) + '" alt="' + Y.Lang.escapeHTML(data.description) + '" />');
					htmleditor.setData(uid, data);
				}
				
				this.hideMediaSidebar();
			}
		},
		
		/**
		 * Update image after it was dropped using HTML5 drag & drop
		 * 
		 * @param {Object} event
		 */
		dropImage: function (target, image_id) {
			//If dropped on un-editable element
			if (!this.htmleditor.isEditable(target)) return;
			
			var htmleditor = this.htmleditor,
				image_data = Manager.MediaSidebar.getData(image_id);
			
			if (image_data.type != Supra.MediaLibraryData.TYPE_IMAGE) {
				//Only handling images; folders should be handled by gallery plugin 
				return;
			}
			
			var uid = htmleditor.generateDataUID(),
				src = this.getImageURLBySize(image_data),
				img = null;
			
			img = Y.Node.create('<img id="' + uid + '" src="' + src + '" title="' + Y.Lang.escapeHTML(image_data.title) + '" alt="' + Y.Lang.escapeHTML(image_data.description) + '" />');
			
			if (target.test('em,i,strong,b,s,strike,sub,sup,u,a,span,big,small,img')) {
				target.insert(img, 'before');
			} else {
				target.prepend(img);
			}
			
			//Set additional image properties
			var data = Supra.mix({}, defaultProps, {
				'type': this.NAME,
				'title': image_data.title,
				'description': image_data.description,
				'image': image_data	//Original image data
			});
			
			//Save into HTML editor data about image
			htmleditor.setData(uid, data);
		},
		
		/**
		 * Returns image url matching size
		 * 
		 * @param {Object} data
		 * @param {String} size
		 */
		getImageURLBySize: function (data, size) {
			var size = size ? size : this.configuration.size;
			
			if (size in data.sizes) {
				return data.sizes[size].external_path;
			}
			
			return null;
		},
		
		/**
		 * Disable image resizing using handles, FF
		 */
		disableImageObjectResizing: function () {
			try {
				this.htmleditor.get('doc').execCommand("enableObjectResizing", false, false);
			} catch (err) {}
		},
			
		/**
		 * Handle drag end (success or failure)
		 */
		onDragEnd: function () {
			this.drag_image = null;
			if (this.fn_drag_end) this.fn_drag_end.detach();
			if (this.fn_mouse_up) this.fn_mouse_up.detach();
			this.fn_drag_end = null;
			this.fn_mouse_up = null;
		},
		
		/**
		 * Handle drop
		 * 
		 * @param {Object} e Event
		 */
		onDrop: function (e) {
			this.onDragEnd();
			
			var image_id = e.drag_id;
			if (!image_id) return;
			
			//Only if dropped from gallery
			//Drop on image is handled by gallery plugin
			if (image_id.match(/^\d+$/) && e.drop && !e.drop.test('IMG')) {
				e.halt();
				this.dropImage(e.drop, image_id);
			}
		},
		
		/**
		 * Bind HTML5 Drag & Drop event listeners
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 */
		bindUIDnD: function (htmleditor) {
			//Allow HTML5 Drag & Drop
			var srcNode = htmleditor.get('srcNode');
			
			//Handle image drag which is in content
			srcNode.on('dragstart', function (e) {
				this.drag_image = e.target;
				
				//On mouse up or drag end remove temporary listeners and
				//reference to image
				this.fn_mouse_up = srcNode.once('mouseup', this.onDragEnd, this);
				this.fn_drag_end = e.target.once('dragend', this.onDragEnd, this);
			}, this);
			srcNode.on('dragend', this.onDragEnd, this);
			
			//On dragover change cursor to copy and prevent native drop
			srcNode.on('dragover', function (e) {
				//If draging image which already was in content, then allow native drop unless
				//droping on another image
				if (this.drag_image && !e.target.test('IMG')) return;
				
				if (e.preventDefault) e.preventDefault(); // Don't drop anything
			    e._event.dataTransfer.dropEffect = 'copy';
			    return false;
			}, this);
			
			//Handle drop event (triggered only if native drop was prevented in dragover) 
			srcNode.on('drop', function (e) {
				var data = e._event.dataTransfer.getData('text'),
					image = this.drag_image,
					target = null;
				
				this._fixTarget(e);
				target = e.target;
				
				//Trigger event to allow other plugins to override this behaviour
				var res = srcNode.fire('imageDrop', {
					'drag_id': data,
					'drag': image,
					'drop': target
				});
				
				//Clean up
				this.onDragEnd();
				
				//If any listener called e.halt() then stop image from being
				//dropped using native drop
				if (res === false) {
					if (e.preventDefault) e.preventDefault(); // Don't drop anything
					return false;
				}
			}, this);
			
			srcNode.on('imageDrop', this.onDrop, this);
		},
		
		/**
		 * IE reports srcNode as target, get correct drop target from mouse position
		 * 
		 * @param {Object} e
		 */
		_fixTarget: (Y.UA.ie ? function (e) {
			//IE reports srcNode as target, fix it
			var htmleditor = this.htmleditor,
				srcNode = htmleditor.get('srcNode'),
				target = null,
				tmp_target = null,
				pos = srcNode.getXY(),
				src_dom_node = Y.Node.getDOMNode(srcNode),
				scroll_x = htmleditor.get('doc').documentElement.scrollLeft,
				scroll_y = htmleditor.get('doc').documentElement.scrollTop;
			
			target = htmleditor.get('doc').elementFromPoint(e._event.x + pos[0] - scroll_x, e._event.y + pos[1] - scroll_y);
			tmp_target = target;
			
			//Check if srcNode is target or one of the targets ancestors
			while(tmp_target) {
				if (tmp_target === src_dom_node) {
					e.target = new Y.Node(target);
				}
				tmp_target = tmp_target.parentNode;
			}
		} : function (e) {}),
		
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			var mediasidebar = Manager.getAction('MediaSidebar'),
				toolbar = htmleditor.get('toolbar'),
				button = toolbar ? toolbar.getButton('insertimage') : null;
			
			// Add command
			htmleditor.addCommand('insertimage', Y.bind(this.toggleMediaSidebar, this));
			
			//When image looses focus hide settings form
			htmleditor.on('selectionChange', this.settingsFormApply, this);
			
			// When clicking on image show image settings
			var container = htmleditor.get('srcNode');
			container.delegate('click', Y.bind(this.showImageSettings, this), 'img');
			
			if (button) {
				//When media library is shown/hidden make button selected/unselected
				mediasidebar.after('visibleChange', function (evt) {
					button.set('down', evt.newVal);
				});
				
				//When un-editable node is selected disable mediasidebar toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
			}
			
			//When media library is hidden show settings form if image is selected
			mediasidebar.on('hide', function () {
				if (this.selected_image) {
					Manager.executeAction('PageContentSettings', this.settings_form);
				}
			}, this);
			
			//Hide media library when editor is closed
			htmleditor.on('disable', this.hideMediaSidebar, this);
			htmleditor.on('disable', this.settingsFormApply, this);
			
			//Disable image object resizing
			this.disableImageObjectResizing();
			htmleditor.on('enable', this.disableImageObjectResizing, this);
			
			this.bindUIDnD(htmleditor);
		},
		
		/**
		 * Clean up node
		 * Remove all styles and data about node
		 */
		cleanUp: function (target, data) {
			if (target.test('img') && data && data.type == this.NAME) {
				this.htmleditor.removeData(target);
				this.setImageProperty('style', '', target);
				this.setImageProperty('align', '', target);
			}
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			
		},
		
		/**
		 * Process HTML and replace all nodes with macros {supra.image id="..."}
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {HTML}
		 */
		processHTML: function (html) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME;
			
			html = html.replace(/<img [^>]*id="([^"]+)"[^>]*>/ig, function (html, id) {
				if (!id) return html;
				var data = htmleditor.getData(id);
				
				if (data && data.type == NAME) {
					return '{supra.' + NAME + ' id="' + id + '"}';
				} else {
					return html;
				}
			});
			return html;
		},
		
		/**
		 * Process data and remove all unneeded before it's sent to server
		 * Called before save
		 * 
		 * @param {String} id Data ID
		 * @param {Object} data Data
		 * @return Processed data
		 * @type {Object}
		 */
		processData: function (id, data) {
			data.image = data.image.id;
			return data;
		}
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});