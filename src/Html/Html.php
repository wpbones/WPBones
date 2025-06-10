<?php

namespace Ondapresswp\WPBones\Html;

class Html
{
    protected static $htmlTags = [
    'a'        => '\Ondapresswp\WPBones\Html\HtmlTagA',
    'button'   => '\Ondapresswp\WPBones\Html\HtmlTagButton',
    'checkbox' => '\Ondapresswp\WPBones\Html\HtmlTagCheckbox',
    'datetime' => '\Ondapresswp\WPBones\Html\HtmlTagDatetime',
    'fieldset' => '\Ondapresswp\WPBones\Html\HtmlTagFieldSet',
    'form'     => '\Ondapresswp\WPBones\Html\HtmlTagForm',
    'input'    => '\Ondapresswp\WPBones\Html\HtmlTagInput',
    'label'    => '\Ondapresswp\WPBones\Html\HtmlTagLabel',
    'optgroup' => '\Ondapresswp\WPBones\Html\HtmlTagOptGroup',
    'option'   => '\Ondapresswp\WPBones\Html\HtmlTagOption',
    'select'   => '\Ondapresswp\WPBones\Html\HtmlTagSelect',
    'textarea' => '\Ondapresswp\WPBones\Html\HtmlTagTextArea',
  ];

    public static function __callStatic($name, $arguments)
    {
        if (in_array($name, array_keys(self::$htmlTags))) {
            $args = (isset($arguments[ 0 ]) && ! is_null($arguments[ 0 ])) ? $arguments[ 0 ] : [];

            return new self::$htmlTags[ $name ]($args);
        }
    }
}
