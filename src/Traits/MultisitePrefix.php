<?php

namespace Ondapresswp\WPBones\Traits;

trait MultisitePrefix
{

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->getPrefix() . parent::getTable();
    }

    /**
     * Get the prefix associated with the model.
     *
     * @return string
     */
    public function getPrefix()
    {
        return is_null($this->prefix) ? '' : $this->prefix;
    }

    /**
     * Set the prefix associated with the model.
     *
     * @param  string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

}
