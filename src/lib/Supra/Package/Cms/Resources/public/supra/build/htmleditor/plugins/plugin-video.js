YUI().add('supra.htmleditor-plugin-video', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	var Manager = Supra.Manager;
	
	var DEFAULT_ALIGN = 'middle';
	
	Supra.HTMLEditor.addPlugin('video', defaultConfiguration, {
		
		/**
		 * Link editor is visible
		 * @type {Boolean}
		 */
		visible: false,
		
		
		/* --------------------------- INSERT/EDIT VIDEO --------------------------- */
		
		
		/**
		 * Returns empty data object
		 * 
		 * @private
		 */
		getBlankData: function () {
			return {
				'type': this.NAME,
				'resource': 'source',
				'source': '',
				'width': 0,
				'height': 0,
				'align': DEFAULT_ALIGN
			};
		},
		
		/**
		 * Insert link around current selection
		 */
		insertVideo: function () {
			if (!this.htmleditor.editingAllowed) return;
			
			var htmleditor = this.htmleditor,
				selection = htmleditor.getSelection(),
				node = null;
			
			// If in current selection is a video then edit it instead of creating new
			var nodes = htmleditor.findNodesInSelection(selection, '.supra-video');
			
			if (nodes && nodes.size()) {
				// Edit selected video
				
				node = nodes.item(0);
				
				if ( this.editVideo(node) ) {
					//Prevent default 
					return false;
				}
				
			} else if (htmleditor.isSelectionEditable(selection)) {
				// Insert video element
				var uid = htmleditor.generateDataUID(),
					html = '<div id="' + uid + '" class="supra-video su-uneditable" tabindex="0"></div>',
					data = this.getBlankData();
				
				htmleditor.setData(uid, data);
				htmleditor.replaceSelection(html, null);
				
				node = htmleditor.one('#' + uid);
				node.addClass('align-' + data.align);
				htmleditor.disableNodeEditing(node.getDOMNode());
				
				// Trigger selection change event
				this.htmleditor.refresh(true);
				
				// Start editing video
				if ( this.editVideo(node, data) ) {
					//Prevent default
					return false;
				}
				
			}
			
			return true;
		},
		
		/**
		 * Edit video
		 * 
		 * @param {Object} target Event or target element
		 * @param {Object} data Target element data, optional
		 * @private
		 */
		editVideo: function (target, data) {
			// Target may be actually event object
			var target = target.currentTarget ? target.currentTarget : target;
			
			if (!this.htmleditor.editingAllowed) return;
			
			//Get current value
			var data = data || this.htmleditor.getData(target);
			
			if (!data) {
				// Missing data
				return false;
			}
			
			this.showVideoSettings(target, data, function () {
				this.onVideoEditingDone(target);
			}, this);
			
			return true;
		},
		
		/**
		 * After user changed video save data into htmleditor
		 * 
		 * @param {Object} event
		 * @private
		 */
		onVideoEditingDone: function () {
			if (!this.selected_video) return;
			
			var data  = this.settings_form.getValues('id'),
				id    = this.selected_video_id,
				video = null;
			
			if (data && data.video) {
				video = data.video;
				
				if (!video.width) {
					video.width = parseInt(this.selected_video.get('offsetWidth'), 10);
					video.height = ~~(video.width / Supra.Input.Video.getVideoSizeRatio(video));
				}
				
				// Update data
				this.htmleditor.setData(id, Supra.mix({'type': this.NAME}, video));
				
				// Update preview
				this.updateVideoPreview(this.selected_video, video);
				this.selected_video.removeClass('align-left').removeClass('align-right').removeClass('align-middle').addClass('align-' + video.align);
			}
			
			//
			this.selected_video = null;
			this.selected_video_id = null;
			
			this.hideVideoSettings();
			
			//Trigger selection change event
			this.visible = false;
			this.htmleditor.refresh(true);
			
			//Button is not down anymore
			var button = this.htmleditor.get('toolbar').getButton('insertvideo');
			if (button) button.set('down', false);
		},
		
		/**
		 * On video input change update UI
		 */
		updatePreview: function (e) {
			// Triggered because we are setting form values
			if (this._setValueTrigger || !e.value) return;
			

			var input = this.settings_form.getInput('video'),
				value = e.value,
				node  = this.selected_video;
			
			node.removeClass('align-left').removeClass('align-right').removeClass('align-middle').addClass('align-' + value.align);
			this.updateVideoPreview(node, value);
			
		},
		
		/**
		 * Update video preview size
		 * 
		 * @param {Object} node Video element
		 * @param {Object} data Video data
		 * @private
		 */
		updateVideoSize: function (node, data) {
			var Input = Supra.Input.Video,
				width = parseInt(data.width, 10) || node.get('offsetWidth'),
				height = ~~(width / Input.getVideoSizeRatio(data));
			
			if (data.width != width) {
				data.width = width;
				data.height = height;
			}
			
			node.setStyles({
				'width': data.width + 'px',
				'height': data.height + 'px'
			});
		},
		
		/**
		 * Update video preview image
		 * 
		 * @param {Object} node Video element
		 * @param {Object} data Video data
		 * @private
		 */
		updateVideoPreview: function (node, data) {
			var Input = Supra.Input.Video;
			Input.getVideoPreviewUrl(data).always(function (url) {
				if (url) {
					// Using setAttribute because it's not possible to use !important in styles
					node.setAttribute('style', 'background: #000000 url("' + url + '") no-repeat scroll center center !important; background-size: 100% !important;')
				} else {
					node.removeAttribute('style');
				}
				
				this.updateVideoSize(node, data);
			}, this);
		},
		
		
		/* --------------------------- DELETE VIDEO --------------------------- */
		
		
		/**
		 * Remove selected video
		 * 
		 * @private
		 */
		removeSelectedVideo: function () {
			var node = this.selected_video,
				id = this.selected_video_id;
			
			if (node) {
				node.remove();
				this.selected_video = null;
				this.selected_video_id = null;
				this.htmleditor.removeData(id);
				this.htmleditor.refresh(true);
				
				this.hideVideoSettings();
			}
		},
		
		
		/* --------------------------- SETTINGS FORM --------------------------- */
		
		
		/**
		 * Settings form
		 * @type {Object}
		 * @private
		 */
		settings_form: null,
		
		/**
		 * Selected video ID
		 * @type {String}
		 * @private
		 */
		selected_video_id: null,
		
		/**
		 * Selected video element
		 * @type {Object}
		 * @private
		 */
		selected_video: null,
		
		
		/**
		 * Create settings form
		 * 
		 * @returns {Object} Settings form
		 * @private
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction("PageContentSettings").get("contentInnerNode");
			if (!content) return;
			
			//Properties form
			var form_config = {
				"inputs": [
					{
						"id": "video",
						"type": "Video",
						"label": Supra.Intl.get(["htmleditor", "video_source"]),
						"description": Supra.Intl.get(["htmleditor", "video_description"]),
						"value": "",
						"allowAlign": true
					}
				],
				"style": "vertical"
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			form.getInput('video').on('change', this.updatePreview, this);
			
			//Delete button
			var btn = new Supra.Button({"label": Supra.Intl.get(["htmleditor", "video_delete"]), "style": "small-red"});
				btn.render(form.get("contentBox"));
				btn.addClass("su-button-delete");
				btn.on("click", this.removeSelectedVideo, this);
			
			this.settings_form = form;
			return form;
		},
		
		/**
		 * Show video settings
		 * 
		 * @param {Object} target Target element
		 * @param {Object} data Video object data
		 */
		showVideoSettings: function (target, data, callback) {
			if (!data) {
				Y.log("Missing data to edit video", "debug");
				return false;
			}
			
			//Make sure PageContentSettings is rendered
			var form = this.settings_form || this.createSettingsForm(),
				action = Manager.getAction("PageContentSettings"),
				width = 0,
				max_width = 0;
			
			if (!form) {
				if (action.get("loaded")) {
					if (!action.get("created")) {
						action.renderAction();
						this.showVideoSettings(target, data, callback);
					}
				} else {
					action.once("loaded", function () {
						this.showVideoSettings(target, data, callback);
					}, this);
					action.load();
				}
				return false;
			}
			
			if (!Manager.getAction('PageToolbar').hasActionButtons("htmleditor-plugin")) {
				Manager.getAction('PageToolbar').addActionButtons("htmleditor-plugin", []);
				Manager.getAction('PageButtons').addActionButtons("htmleditor-plugin", []);
			}
			
			action.execute(form, {
				"hideCallback": Y.bind(callback, this),
				"title": Supra.Intl.get(["htmleditor", "video_properties"]),
				"scrollable": true,
				"toolbarActionName": "htmleditor-plugin"
			});
			
			//
			this.selected_video = target;
			this.selected_video_id = this.selected_video.getAttribute("id");
			
			// Initial width
			max_width = parseInt(this.selected_video.ancestor().get('offsetWidth'), 10);
			width = parseInt(data.width || this.selected_video.get('offsetWidth'), 10);
			
			if (!data.width || data.width != width) {
				data.width = width;
				data.height = ~~(width / Supra.Input.Video.getVideoSizeRatio(data));
			}
			
			this._setValueTrigger = true;
			
			form.getInput('video').set('maxWidth', max_width);
			
			form.resetValues()
				.setValues({'video': data}, 'id', true);
			
			this._setValueTrigger = false;
			
			
			return true;
		},
		
		/**
		 * Hide link manager
		 */
		hideVideoSettings: function () {
			if (this.settings_form && this.settings_form.get("visible")) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * Show or hide link manager based on toolbar button state
		 */
		toggleVideoSettings: function () {
			if (this.selected_video) {
				this.onVideoEditingDone();
				
				// Prevent default
				return false;
			} else {
				return this.insertVideo();
			}
		},
		
		/* --------------------------- INITIALIZE --------------------------- */
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			// Add command
			htmleditor.addCommand('insertvideo', Y.bind(this.toggleVideoSettings, this));
			
			// When clicking on video show editor
			var container = htmleditor.get('srcNode');
			container.delegate('click', Y.bind(this.editVideo, this), '.supra-video');
			
			// Button
			var toolbar = htmleditor.get('toolbar');
			var button = toolbar ? toolbar.getButton('insertvideo') : null;
			if (button) {
				button.show();
				
				//When un-editable node is selected disable toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
			}
			
			this.visible = false;
			
			//After paste replace links with tags
			htmleditor.on('pasteHTML', this.tagPastedHTML, this);
			
			//When selection changes hide link manager
			htmleditor.on('selectionChange', this.hideVideoSettings, this);
			
			//When video looses focus hide settings form
			htmleditor.on('nodeChange', this.onNodeChange, this);
			
			//Hide link manager when editor is closed
			htmleditor.on('disable', this.hideVideoSettings, this);
			
			// When HTML changes make sure video previews are set
			htmleditor.on("afterSetHTML", this.afterSetHTML, this);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {},
		
		
		/* --------------------------- TOOLBAR --------------------------- */
		
		
		onNodeChange: function () {
			var button = htmleditor.get("toolbar").getButton("insertvideo");
				allowEditing = this.htmleditor.editingAllowed;
			
			if (!allowEditing || this.htmleditor.getSelectedElement("svg, img")) {
				button.set('disabled', true);
			} else {
				button.set('disabled', !allowEditing);
			}
		},
		
		
		/* --------------------------- PARSER --------------------------- */
		
		
		/**
		 * Update video previews
		 * 
		 * @private
		 */
		afterSetHTML: function () {
			var htmleditor = this.htmleditor,
				data = htmleditor.getAllData(),
				id,
				srcNode = htmleditor.get('srcNode'),
				node = null;
			
			for(id in data) {
				if (data[id].type == this.NAME) {
					node = srcNode.one('#' + id);
					if (node) {
						this.updateVideoPreview(node, data[id]);
					}
				}
			}
		},
		
		/**
		 * Process HTML and replace all nodes with supra tags {supra.video id="..."}
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {HTML}
		 */
		tagHTML: function (html) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME;
			
			//Opening tag
			html = html.replace(/<div [^>]*id="([^"]+)"[^>]*>[^<]*<\/div[^>]*>/gi, function (html, id) {
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
		 * Process HTML and replace all supra tags with nodes
		 * Called before HTML is set
		 * 
		 * @param {String} html HTML
		 * @param {Object} data Data
		 * @return Processed HTML
		 * @type {String}
		 */
		untagHTML: function (html, data) {
			var NAME = this.NAME;
			
			html = html.replace(/{supra\.video id="([^"]+)"}/ig, function (tag, id) {
				if (!id || !data[id] || data[id].type != NAME) return '';
				return '<div id="' + id + '" class="supra-video su-uneditable align-' + (data[id].align || DEFAULT_ALIGN) + '" tabindex="0"></div>';
			});
			
			return html;
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});