<?php

namespace WPKirk\WPBones\Html;

class HtmlTagInput extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'accept'         => null,
    'align'          => null,
    'alt'            => null,
    'autocomplete'   => null,
    'autofocus'      => null,
    'checked'        => null,
    'disabled'       => null,
    'form'           => null,
    'formaction'     => null,
    'formenctype'    => null,
    'formmethod'     => null,
    'formnovalidate' => null,
    'formtarget'     => null,
    'height'         => null,
    'list'           => null,
    'max'            => null,
    'maxlength'      => null,
    'min'            => null,
    'multiple'       => null,
    'name'           => null,
    'pattern'        => null,
    'placeholder'    => null,
    'readonly'       => null,
    'required'       => null,
    'size'           => null,
    'src'            => null,
    'step'           => null,
    'type'           => null,
    'value'          => null,
    'width'          => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<input', '/>' ];

}