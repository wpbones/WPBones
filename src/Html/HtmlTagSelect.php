<?php

namespace Ondapresswp\WPBones\Html;

use Ondapresswp\WPBones\Html\HtmlTagOption;

class HtmlTagSelect extends HtmlTag
{
  /**
   * Attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $attributes = [
    'autofocus' => null,
    'disabled'  => null,
    'form'      => null,
    'multiple'  => null,
    'name'      => null,
    'size'      => null,
    'selected'  => null,
  ];

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = ['<select', '</select>'];

  private $_options = null;

  public function html()
  {
    if (!is_null($this->_options)) {
      $items = $this->_options;

      $stack = [];
      $flattenedSelected = is_null($this->selected) ? [] : wpbones_flatten_and_uniquify($this->selected);

      foreach ($items as $key => $value) {
        $option = new HtmlTagOption($value);
        if (is_string($key)) {
          $option->value = $key;
        }

        if (in_array($key, $flattenedSelected)) {
          $option->selected = 'selected';
        }

        $stack[] = $option->html();
      }

      $this->content = implode('', $stack);
    }

    return parent::html();
  }

  public function options($items)
  {
    $this->_options = $items;

    return $this;
  }
}
