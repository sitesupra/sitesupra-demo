YUI.add('supra.manager-action-plugin-layout-sidebar', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	function PluginSidebar () {
		PluginSidebar.superclass.constructor.apply(this, arguments);
	};
	
	PluginSidebar.NAME = 'PluginSidebar';
	
	Y.extend(PluginSidebar, Action.PluginBase, {
		
		/**
		 * Content scroller, Supra.Scrollable instance
		 * @type {Object}
		 */
		scrollable: null,
		
		
		/**
		 * Set as sidebar child
		 */
		create: function () {
			//Add as right bar child
			if (this.host.LAYOUT_CONTAINER) {
				Manager.getAction(this.host.LAYOUT_CONTAINER).addChildAction(this.host.NAME);
			}
		},
		
		/**
		 * Initialize plugin
		 */
		initialize: function () {
			var header = null,
				node = null;
			
			//Header
			header = this.host.one('.sidebar-header');
			this.host.addAttr('headerNode', {
				'value': header
			});
			this.host.addAttr('headerVisible', {
				'value': header && !header.hasClass('hidden'),
				'setter': Y.bind(this.setHeaderVisible, this)
			});
			
			//Buttons
			node = header.one('.button-back');
			this.host.addAttr('backButton', {
				'value': node ? new Supra.Button({
									'srcNode': node,
									'style': 'small-gray',
									'visible': !node.hasClass('hidden')
								}) : null
			});
			
			node = header.one('.button-control');
			this.host.addAttr('controlButton', {
				'value': node ? new Supra.Button({
									'srcNode': node,
									'style': 'small-blue',
									'visible': !node.hasClass('hidden')
								}) : null
			});
			
			//Icon
			node = header.one('img');
			this.host.addAttr('iconNode', {
				'value': node
			});
			this.host.addAttr('icon', {
				'value': node.getAttribute('src'),
				'setter': Y.bind(this.setIcon, this)
			});
			
			//Title
			node = header.one('h2');
			this.host.addAttr('titleNode', {
				'value': node
			});
			this.host.addAttr('title', {
				'value': node.get('text'),
				'setter': Y.bind(this.setTitle, this)
			});
			
			//Content
			node = this.host.one('.sidebar-content');
			this.host.addAttr('contentNode', {
				'value': node
			});
			
			this.host.addAttr('contentInnerNode', {
				'value': null,
				'getter': Y.bind(this.getContentInnerNode, this)
			});
			
			//Scrollable
			this.host.addAttr('scrollable', {
				'value': node.hasClass('scrollable'),
				'setter': Y.bind(this.setScrollable, this)
			});
			
			if (this.host.get('scrollable')) {
				this.setScrollable(true);
			}
			
			//Slideshow
			this.host.addAttr('slideshow', {
				'value': false,
				'setter': Y.bind(this.setSlideshow, this)
			});
			
			// Back button event listener
			var back = this.host.get('backButton');
			if (back) {
				back.hide();
				back.after('click', this.onBackButtonClick, this);
			}
			
			/*
			 * In frozen state if sidebar is hidden then toolbar buttons
			 * will not be removed and "hide" function is not called,
			 * on action execute "execute" function will not be called either
			 */
			this.host.addAttr('frozen', {
				'value': this.host.get('frozen') || false
			});
			
			this.host._frozenExecute = this.host.execute;
			this.host._frozenHide = this.host.hide;
			
			this.host.execute = this.executeHost;
			this.host.hide = this.hideHost;
			this.host.showFrozen = this.showFrozenHost;
			
			this.host.after('visibleChange', this.afterVisibleChange, this);
		},
		
		
		/* ------------------------------ Slideshow ------------------------------ */
		
		
		/**
		 * Slideshow attribute setter
		 * 
		 * @param {Object} slideshow Slideshow object or null
		 * @returns {Object} New attribute value
		 * @private
		 */
		setSlideshow: function (slideshow) {
			var backButton = this.host.get('backButton'),
				old_slideshow = this.host.get('slideshow');
			
			if (old_slideshow !== slideshow) {
				if (old_slideshow) {
					old_slideshow.detach('slideChange', this.onSlideshowSlideChange, this);
				}
				if (slideshow) {
					slideshow.on('slideChange', this.onSlideshowSlideChange, this);
				}
				
				if (backButton) {
					if (!slideshow || slideshow.isRootSlide()) {
						backButton.hide();
					} else {
						backButton.show();
					}
				}
			}
			
			return slideshow;
		},
		
		/**
		 * Handle back button click
		 */
		onBackButtonClick: function (event) {
			var slideshow = this.host.get('slideshow');
			if (slideshow && !event.stoped && !event.prevented) {
				slideshow.scrollBack();
			}
		},
		
		/**
		 * On slideshow slide change show or hide back button
		 */
		onSlideshowSlideChange: function (evt) {
			var slideshow = this.host.get('slideshow'),
				history = slideshow.getHistory();
			
			if (!slideshow.isRootSlide(evt.newVal)) {
				// Root slide
				var button = this.host.get('backButton');
				if (button) button.show();
			} else {
				// Not a root slide
				var button = this.host.get('backButton');
				if (button) button.hide();
			}
		},
		
		/* ------------------------------ Attributes ------------------------------ */
		
		
		/**
		 * Set header visibility
		 * 
		 * @param {Boolean} value Visibility state
		 * @return New visibility state
		 * @type {Boolean}
		 */
		setHeaderVisible: function (value) {
			var node = this.host.one('headerNode'),
				cont = this.host.get('contentNode');
			
			if (value && node) {
				node.removeClass('hidden');
				if (cont) cont.addClass('has-header');
			} else {
				if (node) node.addClass('hidden');
				if (cont) cont.removeClass('has-header');
				value = false;
			}
			
			return !!value;
		},
		
		/**
		 * Set header icon
		 * 
		 * @param {String} path Path to icon
		 * @return New icon path
		 * @type {String}
		 */
		setIcon: function (path) {
			var node = this.host.get('iconNode');
			
			if (node) {
				if (path) {
					node.setAttribute('src', path);
					node.removeClass('hidden');
				} else {
					node.addClass('hidden');
				}
				
				return path;
			}
			
			return null;
		},
		
		/**
		 * Set sidebar title
		 * 
		 * @param {String} title Title
		 * @return New title
		 * @type {String}
		 */
		setTitle: function (title) {
			var node = this.host.get('titleNode');
			
			if (node) {
				node.set('text', title);
			}
			
			return title;
		},
		
		/**
		 * Set if content node is scrollable
		 * 
		 * @param {Boolean} scrollable
		 * @return True if content is scrollable, otherwise false
		 * @type {Boolean}
		 */
		setScrollable: function (scrollable) {
			var node = this.host.get('contentNode');
			
			if (scrollable) {
				node.addClass('scrollable');
				if (!this.scrollable) {
					var children = node.get('children');
					this.scrollable = new Supra.Scrollable();
					this.scrollable.render(node);
					this.scrollable.get('contentBox').append(children);
				} else {
					this.scrollable.set('disabled', false);
				}
			} else {
				node.removeClass('scrollable');
				
				if (this.scrollable) {
					this.scrollable.set('disabled', true);
				}
			}
			
			return !!scrollable;
		},
		
		/**
		 * Returns inner content node inside which content should be added
		 * 
		 * @return Content inner node
		 * @type {Boolean}
		 */
		getContentInnerNode: function () {
			if (this.scrollable) {
				return this.scrollable.get('contentBox');
			} else {
				return this.host.get('contentNode');
			}
		},
		
		/**
		 * On visibility change show/hide toolbar buttons
		 */
		afterVisibleChange: function (evt) {
			if (evt.newVal != evt.prevVal) {
				var toolbar = Manager.getAction('PageToolbar'),
					buttons = Manager.getAction('PageButtons'),
					container = this.host.one();
				
				if (evt.newVal) {
					//Show container
					if (container) container.removeClass('hidden');
					
					//Show action
					if (this.host.LAYOUT_CONTAINER) {
						Manager.getAction(this.host.LAYOUT_CONTAINER).setActiveAction(this.host.NAME);
					}
					
					//Show buttons
					if (toolbar.hasActionButtons(this.host.NAME)) {
						if (this.host.PLUGIN_LAYOUT_SIDEBAR_MANAGE_BUTTONS !== false) {
							toolbar.setActiveAction(this.host.NAME);
							buttons.setActiveAction(this.host.NAME);
						}
					}
					
					//Event
					this.host.fire('show');
				} else {
					if (!this.host.get('frozen')) {
						//Hide buttons
						if (toolbar.hasActionButtons(this.host.NAME)) {
							if (this.host.PLUGIN_LAYOUT_SIDEBAR_MANAGE_BUTTONS !== false) {
								toolbar.unsetActiveAction(this.host.NAME);
								buttons.unsetActiveAction(this.host.NAME);
							}
						}
					}
					
					//Hide action
					if (this.host.LAYOUT_CONTAINER) {
						Manager.getAction(this.host.LAYOUT_CONTAINER).unsetActiveAction(this.host.NAME);
					}
					
					//Hide container
					if (container) container.addClass('hidden');
					
					//Event
					this.host.fire('hide');				
				}
				
			}
		},
		
		/**
		 * Render
		 */
		render: function () {
			PluginSidebar.superclass.render.apply(this, arguments);
			
			//Render buttons
			var button = this.host.get('backButton');
			if (button) button.render();
			
			button = this.host.get('controlButton');
			if (button) button.render();
		},
		
		/**
		 * Execute
		 */
		execute: function () {
			PluginSidebar.superclass.execute.apply(this, arguments);
			this.host.show();
		},
		
		
		/**
		 * Function which overwrites hosts "execute" to prevent
		 * calling it in frozen state
		 */
		executeHost: function () {
			if (!this.get('frozen')) {
				this._frozenExecute.apply(this, arguments);
			}
		},
		
		/**
		 * Function which overwrites hosts "hide" to prevent
		 * calling it in frozen state
		 */
		hideHost: function () {
			if (!this.get('frozen')) {
				this._frozenHide.apply(this, arguments);
			}
		},
		
		/**
		 * Force showing content even in frozen mode
		 */
		showFrozenHost: function () {
			if (this.get('visible')) {
				this.plugins.getPlugin('PluginSidebar').afterVisibleChange({'newVal': true, 'prevVal': false});
			} else {
				this.show();
			}
		}
		
	});
	
	Action.PluginLayoutSidebar = PluginSidebar;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base', 'supra.input', 'supra.scrollable']});