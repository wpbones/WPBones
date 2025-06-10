<?php

namespace Ondapresswp\WPBones\Traits;

trait SupportMultisite
{
    use MultisitePrefix;
    function __construct()
    {
        if (!is_null($this->prefix)&&!str_contains(get_current_blog_id() . "_", $this->prefix))
            $this->prefix = get_current_blog_id() . "_";
    }

}
