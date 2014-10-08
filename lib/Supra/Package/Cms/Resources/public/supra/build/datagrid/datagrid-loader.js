/**
 * Continuous loader plugin
 */
YUI.add('supra.datagrid-loader', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function LoaderPlugin (config) {
		LoaderPlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a DataGrid instance, the plugin will be 
	// available on the "loader" property.
	LoaderPlugin.NS = 'loader';
	
	// Attributes
	LoaderPlugin.ATTRS = {
		'recordHeight': {
			'value': null
		},
		'dataSource': {
			'value': null
		}
	};
	
	// Extend Plugin.Base
	Y.extend(LoaderPlugin, Y.Plugin.Base, {
		
		/**
		 * Loading data
		 */
		'loading': false,
		
		/**
		 * Number of records loaded
		 * @type {Number}
		 * @private
		 */
		'loaded': 0,
		
		/**
		 * Total number of records
		 * @type {Number}
		 * @private
		 */
		'total': 0,
		
		/**
		 * Last known results per request
		 * @type {Number}
		 * @private
		 */
		'resultsPerRequest': 0,
		
		/**
		 * Spacing node
		 */
		'tableSpacerNode': null,
		
		
		
		/**
		 * Load records, which should be in view now
		 */
		'checkRecordsInView': function () {
			if (!this.get('host').get('visible') || this.loading || this.loaded >= this.total) return;
			
			var host = this.get('host'),
				content_height = host.tableBodyNode.get('offsetHeight'),	// Loaded data height
				scroll_height = host.scrollable.get('contentBox').get('offsetHeight'),
				scroll_offset = host.scrollable.get('contentBox').get('scrollTop'),
				diff = content_height - (scroll_height + scroll_offset);
			
			if (diff < 50) {
				host.requestParams.set('offset', this.loaded);
				host.requestParams.set('resultsPerRequest', this.getResultsPerRequest());
				host.load();
			}
		},
		
		/**
		 * Return number of results which should be returned for each request
		 * 
		 * @return Optimal results per request
		 * @type {Number}
		 */
		'getResultsPerRequest': function () {
			var host = this.get('host'),
				scroll_height = host.scrollable.get('contentBox').get('offsetHeight'),
				content_height = host.tableBodyNode.get('offsetHeight'),			// Loaded data height
				record_height = this.get('recordHeight'),							// Average record height
				results_per_request = 0;
			
			if (!record_height) {
				record_height = 32;													// 32 is a guess
				if (this.loaded && content_height) {
					record_height = content_height / this.loaded;
				}
			}
			
			if (record_height) {
				results_per_request = Math.ceil(scroll_height / record_height * 1.5);
			} else {
				results_per_request = this.resultsPerRequest;
			}
			
			this.resultsPerRequest = Math.max(20, results_per_request || this.resultsPerRequest);
			return this.resultsPerRequest;
		},
		
		/**
		 * When loading is done reset 'loading' state
		 * 
		 * @private
		 */
		'onLoadComplete': function () {
			this.loading = true;
			
			//Remove loading style
			this.get('host').get('boundingBox').addClass('su-datagrid-loading');
			
			//Sync
			this.get('host').handleChange();
		},
		
		/**
		 * On reset scroll to top
		 * 
		 * @private
		 */
		'beforeReset': function () {
			this.loaded = 0;
			
			//Set initial results per request
			if (!this.get('host').requestParams.get('resultsPerRequest')) {
				this.get('host').requestParams.set('resultsPerRequest', this.getResultsPerRequest());
			}
			
			//Scroll to top
			var scrollable = this.get('host').scrollable;
			scrollable.get('contentBox').set('scrollTop', 0);
			scrollable.syncUIPosition();
		},
		
		/**
		 * Load data
		 * Overwrite DataGrid load function, execution context is DataGrid
		 * 
		 * @private
		 */
		'load': function () {
			//
			if (this.loader.loading) {
				// TRIED TO INITIATE LOADING WHILE PREVIOUS IS NOT COMPLETE
				return;
			}
			
			//Event
			this.fire('load');
			
			this.get('dataSource').sendRequest({
				'request': this.requestParams.toString(),
				'callback': {
					'success': Y.bind(this._dataReceivedSuccess, this),
					'failure': Y.bind(this._dataReceivedFailure, this)
				}
			});
		},
		
		/**
		 * Handle successful load
		 * Overwrite DataGrid _dataReceivedSuccess function, execution
		 * context is DataGrid
		 * 
		 * @param {Object} e Response event
		 * @private
		 */
		'_dataReceivedSuccess': function (e) {
			var response = e.response,
				loader = this.loader;
			
			this.beginChange();
			
			//Mark chunk as loaded
			if (response.meta.total || response.meta.total === 0) {
				this.loader.total = response.meta.total;
			}
			
			this.loader.loaded += response.results.length;
			
			//Add new rows
			var results = response.results, i = null;
			
			for(i in results) {
				if (results.hasOwnProperty(i)) {
					this.add(results[i]);
				}
			}
			
			//Event
			this.fire('load:success', {'results': results});
			
			//Remove loading style
			this.get('boundingBox').removeClass('su-datagrid-loading');
			
			this.endChange();
			
			this.loader.loading = false;
			
			// Don't sync if no results are returned, might result loading loop
			if (response.results.length > 0) {
				this.loader.syncTotal();
			}
		},
		
		/**
		 * Handle load failure
		 * Overwrite DataGrid _dataReceivedFailure function, execution
		 * context is DataGrid
		 * 
		 * @param {Object} e Response event
		 * @private
		 */
		'_dataReceivedFailure': function (e) {
			Y.log(e, 'error');
			
			//Event
			this.fire('load:failure');
			
			this.loader.loading = false;
		},
		
		/**
		 * Update UI to reflect loaded items
		 */
		'syncTotal': function () {
			if (!this.tableSpacerNode) {
				this.tableSpacerNode = Y.Node.create('<div class="su-datagrid-spacer"><div><p></p></div></div>');
				this.tableSpacerNode.one('p').set('text', Supra.Intl.get(['datagrid', 'please_wait']) || '...');
				this.get('host').get('contentBox').append(this.tableSpacerNode);
			}
			
			//Recheck scroll position
			this.checkRecordsInView();
			
			//Sync
			this.get('host').handleChange();
		},
		
		/**
		 * Constructor
		 */
		'initializer': function () {
			var host = this.get('host');
			
			//Overwrite some of the DataGrid functionality
			host._dataReceivedSuccess = this._dataReceivedSuccess;
			host._dataReceivedFailure = this._dataReceivedFailure;
			host.load = this.load;
			
			//Total number of records
			this.total = this.get('host').get('requestTotalRecords') || 0;
			
			//On scroll and resize recheck if more items need to be loaded
			//host.get('boundingBox').on('scroll', this.checkRecordsInView, this);
			host.scrollable.get('contentBox').on('scroll', this.checkRecordsInView, this);
			
			this.handleResize = Y.throttle(Y.bind(this.checkRecordsInView, this), 50);
			host.scrollable.on('sync', this.handleResize);
			//Y.on('resize', this.handleResize, window);
			
			//On load complete update 'loading' status
			host.on('load', this.onLoadComplete, this);
			
			//On reset scroll to top
			host.on('reset', this.beforeReset, this);
			
			//After visibility change update view if needed
			host.after('visibleChange', function (evt) {
				if (evt.newVal) {
					Y.later(16, this, this.checkRecordsInView);
				}
			}, this);
		},
		
		/**
		 * Destructor
		 */
		'destructor': function () {
			
		}
		
	});
	
	Supra.DataGrid.LoaderPlugin = LoaderPlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.datagrid']});