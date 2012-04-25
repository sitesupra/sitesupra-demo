//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
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
		NAME: 'DesignList',
		
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
		 * Scrollable object
		 * @type {Object}
		 * @private
		 */
		scrollable: null,
		
		/**
		 * Design items
		 * @type {Y.NodeList}
		 * @private
		 */
		items: null,
		
		/**
		 * Design data
		 * @type {Array}
		 * @private
		 */
		data: null,
		
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
		 * Loading icon
		 * @type {Object}
		 * @private
		 */
		nodeLoading: null,
		
		/**
		 * Fade overlay
		 * @type {Object}
		 * @private
		 */
		nodeFadeOverlay: null,
		
		
		/**
		 * Set place holder node
		 */
		create: function () {
			this.set('placeHolderNode', Y.one('#designList'));
			
			this.addAttrs({
				'disabled': {
					'value': false,
					'setter': 'setDisabled'
				}
			});
		},
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Arrows
			var next = this.nodeArrowNext = this.one('.arrow.next');
			var prev = this.nodeArrowPrev = this.one('.arrow.prev');
			
			next.setStyle('opacity', 0);
			next.plug(Y.Plugin.NodeFX, {'from': {'opacity': 0}, 'to': {'opacity': 1}, 'duration': 0.35});
			
			prev.setStyle('opacity', 0);
			prev.plug(Y.Plugin.NodeFX, {'from': {'opacity': 0}, 'to': {'opacity': 1}, 'duration': 0.35});
			
			next.on('mousedown', this.scrollNext, this);
			prev.on('mousedown', this.scrollPrevious, this);
			
			//Set default buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Load data
			Supra.io(this.getDataPath('dev/list'), this.setup, this);
			
			//On item click open it
			this.one('.designs ul').delegate('click', this.onItemClick, 'a', this);
			
			//Fade overlay
			this.nodeFadeOverlay = Y.one('div.fade-overlay');
			
			//On visibility change show/hide container
			this.on('visibleChange', function (evt) {
				var node = this.one().ancestor();
				if (node && evt.newVal != evt.prevVal) {
					node.setClass('hidden', !evt.newVal);
					
					if (evt.newVal) {
						this.fire('show');
					} else {
						this.fire('hide');
					}
				}
			});
		},
		
		/**
		 * On item click open design overview
		 */
		onItemClick: function (e) {
			//Don't do anything if already loading
			if (this.get('disabled')) return;
			
			var target = e.target,
				id = target.getAttribute('data-id');
			
			//Loading style
			this.setLoading(id);
			
			//Globals
			Supra.data.set('design', id);
			Y.Global.fire('designChange', {'id': id});
			
			//Open overview
			Manager.executeAction('DesignOverview');
			Manager.executeAction('DesignBar');
			
			//Fade out
			this.fadeOut(target);
		},
		
		/**
		 * Set up
		 */
		setup: function (data, status) {
			Y.one('body').removeClass('loading');
			
			//Create scrollable
			this.scrollable = new Supra.Scrollable({
				'srcNode': this.one('.designs'),
				'axis': 'x'
			});
			
			this.scrollable.render(this.one());
			
			//Render template list
			this.renderDesignList(data, status);
			
			//Start loading other actions
			Manager.loadActions(['DesignOverview', 'DesignBar']);
		},
		
		/**
		 * Render design list
		 */
		renderDesignList: function (data, status) {
			var template = Supra.Template('designTemplate'),
				container = this.one('.designs ul'),
				html = '';
			
			this.data = data;
			if (!data.length) {
				return;
			}
			
			//Render
			container.setStyle('width', Math.ceil(data.length / 2) / 2 * 100 + '%');
			
			html = template({
				'designs': data,
				'size': data.length ? (100 / Math.ceil(data.length / 2)) : 100
			});
			
			container.set('innerHTML', html);
			
			this.items = container.all('li');
			
			//Update scrollable
			this.scrollable.on('sync', this.updateArrows, this);
			this.scrollable.syncUI();
		},
		
		/**
		 * Scroll forward
		 * 
		 * @param {Event} e Event facade object
		 */
		scrollNext: function (e) {
			//If called by mouse down, then validate button
			if (e && typeof e.button == 'number' && e.button != 1) return;
			
			if (this.nodeArrowNext.fx.get('running')) return;
			
			var scrollPos = this.scrollable.getScrollPosition(),
				itemWidth = this.items.item(0).get('offsetWidth'),
				index     = Math.ceil(scrollPos / itemWidth),
				max       = Math.ceil(this.data.length / 2),
				scrollTo  = index * itemWidth;
			
			if (index > max) return;
			if (scrollTo == scrollPos) scrollTo += itemWidth;
			
			this.scrollable.animateTo(scrollTo);
			this.updateArrows(scrollTo);
		},
		
		/**
		 * Scroll back
		 * 
		 * @param {Event} e Event facade object
		 */
		scrollPrevious: function (e) {
			//If called by mouse down, then validate button
			if (e && typeof e.button == 'number' && e.button != 1) return;
			
			if (this.nodeArrowPrev.fx.get('running')) return;
			
			var scrollPos = this.scrollable.getScrollPosition(),
				itemWidth = this.items.item(0).get('offsetWidth'),
				index     = Math.floor(scrollPos / itemWidth),
				scrollTo  = index * itemWidth;
			
			if (index < 0) return;
			if (scrollTo == scrollPos) scrollTo -= itemWidth;
			
			this.scrollable.animateTo(scrollTo);
			this.updateArrows(scrollTo);
		},
		
		/**
		 * Update arrow visibility
		 * 
		 * @private
		 */
		updateArrows: function (pos) {
			var scrollPos = null,
				max       = Math.ceil(this.data.length / 2) - 2,
				itemWidth = this.items.item(0).get('offsetWidth'),
				
				arrowNext = this.nodeArrowNext,
				arrowPrev = this.nodeArrowPrev;
			
			if (typeof pos === 'number') {
				scrollPos = pos;
			} else {
				scrollPos = this.scrollable.getScrollPosition();
			}
			
			if (scrollPos > 0) {
				if (!this.nodeArrowPrevVisible) {
					arrowPrev.removeClass('hidden');
					arrowPrev.fx.set('reverse', false).run();
					this.nodeArrowPrevVisible = true;
				}
			} else {
				if (this.nodeArrowPrevVisible) {
					arrowPrev.fx.once('end', function () { this.addClass('hidden'); }, arrowPrev);
					arrowPrev.fx.set('reverse', true).run();
					this.nodeArrowPrevVisible = false;
				}
			}
			
			if (scrollPos < max * itemWidth - 10) {
				if (!this.nodeArrowNextVisible) {
					arrowNext.removeClass('hidden');
					arrowNext.fx.set('reverse', false).run();
					this.nodeArrowNextVisible = true;
				}
			} else {
				if (this.nodeArrowNextVisible) {
					arrowNext.fx.once('end', function () { this.addClass('hidden'); }, arrowNext);
					arrowNext.fx.set('reverse', true).run();
					this.nodeArrowNextVisible = false;
				}
			}
		},
		
		/**
		 * Disable or enable action
		 * 
		 * @param {Boolean} value New disabled state
		 * @return New disabled state
		 * @type {Boolean}
		 * @private
		 */
		setDisabled: function (value) {
			if (!this.get('created')) return !!value;
			
			if (value) {
				this.one().addClass('disabled');
			} else {
				this.one().removeClass('disabled');
			}
			
			return !!value;
		},
		
		/**
		 * Fade out
		 */
		fadeOut: function (target) {
			if (target && target.isInstanceOf) {
				var region	= target.get('region'),
					node	= this.nodeFadeOverlay,
					content	= Supra.Manager.Root.one().get('region');
				
				node.setStyles({
					'left': region.left,
					'top': region.top - 48,
					'width': region.width,
					'height': region.height,
					'opacity': 0.2,
					'display': 'block'
				});
				
				node.transition({
					'left': 0,
					'top': 0,
					'width': content.width + 'px',
					'height': content.height + 'px',	//175 == design selector
					'opacity': 1,
					'duration': 0.5
				}, Y.bind(function () {
					this.nodeFadeOverlay.setStyle('display', 'none');
					this.set('visible', false);
				}, this));
				
			} else {
				this.hide();
			}
		},
		
		/**
		 * Fade out
		 */
		fadeIn: function (id) {
			var target = this.one('a[data-id="' + id + '"]');
			
			this.set('visible', true);
			
			if (target) {
				var region	= target.get('region'),
					node	= this.nodeFadeOverlay;
				
				node.setStyles({
					'display': 'block'
				});
				
				node.transition({
					'left': region.left + 'px',
					'top': region.top - 48 + 'px',
					'width': region.width + 'px',
					'height': region.height + 'px',
					'opacity': 0.2,
					'duration': 0.5
				}, Y.bind(function () {
					this.nodeFadeOverlay.setStyle('display', 'none');
				}, this));
				
			}
		},
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		
		/**
		 * Set loading icon on item
		 * 
		 * @param {String} id Design ID
		 */
		setLoading: function (id) {
			var nodeNew = null,
				nodePrev = null,
				nodeLoading = this.nodeLoading;
			
			if (nodeLoading) {
				nodePrev = nodeLoading.ancestor();
				if (nodePrev) {
					nodePrev.removeClass('loading');
				}
			}
			
			if (id) {
				nodeNew = this.one('a[data-id="' + id + '"]');
				if (nodeNew) {
					
					if (!this.nodeLoading) {
						this.nodeLoading = nodeLoading = Y.Node.create('<div class="loading-icon"></div>');
					}
					
					nodeNew = nodeNew.ancestor();
					nodeNew.append(nodeLoading);
					nodeNew.addClass('loading');
					
					this.set('disabled', true);
					
					return;
				}
			}
			
			if (nodeLoading) {
				nodeLoading.remove();
				this.set('disabled', false);
			}
		},
		
		/**
		 * Returns design list data
		 * 
		 * @return Array of design list data
		 * @type {Array}
		 */
		getData: function () {
			return this.data;
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			Manager.getAction('Root').slide('designList');
		}
	});
	
});