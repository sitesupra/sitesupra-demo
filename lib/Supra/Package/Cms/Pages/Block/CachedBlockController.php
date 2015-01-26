<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Entity\Abstraction\Block;
use Symfony\Component\HttpFoundation\Request;
use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Response\ResponsePart;

/**
 * CachedBlockController
 */
class CachedBlockController extends BlockController
{
	/**
	 * @param ResponsePart $cachedResponse
	 */
	public function __construct(ResponsePart $cachedResponse, Block $block)
	{
		$this->response = $cachedResponse;
		$this->block = $block;
	}

	/**
	 * @throws \LogicException
	 */
	final public function doExecute()
	{
		throw new \LogicException('Cached block controller should not be executed.');
	}

	/**
	 * @param Request $request
	 * @return ResponsePart
	 */
	public function createBlockResponse(Request $request)
	{
		return $this->response;
	}
}
