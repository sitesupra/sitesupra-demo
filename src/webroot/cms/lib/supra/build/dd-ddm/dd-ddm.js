YUI.add('supra.dd-ddm', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Overwrite Y.DD.DDM shim activation to allow having separate shim for each document
	 * because of iframes
	 */
	Supra.mix(Y.DD.DDM, {
		
		// Preserve original functions
		_pg_activateOriginal: Y.DD.DDM._pg_activate,
		_createPGOriginal: Y.DD.DDM._createPG,
		
		/**
		 * Document body to which _pg belongs to
		 * @private
		 */
		_pg_body: null,
		
		/**
		 * List of all _pg elements and their document bodies
		 * @private
		 */
		_pg_list: [],
		
		
		/**
		 * Add document to the list of documents to which shim should
		 * be added
		 */
		regDoc: function (doc) {
			var list = this._pg_list,
				i = 0,
				ii = list.length,
				body = doc.body,
				original = Y.config.doc,
				_pg = this._pg,
				object = null;
			
			for (; i<ii; i++) {
				if (list[i]._pg_body === body) {
					// Already exists
					return;
				}
			}
			
			// If registering other document than this then reset _pg
			if (doc !== original) {
				this._pg = null;
			}
			
			// Add to the list
			list.push({
				'_pg': this._pg,
				'_pg_doc': doc,
				'_pg_body': doc.body
			});
			
			// Set up listeners
			Y.config.doc = doc;
			Y.DD.DDM._setupListeners();
			Y.config.doc = original;
			
			// If registering other document than this then restore correct _pg 
			if (doc !== original) {
				this._pg = null;
				this._pg_set(original);
			}
		},
		
		/**
		 * Remove document from the list of documents
		 */
		unregDoc: function (doc) {
			var list = this._pg_list,
				i = 0,
				ii = list.length,
				body = doc.body;
			
			// To be sure all targets are deactivated
			//this._deactivateTargets();
			
			for (; i<ii; i++) {
				if (list[i]._pg_body === body) {
					if (body === this._pg_body) {
						// Set new document
						this._pg_set(document);
					}
					list.splice(i, 1);
					return;
				}
			}
		},
		
		/**
		 * Activates the shim for document in which active drag element is inside
		 * if document was added using addDoc()
		 * 
		 * @private
		 */
		_pg_activate: function () {
			var node = this.activeDrag.get('node'),
				doc  = node.getDOMNode().ownerDocument,
				original_doc = Y.config.doc,
				//doc  = document,
				//original_doc = document,
				body = doc.body,
				create = false;
			
			// If current pg owner document body doesn't match then search
			// for existing from the list
			if (this._pg_body !== body) {
				this._pg_set(doc);
			}
			
			this._pg_activateOriginal();
			
			// Restore original document in config after _pg has been created
			Y.config.doc = original_doc;
		},
		
		/**
		 * Finds document in the list of registered documents
		 * 
		 * @param {Object} doc Document
		 * @returns {Object} Object with document, body and shim node
		 * @private
		 */
		_pg_find: function (doc) {
			var list = this._pg_list,
				i = 0,
				ii = list.length,
				body = doc.body;
			
			for (; i<ii; i++) {
				if (list[i]._pg_body === body) {
					return list[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Set document as active
		 * 
		 * @param {Object} doc Document element
		 * @private
		 */
		_pg_set: function (doc) {
			var item = this._pg_find(doc);
			if (item) {
				// Found a match
				this._pg_body = item._pg_body;
				this._pg = item._pg;
				
				// Update document
				Y.config.doc = item._pg_doc;
			}
		},
		
		/**
		 * Create shim element and save it in the registered document list
		 * 
		 * @private
		 */
		_createPG: function () {
			this._createPGOriginal();
			
			// Save _pg element
			this._pg_find(Y.config.doc)._pg = this._pg;
		}
		
	});
	
	// Register self immediatelly
	Y.DD.DDM.regDoc(Y.config.doc);
	
}, YUI.version, {'requires': ['dd-ddm']});