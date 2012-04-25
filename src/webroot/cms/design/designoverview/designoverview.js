//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.imageslider', {
	path: 'imageslider.js',
	requires: ['widget', 'transition']
});

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	'website.imageslider',
	
function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'DesignOverview',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['DesignBar'],
		
		
		
		/**
		 * Design data
		 * @type {Array}
		 * @private
		 */
		data: null,
		
		/**
		 * Template
		 * @type {Function}
		 * @private
		 */
		contentTemplate: null,
		
		/**
		 * Description Supra.Scrollable instance
		 * @type {Object}
		 * @private
		 */
		scrollableDescription: null,
		
		/**
		 * Supra.ImageSlider instance
		 * @type {Object}
		 * @private
		 */
		imageSlider: null,
		
		/**
		 * Arrows
		 * @type {Object}
		 * @private
		 */
		nodeArrowNext: null,
		nodeArrowNextVisible: false,
		nodeArrowPrev: null,
		nodeArrowPrevVisible: false,
		
		
		
		/**
		 * Set place holder node
		 */
		create: function () {
			this.set('placeHolderNode', Y.one('#designOverview'));
		},
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Set default buttons
			var icon_path = Manager.Loader.getStaticPath() + Manager.Loader.getBasePath() + '/images/toolbar/';
			
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [
				{
					'id': 'overview',
					'title': Supra.Intl.get(['design', 'toolbar', 'overview']),
					'icon': icon_path + 'icon-overview.png',
					'action': 'DesignOverview',
					'type': 'tab'
				},
				{
					'id': 'themes',
					'title': Supra.Intl.get(['design', 'toolbar', 'themes']),
					'icon': icon_path + 'icon-themes.png',
					'action': 'Themes',
					'type': 'tab'
				},
				{
					'id': 'preview',
					'title': Supra.Intl.get(['design', 'toolbar', 'preview']),
					'icon': icon_path + 'icon-preview.png',
					'action': 'Preview',
					'type': 'tab'
				},
				{
					'id': 'customize',
					'title': Supra.Intl.get(['design', 'toolbar', 'customize']),
					'icon': icon_path + 'icon-customize.png',
					'action': 'Customize',
					'type': 'button'
				}
			]);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'back',
				'label': Supra.Intl.get(['design', 'toolbar', 'back']),
				'context': this,
				'callback': this.back
			}, {
				'id': 'select',
				'label': Supra.Intl.get(['design', 'toolbar', 'select']),
				'context': this,
				'callback': this.close,
				'style': 'mid-blue'
			}]);
		},
		
		/**
		 * Render widgets, etc.
		 */
		render: function () {
			//Arrows
			var next = this.nodeArrowNext = this.one('.arrow.next');
			var prev = this.nodeArrowPrev = this.one('.arrow.prev');
			
			next.setStyle('opacity', 0);
			prev.setStyle('opacity', 0);
			
			next.on('mousedown', this.scrollPreviewNext, this);
			prev.on('mousedown', this.scrollPreviewPrevious, this);
			
			//On design change reload data
			Y.Global.on('designChange', function () {
				if (this.data) this.load();
			}, this);
			
			//On visibility change enable/disable image slider
			this.on('visibleChange', function (e) {
				if (e.newVal != e.prevVal) {
					if (this.imageSlider) this.imageSlider.set('disabled', !e.newVal);
				}
			});
		},
		
		/**
		 * Set up
		 */
		setup: function (data, status) {
			
			
			//Update main slideshow
			Manager.getAction('Root').sync();
		},
		
		
		/*
		 * ------------------------------- TEMPLATE --------------------------------
		 */
		
		
		/**
		 * Load design data
		 * 
		 * @private
		 */
		load: function () {
			var id = Supra.data.get('design');
			
			//XHR
			Supra.io(this.getDataPath('dev/load'), {
				'data': {
					'id': id
				},
				'context': this,
				'on': {
					'success': this.loadComplete,
					'failure': this.back
				}
			});
			
			//Set loading icon
			Supra.Manager.getAction('DesignBar').setLoading(id);
		},
		
		/**
		 * On load complete fill HTML with data
		 * 
		 * @param {Object} data Design data
		 * @param {Number} status Response status
		 * @private
		 */
		loadComplete: function (data, status) {
			this.data = data;
			
			//Template
			var template = this.contentTemplate;
			if (!template) {
				template = this.contentTemplate = Supra.Template('overviewTemplate');
			}
			
			this.one('div.content').set('innerHTML', template(data));
			
			//Scrollable
			if (this.scrollableDescription) {
				this.scrollableDescription.destroy();
				this.scrollableDescription = null;
			}
			
			var scrollable = this.scrollableDescription = new Supra.Scrollable({
				'srcNode': this.one('div.text div')
			});
			
			scrollable.render();
			scrollable.syncUI();
			
			//Image slider
			if (this.imageSlider) {
				this.imageSlider.destroy();
			}
			
			var slider = this.imageSlider = new Supra.ImageSlider({
				'srcNode': this.one('div.preview'),
				'images': data.images,
				'width': 'auto',
				'height': 'auto'
			});
			
			slider.render();
			slider.syncUI();
			
			slider.after('imageChange', this.updateArrows, this);
			this.updateArrows();
			
			//Remove loading icon
			Supra.Manager.getAction('DesignBar').setLoading(null);
			Supra.Manager.getAction('DesignList').setLoading(null);
		},
		
		/**
		 * Update arrow visibility
		 * 
		 * @private
		 */
		updateArrows: function () {
			var index = this.imageSlider.get('image'),
				count = this.imageSlider.get('images').length;
			
			if (index > 0) {
				if (!this.nodeArrowPrevVisible) {
					this.nodeArrowPrev.removeClass('hidden').setStyle('opacity', 0).transition({'duration': 0.5, 'opacity': 1});
					this.nodeArrowPrevVisible = true;
				}
			} else {
				if (this.nodeArrowPrevVisible) {
					this.nodeArrowPrev.transition({'duration': 0.5, 'opacity': 0}, this.updateArrowsHide);
					this.nodeArrowPrevVisible = false;
				}
			}
			
			if (index < count - 1) {
				if (!this.nodeArrowNextVisible) {
					this.nodeArrowNext.removeClass('hidden').setStyle('opacity', 0).transition({'duration': 0.5, 'opacity': 1});
					this.nodeArrowNextVisible = true;
				}
			} else {
				if (this.nodeArrowNextVisible) {
					this.nodeArrowNext.transition({'duration': 0.5, 'opacity': 0}, this.updateArrowsHide);
					this.nodeArrowNextVisible = false;
				}
			}
		},
		
		/**
		 * Hide arrow after transition
		 * Function execution context is arrow node which was animated
		 * 
		 * @private
		 */
		updateArrowsHide: function () {
			this.addClass('hidden');
		},
		
		
		/*
		 * ------------------------------- IMAGE LIST --------------------------------
		 */
		
		
		/**
		 * Scroll forward
		 * 
		 * @param {Event} e Event facade object
		 */
		scrollPreviewNext: function (e) {
			//If called by mouse down, then validate button
			if (e && typeof e.button == 'number' && e.button != 1) return;
			
			if (this.imageSlider) {
				this.imageSlider.nextImage();
			}
		},
		
		/**
		 * Scroll back
		 * 
		 * @param {Event} e Event facade object
		 */
		scrollPreviewPrevious: function (e) {
			//If called by mouse down, then validate button
			if (e && typeof e.button == 'number' && e.button != 1) return;
			
			if (this.imageSlider) {
				this.imageSlider.previousImage();
			}
		},
		
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		
		/**
		 * Returns design data
		 * 
		 * @return Design data
		 * @type {Object}
		 */
		getData: function () {
			return this.data;
		},
		
		/**
		 * Open design list
		 */
		back: function () {
			Manager.executeAction('DesignList');
			Manager.getAction('DesignList').fadeIn(this.data.id);
			Manager.getAction('DesignBar').hide();
			
			this.data = null;
		},
		
		/**
		 * Close design application
		 */
		close: function () {
			//@TODO
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			Manager.getAction('Root').slide('designOverview');
			
			if (!this.data) {
				this.load();
			}
		}
	});
	
});