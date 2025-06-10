<?php

namespace Ondapresswp\WPBones\Traits;

trait DontUseMultisite
{
    use MultisitePrefix;

    protected $prefix = "";

}
