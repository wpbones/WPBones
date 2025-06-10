<?php

namespace Ondapresswp\WPBones\Html;

class HtmlTagButton extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'autofocus'      => null,
    'disabled'       => null,
    'form'           => null,
    'formaction'     => null,
    'formenctype'    => null,
    'formmethod'     => null,
    'formnovalidate' => null,
    'formtarget'     => null,
    'name'           => null,
    'type'           => null,
    'value'          => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<button', '</button>' ];

}