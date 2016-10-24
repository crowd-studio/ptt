<?php
namespace PttBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
* @Annotation
* @Target("CLASS")
*/
final class PttAnnotation extends Annotation
{
	public $defaultField;
	public $cache;
}