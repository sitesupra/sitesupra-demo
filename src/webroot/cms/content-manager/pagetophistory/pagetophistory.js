SU('slider', function (Y) {

	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageTopHistory',
		
		/**
		 * No need for template
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Need stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Localized texts
		 */
		LOCALE: {
			'published': 'Published by ',
			'draft': 'Draft by '
		},
		
		/**
		 * Version history data
		 * @type {Array}
		 */
		data: null,
		
		/**
		 * List of iframes
		 * @type {Array}
		 */
		iframes: [],
		
		/**
		 * List of loaded ifames
		 * @type {Array}
		 */
		iframes_loaded: [],
		
		/**
		 * Visible iframe index
		 */
		iframe_visible: -1,
		
		/**
		 * Iframe, which is used for editing content
		 * @type {Object}
		 */
		iframe_editable: null,
		
		/**
		 * Iframe content node
		 * @type {Object}
		 */
		iframe_content: null,
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			this.slider = new Y.Slider({
				min     : 1,
				max     : 2,
				value   : 1,
				length  : '100%',
				thumbUrl: Y.config.base + '/slider/assets/skins/supra/thumb-x.png',
				clickableRail: false
			});
			
			//After resize update slider width
			Y.on('resize', Y.throttle(Y.bind(this.sync, this), 50), window);
			
			//After slide move thumb into correct position
			this.slider.on('slideEnd', function () { 
				this.slider.syncUI();
			}, this);
			
			this.slider.after('valueChange', function () {
				this.showContent();
			}, this);
		},
		
		/**
		 * Update slider width
		 */
		sync: function () {
			var width = this.getPlaceHolder().get('offsetWidth');
			this.slider.set('length', width - 183 + 'px');
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			var placeholder = this.getPlaceHolder();
			placeholder.addClass('yui-tab-history');
			
			//Render contents
			this.page_list = Y.Node.create('<div class="slider-pages"></div>');
			placeholder.append(this.page_list);
			
			//On content click change 
			Y.delegate('click', this._handleClick, Y.Node.getDOMNode(this.page_list), 'li', this);
			
			//Render slider
			this.slider.render(placeholder);
			
			//Iframes
			this.iframe_content = SU.Manager.PageContent.getIframe().get('contentBox');
			this.iframe_editable = this.iframe_content.one('iframe');
		},
		
		/**
		 * Show preview iframe
		 */
		showContent: function () {
			var index = this.slider.get('value') - 1,
				data = this.data[index];
			
			this.iframe_editable.addClass('hidden');
			
			if (index != this.iframe_visible) {
				if (this.iframe_visible != -1 && this.iframes[this.iframe_visible]) {
					this.iframes[this.iframe_visible].addClass('hidden');
				}
				
				if (!this.iframes[index]) {
					this.iframes[index] = Y.Node.create('<iframe src="about:blank" />');
					this.iframe_content.append(this.iframes[index]);
				}
				
				if (!this.iframes_loaded[index]) {
					url = this.getPath() + 'version_' + data.version_id + '.html';
					this.iframes[index].setAttribute('src', url);
					this.iframes_loaded[index] = 1;
				}
				
				this.iframes[index].removeClass('hidden');
				this.iframe_visible = index;
			}
		},
		
		/**
		 * Hide preview iframe
		 */
		hideContent: function () {
			this.iframes_loaded = [];
			if (this.iframe_visible != -1) {
				this.iframes[this.iframe_visible].addClass('hidden');
				this.iframe_editable.removeClass('hidden');
				this.iframe_visible = -1;
			}
		},
		
		/**
		 * Handle click on cell
		 */
		_handleClick: function (e) {
			var node = e.currentTarget;
			if (node) {
				var value = parseInt(node.getAttribute('data')) || 0;
				this.slider.set('value', value + 1);
			}
		},
		
		/**
		 * Load history list
		 */
		loadHistory: function () {
			var url = this.getDataPath();
			
			SU.io(url, {
				'on': {
					'success': function (evt, data) {
						this.hideContent();
						
						var html = '',
							factor = 0,
							pos,
							version_id = SU.Manager.Page.getPageData().version_id,
							value = 0;
						
						factor = (data.length ? (100 / (data.length - 1)) : 0);
						
						for(var i=0,ii=data.length-1; i<=ii; i++) {
							pos = (i == ii ? 100 : (factor * i));
							html += '<ul><li data="' + i + '" style="left: ' + pos + '%"><div></div>' + data[i].date + '<br />' + this.LOCALE[data[i].type] + data[i].author + '</li></ul>';
							if (data[i].version_id == version_id) {
								value = i + 1;
							}
						}
						
						this.data = data;
						this.page_list.set('innerHTML', html);
						
						this.slider.set('max', data.length);
						this.slider.set('value', value);
						this.showContent();
						
						//Fire resize event to force iframe in correct position
						this.fire('resize');
						this.sync();
					}
				}
			}, this);
		},
		
		/**
		 * Restore everything like it was before execute
		 */
		restore: function () {
			this.hideContent();
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.loadHistory();
		}
	});
	
});