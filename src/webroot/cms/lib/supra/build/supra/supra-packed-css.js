/**
 * For all modules loaded using pack.js skins are loaded using pack.css
 * and we need to mark these skin modules as loaded
 */
(function (Y) {
	
	var modules = Y.Env._loader.moduleInfo,
		loaded = YUI.Env._loaded[YUI.version],
		name = null;
	
	for (name in modules) {
		
		if (name.indexOf('skin-') === 0) {
			loaded[name] = true;
		} else if (name.indexOf('supra.') === 0) {
			loaded['skin-supra-' + name] = true;
		}
	}
	
})(Supra.Y);