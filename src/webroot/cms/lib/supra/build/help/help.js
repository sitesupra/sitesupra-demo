YUI().add('supra.help', function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	Supra.Help = {
		
		
		/* -------------------------- Help notes / tips --------------------------- */
		
		
		/**
		 * Save all tip widgets
		 * @type {Object}
		 * @private
		 */
		_tips: {},
		
		/**
		 * Create help note (tip)
		 * 
		 * @param {String} id Tip ID
		 * @returns {Object} Supra.Deferred promise to which is passed tip object
		 */
		tip: function (id, options) {
			var deferred = new Supra.Deferred(),
				data     = Supra.data.get(['helpnotes', id]),
				widget   = null;
			
			if (data || typeof data === 'undefined') {
				// We take data from Supra.data and internationalization file
				data = Supra.mix({}, data, Supra.Intl.get(['helpnotes', id]), options || {}, true);
				
				widget = new Supra.HelpTip(data);
				widget.on('close', this._handleTipClose, this);
				
				if (data.prepend || data.append || data.before || data.after) {
					// Node
					var target = (data.prepend || data.append || data.before || data.after);
					if (!(target instanceof Y.Node)) {
						target = Y.one(target);
					}
					if (target) {
						// Render widget and insert into correct place
						widget.render();
						
						if (data.prepend) {
							target.prepend(widget.get('boundingBox'));
						} else if (data.append) {
							target.append(widget.get('boundingBox'));
						} else if (data.before) {
							target.insert(widget.get('boundingBox'), 'before');
						} else if (data.after) {
							target.insert(widget.get('boundingBox'), 'after');
						}
					}
				}
				
				this._tips[id] = this._tips[id] || [];
				this._tips[id].push(widget);
				
				deferred.resolveWith(widget, [widget, data]);
			} else {
				// There is no such tip or tip has been previously closed
				deferred.reject();
			}
			
			return deferred.promise();
		},
		
		/**
		 * Handle tip close event
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_handleTipClose: function (event) {
			this._saveTipState(event.id);
			this._removeTip(event.id);
		},
		
		/**
		 * Remove all tips with given id
		 * 
		 * @param {String} id Tip id
		 * @private
		 */
		_removeTip: function (id) {
			var widgets = this._tips[id],
				i       = 0,
				ii      = widgets ? widgets.length : 0;
			
			for (; i<ii; i++) {
				widgets[i].destroy(true);
			}
			
			this._tips[id] = [];
		},
		
		/**
		 * Save that note has been closed
		 * 
		 * @param {String} id Note ID
		 * @private
		 */
		_saveTipState: function (id) {
			var url = Supra.Manager.getAction('Tips').getDataPath('save');
			
			Supra.io(url, {
				'method': 'post',
				'data': {
					'id': id,
					'closed': true
				}
			});
		}
		
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': []});