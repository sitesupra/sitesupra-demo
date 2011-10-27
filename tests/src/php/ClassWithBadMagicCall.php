<?php

class ClassWithBadMagicCall
{
	private function __call($name, $args){}
}
