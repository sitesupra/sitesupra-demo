<?php

namespace Supra\Editable;

/**
 * Html editable content, extends string so the string could be extended to HTML content
 */
class Html extends String
{
	const EDITOR_TYPE = 'InlineHTML';
	const EDITOR_INLINE_EDITABLE = true;
}
