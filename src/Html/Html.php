<?php

namespace WPKirk\WPBones\Html;

class Html
{

  protected static $htmlTags = [
    'a'        => '\WPKirk\WPBones\Html\HtmlTagA',
    'button'   => '\WPKirk\WPBones\Html\HtmlTagButton',
    'checkbox' => '\WPKirk\WPBones\Html\HtmlTagCheckbox',
    'datetime' => '\WPKirk\WPBones\Html\HtmlTagDatetime',
    'fieldset' => '\WPKirk\WPBones\Html\HtmlTagFieldSet',
    'form'     => '\WPKirk\WPBones\Html\HtmlTagForm',
    'input'    => '\WPKirk\WPBones\Html\HtmlTagInput',
    'label'    => '\WPKirk\WPBones\Html\HtmlTagLabel',
    'optgroup' => '\WPKirk\WPBones\Html\HtmlTagOptGroup',
    'option'   => '\WPKirk\WPBones\Html\HtmlTagOption',
    'select'   => '\WPKirk\WPBones\Html\HtmlTagSelect',
    'textarea' => '\WPKirk\WPBones\Html\HtmlTagTextArea',
  ];

  public static function __callStatic( $name, $arguments )
  {
    if ( in_array( $name, array_keys( self::$htmlTags ) ) ) {
      $args = ( isset( $arguments[ 0 ] ) && ! is_null( $arguments[ 0 ] ) ) ? $arguments[ 0 ] : [];

      return new self::$htmlTags[ $name ]( $args );
    }
  }
}