YUI.add('supra.dom-screen', function(Y) {
	//Invoke strict mode
	"use strict";
	
	
	// Cache window size whenever possible
	// #16380 4. Reading window size triggers layout, while it's rarely neccessary
	var _getWinSize = Y.DOM._getWinSize,
		_winSizes = {},
		_winSizesInitialized = false;
	
	Y.DOM._getWinSizeReset = function () {
		// Clean cache
		_winSizes = {};
	};
	
	Y.DOM._getWinSize = function (node, doc) {
		if (!_winSizesInitialized) {
			_winSizesInitialized = true;
			Y.on('resize', Y.DOM._getWinSizeReset);
		}
		
		doc  = doc || (node) ? Y.DOM._getDoc(node) : Y.config.doc;
		
		var size = _winSizes[doc._yuid];
		
		if (size) {
			return size;
		} else {
			size = _winSizes[doc._yuid] = _getWinSize(node, doc);
			return size;
		}
	};
	

}, YUI.version ,{requires:['dom-core', 'dom-screen']});

YUI.Env.mods['dom-base'].details.requires.push('supra.dom-screen');
