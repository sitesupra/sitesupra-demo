//Invoke strict mode
"use strict";

/**
 * Continuous loader plugin
 */
YUI.add('website.datagrid-loader', function (Y) {
	
	function LoaderPlugin (config) {
		LoaderPlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a DataGrid instance, the plugin will be 
	// available on the "loader" property.
	LoaderPlugin.NS = 'loader';
	
	// Attributes
	LoaderPlugin.ATTRS = {
		
	};
	
	// Extend Plugin.Base
	Y.extend(LoaderPlugin, Y.Plugin.Base, {
		
		/**
		 * Loaded chunks
		 * @type {Object}
		 * @private
		 */
		chunks: {},
		
		/**
		 * Loading data
		 */
		loading: false,
		
		/**
		 * Number of records loaded
		 * @type {Number}
		 * @private
		 */
		loaded: 0,
		
		/**
		 * Total number of records
		 * @type {Number}
		 * @private
		 */
		total: 0,
		
		/**
		 * Spacing node
		 */
		tableSpacerNode: null,
		
		
		
		/**
		 * Load records, which should be in view now
		 */
		_loadRecordsInView: function () {
			if (this.loading || this.loaded >= this.total) return;
			
			var host = this.get('host'),
				content_height = host.tableBodyNode.get('offsetHeight'),	//Loaded data height
				scroll_height = host.get('contentBox').get('offsetHeight');
				scroll_offset = host.get('contentBox').get('scrollTop'),
				diff = content_height - (scroll_height + scroll_offset);
			
			if (diff < 50) {
				host.requestParams.set('offset', this.loaded);
				host.reset();
			}
		},
		
		_onReset: function () {
			this.loading = true;
		},
		
		/**
		 * Handle successful load
		 * Execution context is DataGrid
		 * 
		 * @param {Object} e Response event
		 * @private
		 */
		_dataReceivedSuccess: function (e) {
			var response = e.response,
				loader = this.loader;
			
			this.beginChange();
			
			//Don't need old data
			if (response.meta.offset == 0) {
				this.loader.loaded = 0;
				this.removeAllRows();
			}
			
			//Mark chunk as loaded
			this.loader.total = response.meta.total;
			this.loader.loaded += response.results.length;
			this.loader.chunks[response.meta.offset] = true;
			
			//Add new rows
			var results = response.results, i = null;
			for(i in results) {
				if (results.hasOwnProperty(i)) {
					this.addRow(results[i]);
				}
			}
			
			this._renderEvenOddRows();
			
			this.fire('reset:success', {'results': results});
			
			this.endChange();
			
			this.loader.loading = false;
			this.loader.syncTotal();
		},
		
		/**
		 * Handle load failure
		 * Execution context is DataGrid
		 * 
		 * @param {Object} e Response event
		 * @private
		 */
		_dataReceivedFailure: function (e) {
			Y.log(e, 'error');
			
			this.loader.loading = false;
		},
		
		/**
		 * Update UI to reflect loaded items
		 */
		syncTotal: function () {
			var total = this.total,
				loaded = this.loaded,
				host = this.get('host'),
				height = host.tableBodyNode.get('offsetHeight'),
				content_height = 0;
			
			if (!this.tableSpacerNode) {
				this.tableSpacerNode = Y.Node.create('<div class="yui3-datagrid-spacer"></div>');
				host.get('contentBox').append(this.tableSpacerNode);
			}
			
			content_height = Math.max(0, total - loaded) * (height / loaded);
			
			this.tableSpacerNode.setStyle('height', content_height + 'px');
			
			//Recheck scroll position
			this._loadRecordsInView();
		},
		
		/**
		 * Constructor
		 */
		initializer: function () {
			var host = this.get('host');
			
			this.chunks = {};
			
			host._dataReceivedSuccess = this._dataReceivedSuccess;
			host._dataReceivedFailure = this._dataReceivedFailure;
			
			host.get('contentBox').on('scroll', this._loadRecordsInView, this);
			
			this._onResize = Y.throttle(Y.bind(this._onResize, this), 50);
			Y.on('resize', this._onResize, window);
			
			host.on('reset', this._onReset, this);
			
		},
		
		/**
		 * Destructor
		 */
		destructor: function () {
			
		}
		
	});
	
	Supra.DataGrid.LoaderPlugin = LoaderPlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'website.datagrid']});