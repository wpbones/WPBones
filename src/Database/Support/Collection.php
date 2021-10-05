<?php

namespace WPKirk\WPBones\Database\Support;

use ArrayObject;

if (!defined('ABSPATH')) {
    exit;
}

class Collection extends ArrayObject
{
    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     * @return void
     */
    public function __construct($items = [])
    {
        parent::__construct($items, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Return the first item from the collection.
     */
    public function first()
    {
        return $this->offsetGet(0);
    }

    /**
     * Return the last item from the collection.
     */
    public function last()
    {
        return $this->offsetGet($this->count() - 1);
    }

    /**
     * Return a JSON representation of the collection.
     *
     * @return string
     */
    public function __toString()
    {
        //error_log(print_r($this->getArrayCopy(), true));

        $array = array_map(function ($e) {
            return json_decode((string) $e);
        }, $this->getArrayCopy());

        error_log(print_r($array, true));

        return json_encode($array);
    }

    /**
     * Return a JSON pretty version of the collection.
     *
     * @return string
     */
    public function dump()
    {
        return json_encode(json_decode((string) $this), JSON_PRETTY_PRINT);
    }
}
