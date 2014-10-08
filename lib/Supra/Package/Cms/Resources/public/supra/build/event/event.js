/**
 * Adds on('exist', ...) event to YUI Event class
 * 
 * Usage:
 * 		//Event handler function will be called when AppList object will exist
 * 		Y.on('exist', function () {...}, 'Supra.Dashboard.AppList');
 */
YUI.add('supra.event', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//If already defined, then exit
	if (Y.Event.onExist) return;
	
	var Event = Y.Event;
	var _exist = [];
	var _original_load = Event._load;
	var _retryCount = 0;
	
	Event._exist_interval = null;
	Event.onExist = function(id, fn, p_obj, p_override, checkContent, compat) {

        var a = Y.Array(id), i, existHandle;

        for (i=0; i<a.length; i=i+1) {
            _exist.push({ 
                id:         a[i], 
                fn:         fn, 
                obj:        p_obj, 
                override:   p_override, 
                checkReady: checkContent,
                compat:     compat 
            });
        }
        _retryCount = this.POLL_RETRYS;

        // We want the first test to be immediate, but async
        setTimeout(Y.bind(Event._poll_exist, Event), 0);

        existHandle = new Y.EventHandle({

            _delete: function() {
                // set by the event system for lazy DOM listeners
                if (existHandle.handle) {
                    existHandle.handle.detach();
					return;
                }

                var i, j;

                // otherwise try to remove the onAvailable listener(s)
                for (i = 0; i < a.length; i++) {
                    for (j = 0; j < _exist.length; j++) {
                        if (a[i] === _exist[j].id) {
                            _exist.splice(j, 1);
                        }
                    }
                }
            }

        });

        return existHandle;
    };
	
	Event._poll_exist = function() {
        if (this.locked) {
            return;
        }

        this.locked = true;

        // keep trying until after the page is loaded.  We need to 
        // check the page load state prior to trying to bind the 
        // elements so that we can be certain all elements have been 
        // tested appropriately
        var i, len, item, el, notExist, executeItem,
            tryAgain = true;

        if (!tryAgain) {
            tryAgain = (_retryCount > 0);
        }

        // onAvailable
        notExist = [];

        executeItem = function (el, item) {
            var context, ov = item.override;
            if (item.compat) {
                if (item.override) {
                    if (ov === true) {
                        context = item.obj;
                    } else {
                        context = ov;
                    }
                } else {
                    context = el;
                }
                item.fn.call(context, item.obj);
            } else {
                context = item.obj || el;
                item.fn.apply(context, (Y.Lang.isArray(ov)) ? ov : []);
            }
        };

        // onAvailable
        for (i=0,len=_exist.length; i<len; ++i) {
            item = _exist[i];
            if (item && !item.checkReady) {

                // el = (item.compat) ? Y.DOM.byId(item.id) : Y.one(item.id);
                var obj_id = item.id.split('.');
				var targ = window;
				for(var k=0,kk=obj_id.length; k<kk; k++) {
					if (obj_id[k] in targ) {
						targ = targ[obj_id[k]];
					} else {
						targ = null;
						notExist.push(item);
						break;
					}
				}
				
				if (targ) {
					executeItem(el, item);
                    _exist[i] = null;
				}
            }
        }
		
        _retryCount = (notExist.length === 0) ? 0 : _retryCount - 1;

        if (tryAgain) {
            // we may need to strip the nulled out items here
            this.startExistInterval();
        } else {
            clearInterval(this._exist_interval);
            this._exist_interval = null;
        }

        this.locked = false;

        return;

    };
	
	Event.startExistInterval = function() {
        if (!Event._exist_interval) {
			Event._exist_interval = setInterval(Y.bind(Event._poll_exist, Event), Event.POLL_INTERVAL);
        }
    };
		
	Event._load = function () {
		_original_load.apply(this, arguments);
		Event._poll_exist();
	};
	
	
	//Add plugin
	Y.Env.evt.plugins.exist = {
	    on: function(type, fn, id, o) {
	        var a = arguments.length > 4 ?  Y.Array(arguments, 4, true) : [];
	        return Y.Event.onExist.call(Y.Event, id, fn, o, a);
	    }
	};
	
	
	/**
	 * Returns character number from keyboard event
	 * If key doesn't have character number, then returns key code
	 * 
	 * Should be used with 'keypress' event, 'keydown' and 'keyup' events are
	 * inconsistent across browsers
	 * 
	 * @param {Object} e Event facade object
	 * @returns {Number} Character code
	 */
	Event.charCodeFromEvent = function (e) {
		var event    = e._event,
			keyCode  = null,
			match;
		
		if (typeof event.keyIdentifier === 'string' && (match = event.keyIdentifier.match(/U\+([A-F0-9]+)/i))) {
			keyCode = parseInt(match[1], 16); // convert from hex
		} else if (typeof event.char === 'string') {
			keyCode = event.char.charCodeAt(0); // get first character code
			
			if (keyCode === 10) {
				// Except new line, we want 13 instead
				keyCode = 13;
			}
		} else if (event.which == null) {
			keyCode = event.keyCode;
		} else if (event.which != 0 && event.charCode != 0) {
			keyCode = event.which;
		} else {
			// Special key, return code anyway
			keyCode = e.charCode || e.keyCode;
		}
		
		return keyCode;
	};

}, YUI.version, {requires:['event-custom-base']});