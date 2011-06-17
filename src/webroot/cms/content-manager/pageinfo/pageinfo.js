SU(function (Y) {

	//Shortcut
	var Action = SU.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, {
		
		NAME: 'PageInfo',
		
		/**
		 * PageInfo action has stylesheet
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Localized texts
		 */
		LOCALE: {
			'published': 'Published Version',
			'draft': 'Draft Version',
			'publish': 'Scheduled Version<small>Due to publish on <span></span></small>',
			'unpublish': 'Scheduled Version<small>Due to unpublish on <span></span></small>'
		},
		
		/**
		 * Delete buttons
		 * @type {Object}
		 * @see {Supra.Button}
		 */
		button_delete: null,
		
		/**
		 * New page button
		 * @type {Object}
		 * @see {Supra.Button}
		 */
		button_newpage: null,
		
		/**
		 * "Published" button
		 * @type {Object}
		 * @see {Supra.Button}
		 */
		button_version: null,
		
		/**
		 * "Scheduled" button
		 * @type {Object}
		 * @see {Supra.Button}
		 */
		button_scheduled: null,
		
		/**
		 * Page data
		 * @type {Object}
		 */
		page_data: null,
		
		/**
		 * Page version data
		 * @type {Object}
		 */
		page_version_data: null,
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			// Create buttons
			var content = this.getContainer().one('div.buttons');
			var buttons = content.all('button');
			
			this.button_delete    = new SU.Button({ 'srcNode': buttons.item(3), 'style': 'mid-red' });
			this.button_scheduled = new SU.Button({ 'srcNode': buttons.item(2), 'style': 'large' });
			this.button_version   = new SU.Button({ 'srcNode': buttons.item(1), 'style': 'large' });
			this.button_newpage   = new SU.Button({ 'srcNode': buttons.item(0), 'style': 'large' });
			
			var rnd = Math.random();
			this.button_delete.on("click", function () {
				//@TODO
			});
			
			// Panel settings
			this.panel.setCloseVisible(true)
					  .setArrowPosition([SU.Panel.ARROW_L, SU.Panel.ARROW_C])
					  .setArrowVisible(true)
					  .set('constrain', document.body);
			
			/*
			 * Load page preview, because if mouseout event will fire before page preview has
			 * finished loading, then PagePreview will be visible even if mouse is not over
			 * button anymore
			 */
			SU.Manager.Loader.loadAction('PagePreview');
			
			window.xxx = this;
		},
		
		/**
		 * Render widgets
		 */
		render: function () {
			//Render buttons
			this.button_delete.render();
			this.button_version.render();
			this.button_newpage.render();
			
			this.button_scheduled.render();
			this.button_scheduled.addClass(Y.ClassNameManager.getClassName('button', 'multiline'));
			
			//Bind events
			this.button_scheduled.on('mouseover', function () {
				if (this.page_version_data) this.showPreview(this.page_version_data.scheduled, this.button_scheduled.get('contentBox'));
			}, this);
			
			this.button_scheduled.on('mouseout', function () {
				this.hidePreview();
			}, this);
			
			this.button_version.on('mouseover', function () {
				if (this.page_version_data) this.showPreview(this.page_version_data.latest, this.button_version.get('contentBox'));
			}, this);
			
			this.button_version.on('mouseout', function () {
				this.hidePreview();
			}, this);
			
			this.button_newpage.on('click', function () {
				this.hidePreview();
				this.fire('click:pagenew');
				SU.Manager.executeAction('PageNew', this.button_newpage.get('contentBox'));
			}, this);
			
			this.button_scheduled.on('click', function () {
				var data = SU.mix({}, this.page_data, this.page_version_data.scheduled);
				this.fire('versionClick', {'data': data});
			}, this);
			this.button_version.on('click', function () {
				var data = SU.mix({}, this.page_data, this.page_version_data.latest);
				this.fire('versionClick', {'data': data});
			}, this);
			
			//When PageInfo is hidden, then also hide PagePreview and PageNew
			this.addChildAction('PagePreview');
			this.addChildAction('PageNew');
		},
		
		/**
		 * Hide new page popup
		 * @private
		 */
		hideNewPage: function () {
			SU.Manager.getAction('PageNew').hide();
		},
		
		/**
		 * Hide page preview
		 * @private
		 */
		hidePreview: function () {
			SU.Manager.getAction('PagePreview').hide();
		},
		
		/**
		 * Show page preview
		 * 
		 * @param {Object} data Page version data
		 * @param {Object} node Button node
		 * @private
		 */
		showPreview: function (data, node) {
			//Show only if new page popup is not visible
			if (!SU.Manager.getAction('PageNew').get('visible')) {
				SU.Manager.executeAction('PagePreview', data.preview_url, node);
			}
		},
		
		/**
		 * When data is loaded update button labels and show/hide
		 * scheduled button 
		 * 
		 * @param {Number} transaction Transaction id
		 * @param {Object} data JSON data received from server
		 * @private
		 */
		_loadingComplete: function (transaction, data) {
			
			delete(this.page_version_data);
			
			this.page_version_data = data;
			
			if (data) {
				if ('latest' in data && data.latest) {
					this.button_version.set('label', this.LOCALE[data.latest.type]);
				} else {
					this.button_version.hide();
				}
				
				if ('scheduled' in data && data.scheduled) {
					var button = this.button_scheduled;
					if (data.scheduled.type == 'publish') {
						//Change button label
						button.show();
						button.set('label', this.LOCALE.publish);
						button.get('boundingBox').one('span').set('innerHTML', data.scheduled.date);
						
					} else if (data.scheduled.type == 'unpublish') {
						//Change button label
						button.show();
						button.set('label', this.LOCALE.unpublish);
						button.get('boundingBox').one('span').set('innerHTML', data.scheduled.date);
						
					} else {
						button.hide();
					}
				} else {
					this.button_scheduled.hide();
				}
			}
		},
		
		/**
		 * Adjust panel position
		 * 
		 * @param {Object} data Page data
		 * @private
		 */
		_setPanelPosition: function (data, node) {
			//Align with link	
    		this.panel.set('align', {'node': node, 'points': [Y.WidgetPositionAlign.LC, Y.WidgetPositionAlign.LC]});
			
			//Left position is fixed, overwrite "align" settings
			var xy = this.panel.get('xy');
				xy[0] = 320;
			
			//Change style
			this.panel.setAttrs({
				'xy': xy,
				'arrowAlign': node
			});
		},
		
		/**
		 * Execute action
		 */
		execute: function (data, node) {
			//Save page data for later use
			delete(this.page_data);
			this.page_data = data;
			
			//Load page version data
			setTimeout(Y.bind(function () {
				SU.io(this.getDataPath(), Y.bind(this._loadingComplete, this));
			}, this), 1);
			
			//Align panel to the middle of the selected node
			this._setPanelPosition(data, node);
			
			//Hide new page popup
			this.hideNewPage();
		}
		
	});
	
});
