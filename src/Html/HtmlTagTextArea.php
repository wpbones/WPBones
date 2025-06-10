<?php

namespace Ondapresswp\WPBones\Html;

use Ondapresswp\WPBones\Html\HtmlTagOption;

class HtmlTagTextArea extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'autofocus'   => null,
    'cols'        => null,
    'disabled'    => null,
    'form'        => null,
    'maxlength'   => null,
    'name'        => null,
    'placeholder' => null,
    'readonly'    => null,
    'required'    => null,
    'rows'        => null,
    'wrap'        => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<textarea', '</textarea>' ];

}