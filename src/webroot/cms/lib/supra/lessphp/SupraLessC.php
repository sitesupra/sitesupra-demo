<?php

require_once __DIR__ . '/lessc.inc.php';

class SupraLessC extends lessc
{
	public $rootDir;
	
	public function createChild($fname)
	{
		$less = new lessc($fname);
		// Don't copy the import dir, it should remain relative
//		$less->importDir = array_merge((array) $less->importDir, (array) $this->importDir);
		$less->indentChar = $this->indentChar;
		$less->compat = $this->compat;
		return $less;
	}
	
	public function setRootDir($rootDir)
	{
		$this->rootDir = $rootDir;
	}
	
	function findImport($url)
	{
		// Check "absolute" paths beforehand
		if ($url[0] == '/') {
			if (is_file($this->rootDir . $url)) {
				return $this->rootDir . $url;
			} else {
				return null;
			}
		}
		
		return parent::findImport($url);
	}
    
    // Returns from all parameters first color which is not transparent
    protected function lib_firstcolor($args) {
        if ($args[0] != 'list' || count($args[2]) < 1) {
            return array(array('color', 0, 0, 0), 0);
        }
        
        foreach ($args[2] as $color) {
            $coerced = $this->coerceColor($color);
            if ($coerced && $this->toBool($coerced) && $color[1] != 'transparent') {
                return $color;
            }
        }
        
        return array(array('color', 0, 0, 0), 0);
    }
}

/**
 * Class for getting used file list only.
 * Here other performance improvements can be made (output generation code removed).
 */
class SupraLessCFileList extends SupraLessC
{
	public function getFileList()
	{
		return array_keys($this->allParsedFiles);
	}
	
	// Skip all except import
	//function compileProp($prop, $block, $tags, &$_lines, &$_blocks) {
	function compileProp($prop, $block, $out) 
	{
		if ($prop[0] != 'import') {
			return;
		}
		
		return parent::compileProp($prop, $block, $out);
	}
	
}
