<?php

namespace WPKirk\WPBones\Html;

class HtmlTagOption extends HtmlTag
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
    'label'    => null,
    'selected' => null,
    'value'    => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<option', '</option>' ];
}