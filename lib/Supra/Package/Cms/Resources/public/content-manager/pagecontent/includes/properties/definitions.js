YUI.add('supra.page-properties-definitions', function (Y) {
	"use strict";
	
	function PropertyDefinitions () {
		PropertyDefinitions.superclass.constructor.apply(this, arguments);
	}
	
	PropertyDefinitions.NAME = 'page-properties-definitions';
	PropertyDefinitions.CLASS_NAME = 'su-' + PropertyDefinitions.NAME;
	
	PropertyDefinitions.ATTRS = {
		'definitions': {
			setter: '_setAttrDefinitions',
			getter: '_getAttrDefinitions'
		}
	};
	
	Y.extend(PropertyDefinitions, Y.Base, {
		/**
		 * All property definitions
		 * @type {Object|Null}
		 * @protected
		 */
		definitions: null,
		
		/**
		 * @constructor
		 */
		initialize: function () {
			this.definitions = {};
		},
		
		/**
		 * Calculate differences between two property lists
		 *
		 * @param {Array} from Last know property list
		 * @param {Array} to New property list
		 * @param {Array} [path] Key path to the property lists
		 */
		calculateDiff: function (from, to, path) {
			var changes = [],
				i, ii,
				k, kk,
				attrs, key,
				fromIsArray = Array.isArray(from),
				toIsArray = Array.isArray(to),
				removed = false,
				found = false;
			
			if (fromIsArray && toIsArray) {
				for (i=0, ii=from.length; i<ii; i++) {
					for (k=0, kk=to.length; k<kk; k++) {
						if (from[i].id === to[k].id) {
							// Go deeper into the tree
							if (from[i].properties || to[k].properties) {
								changes = changes.concat(this.calculateDiff(from[i].properties, to[k].properties, [].concat(path).concat(i)));
							}
							break;
						}
					}
					
					if (k === kk) {
						// Didn't found a property in the new list
						removed = true;
						changes.push({'action': 'remove', 'path': [].concat(path).concat(i), 'item': from[i]});
					}
				}
				
				if (!removed && from.length === to.length) {
					// There can't be new items
					return changes;
				}
				
				for (i=0, ii=to.length; i<ii; i++) {
					for (k=0, kk=from.length; k<kk; k++) {
						if (from[k].id === to[i].id) break;
					}
					
					if (k === kk) {
						// Didn't found a property in the old list
						changes.push({'action': 'add', 'path': [].concat(path).concat(i), 'item': to[i]});
					}
				}
				
			} else if (!fromIsArray && !toIsArray) {
				// Two objects
				if (typeof fromIsArray === 'object' && typeof toIsArray === 'object') {
					// Do we even need to compare attributes? Will they ever change?
				}
			} else {
				// Array mutated from/into object?! That's not a valid
				// property change, will ignore it.
			}
			
			return changes;
		},
		
		
		/* --------------------- Attributes --------------------- */
		
		
		/**
		 * Property definitions attribute setter
		 *
		 * @param {Object} definitions New attribute value
		 * @returns {Object} Property definitions
		 * @protected
		 */
		_setAttrDefinitions: function (_definitions) {
			var definitions = _definitions || [],
				diff = this.calculateDiff(this.definitions || [], definitions);
			
			if (diff.length) {
				this.fire('definitionsChange', {'value': definitions, 'prevValue': this.definitions, 'diff': diff});
				this.definitions = definitions;
			}
		},
		
		/**
		 * Property definitions attribute getter
		 *
		 * @returns {Object} Property definitions
		 * @protected
		 */
		_getAttrDefinitions: function () {
			return this.definitions;
		}
		
	});
	
	Supra.Manager.PageContent.PropertyDefinitions = PropertyDefinitions;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['yui-base']});
