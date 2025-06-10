<?php

namespace Ondapresswp\WPBones\Html;

class HtmlTagLabel extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'for'  => null,
    'form' => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<label', '</label>' ];

}