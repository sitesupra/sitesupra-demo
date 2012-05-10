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
	new Action(Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'DesignBar',
		
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
		 * Set place holder node
		 */
		create: function () {
			this.set('placeHolderNode', Y.one('div.design'));
			
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
			
			//Render design list
			this.setup();
			
			//On item click open it
			this.one('.designs ul').delegate('click', this.onItemClick, 'a', this);
			
			//When hidden remove loading icon
			this.on('visibleChange', function (e) {
				if (e.newVal != e.prevVal && !e.newVal) {
					this.setLoading(null);
				}
			}, this);
		},
		
		/**
		 * On item click open design overview
		 */
		onItemClick: function (e) {
			if (this.get('disabled')) return;
			
			var target = e.target,
				id = target.getAttribute('data-id');
			
			//Globals
			Supra.data.set('design', id);
			Y.Global.fire('designChange', {'id': id});
			
			//Open overview
			Manager.executeAction('DesignOverview');
		},
		
		/**
		 * Set up
		 */
		setup: function (data, status) {
			//Create scrollable
			this.scrollable = new Supra.Scrollable({
				'srcNode': this.one('.designs'),
				'axis': 'x'
			});
			
			this.scrollable.render(this.one());
			
			//Render template list
			this.renderDesignList(this.getData());
		},
		
		/**
		 * Render design list
		 */
		renderDesignList: function (data) {
			var template = Supra.Template('designBarTemplate'),
				container = this.one('.designs ul'),
				html = '';
			
			this.data = data;
			if (!data.length) {
				return;
			}
			
			//Render
			//218 - item width, 57 - left and right margins for first and last items
			container.setStyle('width', 218 * data.length + 57 * 2 + 'px');
			
			html = template({
				'designs': data
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
		 * @private
		 */
		scrollNext: function (e) {
			//If called by mouse down, then validate button
			if (e && typeof e.button == 'number' && e.button != 1) return;
			
			var scrollPos = this.scrollable.getScrollPosition(),
				maxPos    = this.scrollable.getMaxScrollPosition(),
				viewSize  = this.scrollable.getViewSize(),
				itemWidth = 218,
				margin    = 57,
				
				itemPos   = null,
				scrollTo  = null;
			
			//If already at max, then there is nowhere to scroll
			if (scrollPos == maxPos) return;
			
			//Find position of item to which we want to scroll to
			itemPos = Math.ceil((viewSize + scrollPos - margin) / itemWidth) * itemWidth + margin;
			
			scrollTo = itemPos - viewSize + margin; // margin is to adjust so that item is not behind arrows
			
			//If difference between target and now is too small then scroll by 1 item more
			if (scrollTo - scrollPos < itemWidth / 2) scrollTo += itemWidth;
			
			//Validate
			scrollTo = Math.min(maxPos, scrollTo);
			
			this.scrollable.animateTo(scrollTo);
			this.updateArrows(scrollTo);
		},
		
		/**
		 * Scroll back
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		scrollPrevious: function (e) {
			//If called by mouse down, then validate button
			if (e && typeof e.button == 'number' && e.button != 1) return;
			
			var scrollPos = this.scrollable.getScrollPosition(),
				viewSize  = this.scrollable.getViewSize(),
				itemWidth = 218,
				margin    = 57,
				
				itemPos   = null,
				scrollTo  = null;
			
			//If already at min, then there is nowhere to scroll
			if (scrollPos == 0) return;
			
			//Find position of item to which we want to scroll to
			itemPos = Math.floor((scrollPos - margin) / itemWidth) * itemWidth + margin;
			
			scrollTo = itemPos - margin; // margin is to adjust so that item is not behind arrows
			
			//If difference between target and now is too small then scroll by 1 item more
			if (scrollPos - scrollTo < itemWidth / 2) scrollTo -= itemWidth;
			
			//Validate
			scrollTo = Math.max(0, scrollTo);
			
			this.scrollable.animateTo(scrollTo);
			this.updateArrows(scrollTo);
		},
		
		/**
		 * Update arrow visibility
		 * 
		 * @private
		 */
		updateArrows: function (pos) {
			var scrollPos = 0,
				maxPos    = this.scrollable.getMaxScrollPosition(),
				itemWidth = 218,
				
				arrowNext = this.nodeArrowNext,
				arrowPrev = this.nodeArrowPrev;
			
			if (typeof pos === 'number') {
				scrollPos = pos;
			} else {
				scrollPos = this.scrollable.getScrollPosition();
			}
			
			if (scrollPos > 0) {
				if (!this.nodeArrowNextVisible) {
					arrowPrev.removeClass('hidden');
					arrowPrev.fx.set('reverse', false).run();
					this.nodeArrowNextVisible = true;
				}
			} else {
				if (this.nodeArrowNextVisible) {
					arrowPrev.fx.once('end', function () { this.addClass('hidden'); }, arrowPrev);
					arrowPrev.fx.set('reverse', true).run();
					this.nodeArrowNextVisible = false;
				}
			}
			
			if (scrollPos < maxPos) {
				if (!this.nodeArrowPrevVisible) {
					arrowNext.removeClass('hidden');
					arrowNext.fx.set('reverse', false).run();
					this.nodeArrowPrevVisible = true;
				}
			} else {
				if (this.nodeArrowPrevVisible) {
					arrowNext.fx.once('end', function () { this.addClass('hidden'); }, arrowNext);
					arrowNext.fx.set('reverse', true).run();
					this.nodeArrowPrevVisible = false;
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
			return Manager.getAction('DesignList').getData();
		},
		
		/**
		 * Hide design bar
		 */
		hide: function () {
			Action.Base.prototype.hide.call(this);
			
			this.getPlaceHolder().removeClass('has-design-bar');
		},
		
		/**
		 * Show design bar
		 */
		show: function () {
			Action.Base.prototype.show.call(this);
			
			this.getPlaceHolder().addClass('has-design-bar');
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});