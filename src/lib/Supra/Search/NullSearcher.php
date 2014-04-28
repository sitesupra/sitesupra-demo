<?php

namespace Supra\Search;

class NullSearcher extends AbstractSearcher
{
	/**
	 * {@inheritDoc}
	 */
	public function processRequest(Request\SearchRequestInterface $request)
	{
		return new Result\DefaultSearchResultSet;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isKeywordSuggestionSupported()
	{
		return false;
	}
}