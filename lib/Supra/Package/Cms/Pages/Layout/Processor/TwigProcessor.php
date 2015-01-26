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

namespace Supra\Package\Cms\Pages\Layout\Processor;

use Symfony\Component\HttpFoundation\Response;
use Supra\Package\Cms\Pages\Twig\PlaceHolderNodeCollector;

/**
 * Twig layout processor
 */
class TwigProcessor implements ProcessorInterface
{
	/**
	 * @var \Twig_Environment
	 */
	protected $twig;

	/**
	 * @param \Twig_Environment $twig
	 */
	public function __construct(\Twig_Environment $twig)
	{
		$this->twig = $twig;
	}

	/**
	 * {@inheritDoc}
	 */
	public function process($layoutSrc, Response $response, array $placeResponses)
	{
		if (! $this->twig->hasExtension('supraPage')) {
			throw new \UnexpectedValueException('Missing for Supra Page extension.');
		}

		$response->setContent(
				$this->twig->render($layoutSrc, array('responses' => $placeResponses))
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPlaces($layoutSrc)
	{
		$tokenStream = $this->twig->tokenize(
				$this->twig->getLoader()->getSource($layoutSrc)
		);

		$collector = new PlaceHolderNodeCollector();
		$traverser = new \Twig_NodeTraverser($this->twig, array($collector));

		$traverser->traverse($this->twig->parse($tokenStream));

		return $collector->getCollectedNames();
	}
}