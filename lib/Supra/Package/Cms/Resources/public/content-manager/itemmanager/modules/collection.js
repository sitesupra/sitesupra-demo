YUI.add('itemmanager.collection', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * List of input groups with controls to add or remove
	 * groups
	 */
	function Collection (config) {
		Collection.superclass.constructor.apply(this, arguments);
	}

	// Collection is not inline
	Collection.IS_INLINE = false;

	// Collection is inside form
	Collection.IS_CONTAINED = true;

	Collection.NAME = 'input-collection';
	Collection.CLASS_NAME = Collection.CSS_PREFIX = 'su-' + Collection.NAME;

	Collection.ATTRS = {
		// Item which is visible
		'visibleItem': {
			value: null
		},
		
		// Remove button label
		'labelRemove': {
			value: 'Remove item'
		}
	};

	Y.extend(Collection, Supra.Input.Collection, {
		
		/**
		 * Unset heading template to prevent creation of it
		 * @type {String|Null}
		 * @protected
		 */
		HEADING_TEMPLATE: null,
		
		
		/**
		 * In sidebar there shouldn't be "Add more" button, so we skip creating
		 * this button
		 * @protected
		 */
		renderUINewItemButton: function () {
		},
		
		renderUI: function () {
			this.after('visibleItemChange', this.attrVisibleItemChange, this);
			this.on('item:add', this.decorateItem, this);
			
			if (!this.get('separateSlide')) {
				var slideshow = this.getSlideshow();
				if (slideshow) {
					this._slideId = slideshow.get('slide');
				}
			}
			
			Collection.superclass.renderUI.apply(this, arguments);
		},
		
		
		/*
		 * ---------------------------- Items ------------------------------
		 */
		
		
		/**
		 * Add new item to the list
		 */
		addItem: function (_data) {
			var data = Supra.mix({
				'__suid': Y.guid()
			}, _data);
			
			this._addItem(data, false /* disabled animations */);
			this._fireChangeEvent();
			this.fire('add', data);
			
			return data;
		},
		
		/**
		 * Remove item
		 */
		removeItem: function (index) {
			this._removeItem(index, false /* disabled animations */);
			this._fireChangeEvent();
			this.fire('remove', index);
		},
		
		/**
		 * Hide item element after creation, we need to display only active
		 * one
		 */
		decorateItem: function (e) {
			var visible = this.get('visibleItem');
			
			if (e.element && e.data && visible !== e.data.__suid) {
				e.element.addClass('hidden');
			}
		},
		
		
		/*
		 * ------------------------- Attributes -----------------------------
		 */
		
		
		/**
		 * Visible item attribute changed, show / hide items
		 * 
		 * @param {Object} e Event facade object
		 * @protected
		 */
		attrVisibleItemChange: function (e) {
			if (e.newVal != e.prevVal) {
				var widgets = this._widgets,
					nodes = this._nodes,
					i = 0,
					ii = widgets.length,
					id;
				
				for (; i<ii; i++) {
					id = widgets[i].input.getInput('__suid').get('value');
					
					if (id === e.prevVal) {
						nodes[i].addClass('hidden');
					} else if (id === e.newVal) {
						nodes[i].removeClass('hidden');
					}
				}
				
				if (this._slideId) {
					// Scroll back to main slide
					var slideshow = this.getSlideshow();
					slideshow.set('noAnimations', true);
					slideshow.set('slide', this._slideId);
					slideshow.set('noAnimations', false);
				}
			}
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value New value
		 * @returns {Object} New value
		 * @protected
		 */
		_setValue: function (_value) {
			var value = Supra.mix([],_value || [], true),
				i = 0,
				ii = value.length;
			
			for (; i<ii; i++) {
				// Internal ID for ItemManager
				if (!value[i].__suid) {
					value[i].__suid = Y.guid();
				}
			}
			
			return Collection.superclass._setValue.apply(this, [value]);
		}
		
	});

	Supra.Input.ItemManagerCollection = Collection;

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {requires:['supra.input-collection']});
