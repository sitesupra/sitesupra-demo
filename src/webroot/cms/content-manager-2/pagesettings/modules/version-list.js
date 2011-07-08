//Invoke strict mode
"use strict";

/**
 * Version selection input
 */
YUI.add("website.version-list", function (Y) {
	
	function VersionList (config) {
		VersionList.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this._versions = null;
	}
	
	VersionList.NAME = "version-list";
	VersionList.CLASS_NAME = Y.ClassNameManager.getClassName(VersionList.NAME);
	VersionList.ATTRS = {
		'requestUri': null
	};
	
	VersionList.HTML_PARSER = {};
	
	Y.extend(VersionList, Y.Widget, {
		
		/**
		 * Version data
		 * @type {Object}
		 * @private
		 */
		_versions: null,
		
		/**
		 * Load versions
		 * 
		 * @private
		 */
		_loadVersions: function () {
			var uri = this.get('requestUri');
			Supra.io(uri, this._loadVersionsComplete, this);
		},
		
		/**
		 * Handle version loading completion
		 * 
		 * @param {Object} transaction
		 * @param {Object} data
		 * @private
		 */
		_loadVersionsComplete: function (transaction, data) {
			this._versions = data;
			this.syncUI();
		},
		
		/**
		 * Create new version
		 */
		newVersion: function (e) {
			/* @TODO */
			e.halt();
			e.stopPropagation();
			return false;
		},
		
		/**
		 * Rename selected version
		 * @param {Object} e
		 */
		renameVersion: function (e) {
			/* @TODO */
		},
		
		/**
		 * Delete selected version
		 * @param {Object} e
		 */
		deleteVersion: function (e) {
			/* @TODO */
		},
		
		/**
		 * Select version
		 * @param {Object} e
		 */
		selectVersion: function (evt) {
			//Buttons has their own event handlers
			var button = evt.target.closest('.yui3-button');
			if(button) return;
			
			//
			var versions = this._versions,
				target = evt.target.closest('LI'),
				version_id = target.getData('version_id');
			
			for(var i=0,ii=versions.length; i<ii; i++) if (version_id == versions[i].id) {
				this.fire('change', {'version': versions[i]});
				return;
			}
		},
		
		syncUI: function () {
			VersionList.superclass.syncUI.apply(this, arguments);
			
			var versions = this._versions;
			if (!versions) {
				this._loadVersions();
				return;
			}
			
			var content = this.get('contentBox').one('ul'),
				version,
				buttons,
				item;
			
			//Remove old items
			content.all('li').remove();
			
			//Create new items
			for(var i=0,ii=versions.length; i<ii; i++) {
				version = versions[i];
				
				item = Y.Node.create('<li class="clearfix version-' + version.type + '">\
										<h5>' + Y.Lang.escapeHTML(version.title) + '</h5>\
										<p>' + Y.Lang.escapeHTML(version.description) + '</p>\
										<small>Last change: ' + Y.Lang.escapeHTML(version.author) + ', ' + Y.Lang.escapeHTML(version.date) + '</small>\
										<div class="version-item-buttons"></div>\
									  </li>');
				
				item.setData('version_id', version.id);
				
				//Buttons
				buttons = item.one('.version-item-buttons');
				(new Supra.Button({'style': 'mid', 'label': 'Rename'})).render(buttons).on('click', this.renameVersion, this);
				(new Supra.Button({'style': 'mid-red', 'label': 'Delete'})).render(buttons).on('click', this.deleteVersion, this);
				
				content.append(item);
			}
			
			content.all('li').on('click', this.selectVersion, this);
		},
		
		renderUI: function () {
			VersionList.superclass.renderUI.apply(this, arguments);
			
			var content = this.get('contentBox');
			
			(new Supra.Button({'srcNode': content.one('button.button-new-version'), 'style': 'mid'}))
				.render().on('click', this.newVersion, this);
		}
	});
	
	Supra.VersionList = VersionList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["widget"]});