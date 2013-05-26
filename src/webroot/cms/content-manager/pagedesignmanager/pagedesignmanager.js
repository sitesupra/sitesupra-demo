Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Set 'design' module path
	Supra.setModuleGroupPath('design', Manager.Loader.getStaticPath() + Manager.Loader.getActionBasePath('DesignCustomize') + '/modules');
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'PageDesignManager',
		
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
		 * Design manager options
		 * @type {Object}
		 * @private
		 */
		options: {},
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
		},
		
		/**
		 * 
		 */
		onDesignCustomizeClose: function (changed) {
			if (changed) {
				Supra.Manager.Page.reloadPage();
			}
			
			this.hide();
		},
		
		/*
		 * ---------------------------------- SHOW/HIDE USING ANIMATION ------------------------------------
		 */
		
		
		show: function () {
			if (!this.get('visible')) {
				this.set('layoutDisabled', true);
				this.set('visible', true);
				this.animateIn();
			}
		},
		
		hide: function () {
			if (this.get('visible')) {
				this.set('layoutDisabled', true);
				this.animateOut();
			}
		},
		
		animateIn: function () {
			var node = this.one(),
				width = Y.DOM.viewportRegion().width;
			
			if (Supra.Y.Transition.useNative) {
				// Use CSS transforms + transition
				node.addClass('hidden');
				node.setStyle('transform', 'translate(' + width + 'px, 0px)');
				
				Y.later(1, this, function () {
					// Only now remove hidden to prevent unneeded animation
					node.removeClass('hidden');
				});
				
				// Use CSS animation
				Y.later(32, this, function () {
					// Animate
					node.setStyle('transform', 'translate(0px, 0px)');
					
					Y.later(500, this, this.afterAnimateIn);
				});
			} else {
				node.removeClass('hidden');
				
				// Fallback for IE9
				// Update styles to allow 'left' animation
				node.setStyles({
					'width': width,
					'right': 'auto',
					'left': '100%'
				});
				
				// Animate position
				node.transition({
					'duration': 0.5,
					'left': '0%'
				}, Y.bind(function () {
					node.setStyles({
						'width': 'auto',
						'left': '0px',
						'right': '0px'
					});
					
					// Animation completed, show UI elements
					this.afterAnimateIn();
				}, this));
			}
		},
		
		animateOut: function () {
			var node = this.one(),
				width = Y.DOM.viewportRegion().width;
			
			if (Supra.Y.Transition.useNative) {
				// Use CSS transforms + transition
				node.setStyle('transform', 'translate(' + width + 'px, 0px)');
				Y.later(350, this, this.afterAnimateOut);
			} else {
				// Update styles to allow 'left' animation
				// IE9 fallback
				node.setStyles({
					'width': width,
					'right': 'auto',
					'left': '0%'
				});
				
				// Animate position
				node.transition({
					'duration': 0.5,
					'left': '100%'
				}, Y.bind(this.afterAnimateOut, this));
			}
		},
		
		/**
		 * After animation in, show actual design manager
		 */
		afterAnimateIn: function () {
			// Enable auto layout management
			this.set('layoutDisabled', false);
			
			// Open actual customization manager
			Supra.Manager.executeAction('DesignCustomize', {
				'callback': Y.bind(this.onDesignCustomizeClose, this)
			});
		},
		
		/**
		 * After animation out
		 */
		afterAnimateOut: function () {
			this.one().addClass('hidden');
			this.set('visible', false);
					
			// Enable auto layout management
			this.set('layoutDisabled', false);
		},
		
		
		/*
		 * ---------------------------------- OPEN/SAVE/CLOSE ------------------------------------
		 */
		
		
		/**
		 * Normalize options
		 * 
		 * @param {Object} options
		 * @returns {Object} Normalized options
		 * @private
		 */
		normalizeOptions: function (options) {
			return Supra.mix({
			}, options || {});
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} options Slideshow options: data, callback, context, block
		 */
		execute: function (options) {
			this.options = options = this.normalizeOptions(options);
			this.show();
		}
		
	});
	
});
