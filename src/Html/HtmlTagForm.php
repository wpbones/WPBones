<?php

namespace Ondapresswp\WPBones\Html;

class HtmlTagForm extends HtmlTag
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
    'accept-charset' => null,
    'action'         => null,
    'autocomplete'   => null,
    'enctype'        => null,
    'method'         => null,
    'name'           => null,
    'novalidate'     => null,
    'target'         => null
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<form', '</form>' ];

}