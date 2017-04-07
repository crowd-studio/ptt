<?php

use Doctrine\ORM\Mapping as ORM;

/** @ORM\MappedSuperclass */
class MappedSuperclassBase
{
    /** @ORM\Column(type="integer") */
    protected $mapped1;
    /** @ORM\Column(type="string") */
    protected $mapped2;

    /**
     * @ORM\OneToOne(targetEntity="ModuleRow")
     * @ORM\JoinColumn(name="related1_id", referencedColumnName="id")
     */
    protected $row;

    // ... more fields and methods
}
