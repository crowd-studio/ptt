<?php
namespace AppBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
* @Annotation
* 
*/
final class PttAnnotation extends Annotation
{
	public $name;
	public $type;
	public $options;
	public $class;
}