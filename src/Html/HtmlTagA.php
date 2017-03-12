<?php

namespace WPKirk\WPBones\Html;

class HtmlTagA extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'charset'  => null,
    'coords'   => null,
    'href'     => null,
    'hreflang' => null,
    'media'    => null,
    'name'     => null,
    'rel'      => null,
    'rev'      => null,
    'shape'    => null,
    'target'   => null,
    'type'     => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ '<a', '</a>' ];

}