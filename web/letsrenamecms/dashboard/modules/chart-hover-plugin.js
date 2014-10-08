YUI.add("dashboard.chart-hover-plugin", function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'hover-plugin';
	Plugin.NS = 'hover';
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			var host = this.get("host");
			
			host.on("markerEvent:mouseover", Y.bind(function (e) { this.updateMarkerState("mouseover", e.index); }, this));
			host.on("markerEvent:mouseout", Y.bind(function (e) { this.updateMarkerState("mouseout", e.index); }, this));
		},
		
		/**
		 * Returns all series
		 * 
		 * @return {Array} List of all series
		 */
		getSeries: function () {
			var host = this.get("host"),
				key = 0,
				series = host.getSeries(key),
				all = [];
			
			while (series) {
				all.push(series);
				key++;
				series = host.getSeries(key);
			}
			
			return all;
		},
		
		/**
		 * Update marker state for all series
		 * 
		 * @param {String} state New state, "mouseover" or "mouseout"
		 * @param {Number} index
		 */
		updateMarkerState: function (state, index) {
			var series = this.getSeries(),
				i = 0,
				ii = series.length;
			
			for (; i<ii; i++) {
				series[i].updateMarkerState(state, index);
			}
		}
		
	});
	
	Supra.ChartHoverPlugin = Plugin;
	
}, YUI.version, {requires:["plugin", "charts"]});