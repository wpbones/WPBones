<?php

namespace Ondapresswp\WPBones\Html;

class HtmlTagFieldSet extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'disabled' => null,
    'form'     => null,
    'name'     => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<fieldset', '</fieldset>' ];

}