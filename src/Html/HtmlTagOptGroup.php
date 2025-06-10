<?php

namespace Ondapresswp\WPBones\Html;

class HtmlTagOptGroup extends HtmlTag
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
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<optgroup', '</optgroup>' ];

}