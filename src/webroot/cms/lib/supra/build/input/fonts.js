YUI.add('supra.input-fonts', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * 
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-fonts';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	
	Input.ATTRS = {
		
		/**
		 * Loading state
		 */
		'loading': {
			value: false,
			setter: '_setLoading'
		},
		
		/**
		 * Loading icon
		 */
		'nodeLoading': {
			value: null
		},
		
		/**
		 * Button label in case of separate slide
		 */
		'labelButton': {
			value: ''
		},
		
		/**
		 * List of all fonts
		 */
		'values': {
			value: [],
			setter: '_setValues'
		},
		
		/**
		 * Fonts preview object
		 */
		'previewGoogleFonts': {
			value: null
		},
		
		/**
		 * Render widget into separate slide and add
		 * button to the place where this widget should be
		 */
		'separateSlide': {
			value: true
		}
		
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<select class="hidden"></select>',
		LABEL_TEMPLATE: '<label></label>',
		
		FONT_ITEM_HEIGHT: 40,
		
		widgets: {
		},
		
		backButtonInitiallyVisible: null,
		
		renderUI: function () {
			this.backButtonInitiallyVisible = null;
			
			this.widgets = {
				// Separate slide
				'slide': null,
				'button': null,
				
				// Form
				'search': null,
				
				// Slideshow for font list
				'slideshow': null,
				
				// Scrollable groups
				'groups': {},
				
				// Container for label and button
				'separateContainer': null
			};
			
			Input.superclass.renderUI.apply(this, arguments);
			
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow(),
					slide = null,
					button = null;
				
				if (slideshow) {
					var value = this.getValueData(this.get('value')),
						label = (value ? value.title || value.family : '') || this.get('labelButton') || '';
					
					this.widgets.button = button = new Supra.Button({
						'label': label,
						'style': 'group',
						'groupStyle': 'no-labels',
						'iconStyle': 'center',
						'icon': ''
					});
					
					this.widgets.slide = slide = slideshow.addSlide({
						'id': 'propertySlide' + this.get('id'),
						'scrollable': false,
						'title': this.get('label')
					});
					
					slide = slide.one('.su-slide-content');
					
					button.render();
					button.addClass('button-section');
					button.addClass(this.getClassName('slide', 'button'));
					button.on('click', this._slideshowChangeSlide, this);
					
					var labelNode = this.get('labelNode'),
						boundingBox = this.get('boundingBox'),
						container = this.widgets.separateContainer = Y.Node.create('<div class="yui3-widget yui3-input"></div>');
					
					if (labelNode) {
						container.append(labelNode, 'before');
					}
					
					container.append(button.get('boundingBox'));
					boundingBox.insert(container, 'before');
					 
					slide.append(boundingBox);
				} else {
					this.set('separateSlide', false);
				}
			}
			
			// Value
			var values = this.get('values'),
				promise = null,
				preview = new Supra.GoogleFonts();
			
			this.set('previewGoogleFonts', preview);
			
			if (values && values.length) {
				// Render fonts
				this._renderFontList(values);
			} else {
				// Load fonts
				promise = Supra.GoogleFonts.loadFonts();
				
				if (promise.state() == 'pending') {
					this.set('loading', true);
				}
				
				promise.done(function (fonts) {
					this.set('loading', false);
					this.set('values', fonts);
				}, this);
			}
		},
		
		bindUI: function () {
			this.get('contentBox').delegate('click', this._onItemClick, 'a', this);
			this.after('valueChange', this._afterValueChange, this);
		},
		
		
		/*
		 * ---------------------------------------- EVENT LISTENERS ----------------------------------------
		 */
		
		
		/**
		 * Handle item click
		 */
		_onItemClick: function (e) {
			var target = e.target.closest('a'),
				family = target.getAttribute('data-family');
			
			if (family) {
				this.set('value', family);
			}
		},
		
		/**
		 * Change slideshow slide to values list
		 * 
		 * @private
		 */
		_slideshowChangeSlide: function (event, id) {
			var slideshow = this.getSlideshow(),
				slide_id  = 'propertySlide' + this.get('id');
			
			if (id) {
				slide_id += id;
			}
			
			slideshow.set('slide', slide_id);
		},
		
		/**
		 * After value change
		 * 
		 * @param {Object} evt Event facade object
		 * @private
		 */
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
				
				var inputs = this.widgets.inputs,
					content = this.get('contentBox'),
					nodes = null,
					i = 0,
					ii = 0;
				
				nodes = evt.prevVal ? content.all('a[data-family="' + evt.prevVal.replace(/"/g, '') + '"]') : null;
				if (nodes) {
					for (i=0, ii=nodes.size(); i<ii; i++) {
						nodes.item(i).removeClass('active');
					}
				}
				
				nodes = evt.newVal ? content.all('a[data-family="' + evt.newVal.replace(/"/g, '') + '"]') : null;
				if (nodes) {
					for (i=0, ii=nodes.size(); i<ii; i++) {
						nodes.item(i).addClass('active');
					}
				}
			}
		},
		
		
		/*
		 * ---------------------------------------- SLIDESHOW ----------------------------------------
		 */
		
		
		/**
		 * Returns parent widget by class name
		 * 
		 * @param {String} classname Parent widgets class name
		 * @return Widget instance or null if not found
		 * @private
		 */
		getParentWidget: function (classname) {
			var parent = this.get("parent");
			while (parent) {
				if (parent.isInstanceOf(classname)) return parent;
				parent = parent.get("parent");
			}
			return null;
		},
		
		/**
		 * Returns slideshow
		 * 
		 * @return Slideshow
		 * @type {Object}
		 * @private
		 */
		getSlideshow: function () {
			var form = this.getParentWidget("form");
			return form ? form.get("slideshow") : null;
		},
		
		
		/*
		 * ---------------------------------------- API ----------------------------------------
		 */
		
		
		/**
		 * Returns full data for value
		 * 
		 * @param {String} value Optional, value for which to return full data
		 * @returns {Object} Value data
		 */
		getValueData: function (value, groups) {
			var value  = value === null || typeof value === 'undefined' ? this.get('value') : value,
				groups = groups || this.get('values'),
				i  = 0,
				ii = groups ? groups.length : 0,
				values = null,
				k  = 0,
				kk = 0,
				family = '';
			
			for (; i<ii; i++) {
				values = groups[i].fonts;
				
				for (k=0,kk=values.length; k<kk; k++) {
					// When setting data-family attribute quotes are removed, here we have to do the same
					family = values[k].family.replace(/"/g, '');
					
					if (family === value || values[k].apis === value) {
						return values[k];
					}
				}
			}
			
			return null;
		},
		
		
		/*
		 * ---------------------------------------- FONT LIST ----------------------------------------
		 */
		
		
		/**
		 * Render font list
		 * 
		 * @param {Array} fonts List of fonts grouped by categories
		 * @private
		 */
		_renderFontList: function (fonts) {
			var search = this.widgets.search,
				slideshow = this.widgets.slideshow,
				slide = null,
				id = null,
				i = 0,
				ii = fonts.length,
				button = this.widgets.button;
			
			if (!search) {
				search = new Supra.Input.String();
				search.render(this.get('contentBox'));
				search.addClass('search');
				
				window.font = this;
				search.on('input', this._onSearchInput, this);
				
				this.widgets.search = search;
			}
			
			if (!slideshow) {
				slideshow = new Supra.Slideshow();
				slideshow.render(this.get('contentBox'));
				slideshow.on('slideChange', this._updateBackButtonVisibility, this);
				this.widgets.slideshow = slideshow;
				
				// Main / root
				id = 'main' + this.get('id');
				slide = slideshow.addSlide({'id': id});
				this.widgets.groups['main'] = {
					id: id,
					node: slide.one('.su-slide-content, .su-multiview-slide-content'),
					buttons: []
				};
				
				// Search
				id = 'search' + this.get('id');
				this._renderFontGroup({
					'id': id,
					'title': 'search',
					'fonts': [],
					'visible': false,
					'namespace': 'search'
				});
			}
			
			// Create main slides
			slide = this.widgets.groups['main'].node;
			
			for (; i<ii; i++) {
				this._renderFontGroup(fonts[i]);
			}
			
			
			if (button) {
				var data = this.getValueData(this.get('value'), fonts),
					label = (data ? data.title || data.family : '') || this.get('labelButton') || '';
				
				button.set('icon', data && data.icon ? data.icon : '');
				button.set('label', label);
			}
		},
		
		/**
		 * Render font group
		 * 
		 * @param {String} title Group title
		 * @private
		 */
		_renderFontGroup: function (group) {
			var slideshow = this.widgets.slideshow,
				main = this.widgets.groups['main'].node,
				button = null,
				node = null,
				scrollable = null,
				id = group.id ? group.id : group.title + this.get('id'),
				slide = slideshow.addSlide({
					'id': id
				});
			
			// Button on main slide
			if (group.visible !== false) {
				button = new Supra.Button({
					style: 'small',
					label: group.title
				});
				button.addClass('button-section');
				button.render(main);
				
				button.on('click', this._handleFontGroupButtonClick, this, id);
			}
			
			// Slide content
			node = Y.Node.create('<div class="yui3-input-font-list" style="height: ' + (group.fonts.length * this.FONT_ITEM_HEIGHT) + 'px;"></div>');
			slide.one('.su-slide-content, .su-multiview-slide-content').append(node);
			
			scrollable = slide.getData('scrollable');
			
			this.widgets.groups[group.namespace || id] = {
				id: id,
				
				button: button,
				slide: slide,
				node: node,
				
				fonts: group.fonts,
				count: group.fonts.length,
				rendered: 0
			};
			
			scrollable.on('sync', this._updateFontList, this);
			
			window.font = this;
		},
		
		/**
		 * Render font list
		 * 
		 * @param {Object} group Font group
		 * @private
		 */
		_renderFontItems: function (group, from, to) {
			var fonts = group.fonts,
				node  = null,
				i = from,
				container = group.node,
				preview_fonts = [],
				preview = this.get('previewGoogleFonts'),
				value = this.get('value'),
				family = '';
			
			for (; i < to; i++) {
				if (fonts[i].apis) {
					preview_fonts.push(fonts[i]);
				}
				
				family = fonts[i].family.replace(/"/g, '');
				
				node = Y.Node.create('<a ' + (family == value ? 'class="active" ' : '') + '>' + fonts[i].title + '</a>');
				node.setStyle('fontFamily', fonts[i].family);
				node.setAttribute('data-family', family);
				container.append(node);
			}
			
			preview.addFonts(preview_fonts);
			
			group.rendered = to;
		},
		
		/**
		 * Draw additional fonts if needed
		 * 
		 * @private
		 */
		_updateFontList: function (group) {
			// Prevent loop caused by 'sync'
			if (this._isUpdatingFontList) return;
			this._isUpdatingFontList = true;
			
			var group = typeof group == 'string' ? group : this.widgets.slideshow.get('slide'),
				groups = this.widgets.groups,
				from = 0,
				to = 0,
				scroll = 0,
				view = 0,
				scrollable = null;
			
			if (group in groups) {
				group = groups[group];
				from = group.rendered;
				
				if (group.count > group.rendered) {
					// Check if we need to render more fonts
					scrollable = group.slide.getData('scrollable');
					scrollable.syncUI();
					scroll = scrollable.getScrollPosition();
					view = scrollable.getViewSize();
					
					if (scroll + view > group.rendered * this.FONT_ITEM_HEIGHT) {
						to = Math.min(Math.ceil((scroll + view * 2) / this.FONT_ITEM_HEIGHT), group.count);
						
						if (from != to) {
							this._renderFontItems(group, from, to);
						}
					}
				}
			}
			
			this._isUpdatingFontList = false;
		},
		
		/**
		 * On group button click open specific slideshow slide
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} data Additional event data
		 * @private
		 */
		_handleFontGroupButtonClick: function (event, data) {
			this._initView();
			this.widgets.slideshow.set('slide', data);
			this._updateFontList();
		},
		
		/**
		 * Handle back button click
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_handleBackButtonClick: function (event) {
			var slideshow = this.widgets.slideshow,
				slide = slideshow.get('slide'),
				main = this.widgets.groups.main.id,
				search = this.widgets.groups.search.id;
			
			if (slide != main) {
				slideshow.scrollBack();
				
				if (slide == search) {
					this.widgets.search.set('value', '');
				}
				
				event.halt();
			}
		},
		
		
		/**
		 * On slide change update back button visibility
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_updateBackButtonVisibility: function (event) {
			var was_visible = this.backButtonInitiallyVisible,
				sidebar = null,
				button = null;
			
			if (was_visible === false) {
				// It was not visible, so for main slide button shouldn't be visible
				sidebar = this.getParentWidget('ActionBase');
				
				if (sidebar) {
					button = sidebar.get('backButton');
					
					if (!event.newVal || !this.widgets.groups.main || event.newVal == this.widgets.groups.main.id) {
						button.hide();
					} else {
						button.show();
						this._viewActive = true;
					}
				}
				
			}
		},
		
		
		_viewActive: false,
		
		/**
		 * Check back button initial state
		 * Attach to form visible event to observe when sidebar is hidden
		 */
		_initView: function () {
			var was_visible = this.backButtonInitiallyVisible,
				sidebar = null,
				button = null,
				form = null;
			
			if (was_visible === null) {
				// Find if back button is visible and bind linstener
				sidebar = this.getParentWidget('ActionBase');
				was_visible = this.backButtonInitiallyVisible = false;
				
				if (sidebar) {
					// Back button
					button = sidebar.get('backButton');
					if (button) {
						was_visible = this.backButtonInitiallyVisible = button.get('visible');
						button.before('click', this._handleBackButtonClick, this);
					}
					
					// Control button
					button = sidebar.get('controlButton');
					if (button) {
						button.before('click', this._resetView, this);
					}
				}
				
				// When form is hidden reset 
				form = this.getParentWidget('form');
				form.on('visibleChange', function (event) {
					if (!event.newVal && event.prevVal) {
						this._resetView();
					}
				}, this);
			}
		},
		
		/**
		 * Reset view to inital state,
		 * set slideshow to first main slide, which will
		 * hide back button if needed
		 */
		_resetView: function (event) {
			if (this._viewActive) {
				this._viewActive = false;
				
				if (this.widgets.slideshow) {
					this.widgets.slideshow
							.set('noAnimations', true)
							.set('slide', this.widgets.groups.main.id)
							.set('noAnimations', false);
				}
			}
		},
		
		
		/*
		 * ---------------------------------------- SEARCH ----------------------------------------
		 */
		
		
		/**
		 * Handle search input event
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_onSearchInput: function (event) {
			var slideshow = this.widgets.slideshow,
				slide = slideshow.get('slide'),
				groups = this.widgets.groups,
				animate = false,
				scrollable = null,
				search = groups.search;
			
			if (event.value) {
				if (slide != search.id) {
					
					if (slide != groups.main.id) {
						// Inside one of the groups, don't animate slideshow
						slideshow
							.set('noAnimations', true)
							.set('slide', groups.main.id)
							.set('slide', search.id)
							.set('noAnimations', false);
					} else {
						// Main view, animate slideshow
						slideshow.set('slide', search.id);
					}
				}
				
				if (search.query != event.value) {
					search.query = event.value;
					search.fonts = this._filterFonts(event.value);
					search.count = search.fonts.length;
					search.rendered = 0;
					search.node.empty();
					search.node.setStyle('height', search.fonts.length * this.FONT_ITEM_HEIGHT + 'px');
					
					scrollable = search.slide.getData('scrollable');
					scrollable.syncUI();
					
					this._updateFontList('search');
				}
			} else {
				if (slide != groups.main.id) {
					slideshow.set('slide', groups.main.id);
				}
			}
		},
		
		/**
		 * Filter font list to find fonts which match query string
		 * 
		 * @param {String} query Query string
		 * @returns {Array} List of match fonts
		 * @private
		 */
		_filterFonts: function (query) {
			var groups = this.get('values'),
				i = 0,
				ii = groups.length,
				fonts = null,
				f = 0,
				ff = 0,
				matches = [];
			
			// Lower case and trim
			query = Y.Lang.trim(query.toLowerCase());
			
			for (; i<ii; i++) {
				fonts = groups[i].fonts;
				for (f=0,ff=fonts.length; f<ff; f++) {
					if (fonts[f].title.toLowerCase().indexOf(query) != -1) {
						matches.push(fonts[f]);
					}
				}
			}
			
			return matches;
		},
		
		
		/*
		 * ---------------------------------------- ATTRIBUTES ----------------------------------------
		 */
		
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String} value Value id
		 * @returns {String} New value
		 * @private
		 */
		_setValue: function (value) {
			if (typeof value === 'object' && value.family) {
				value = value.family;
			}
			if (typeof value !== 'string') {
				value = '';
			}
			
			if (this.widgets && this.widgets.button) {
				var data = this.getValueData(value),
					label = (data ? data.title || data.family : '') || this.get('labelButton') || '';
				
				this.widgets.button.set('icon', data && data.icon ? data.icon : '');
				this.widgets.button.set('label', label);
			}
			
			return value;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @param {String} value Previous value
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_getValue: function (value) {
			return value;
		},
		
		/**
		 * Values attribute setter
		 * 
		 * @param {Array} values List of values
		 * @returns {Array} New values list
		 * @private
		 */
		_setValues: function (values) {
			if (this.get('rendered')) {
				this._renderFontList(values);
			}
			return values;
		},
		
		/**
		 * Disabled attribute setter
		 * Disable / enable HTMLEditor
		 * 
		 * @param {Boolean} value New state value
		 * @return New state value
		 * @type {Boolean}
		 * @private
		 */
		_setDisabled: function (value) {
			var button = this.widgets.button,
				search = this.widgets.search;
			
			if (button) {
				button.set('disabled', !!value);
			}
			
			if (search) {
				search.set('disabled', !!value);
			}
			
			this.get('boundingBox').toggleClass('yui3-input-disabled', value);
			
			return !!value;
		},
		
		/**
		 * Loading attribute setter
		 * 
		 * @param {Boolean} loading Loading attribute value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setLoading: function (loading) {
			var box = this.get('contentBox');
			
			if (box) {
				if (loading && !this.get('nodeLoading')) {
					var node = Y.Node.create('<span class="loading-icon"></span>');
					box.append(node);
					this.set('nodeLoading', node);
				}
				
				box.toggleClass(this.getClassName('loading'), loading);
			}
			
			this.set('disabled', loading);
			return loading;
		},
		
	});
	
	Supra.Input.Fonts = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.google-fonts']});