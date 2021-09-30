<?php

namespace WPKirk\WPBones\Html;

class HtmlTagCheckbox extends HtmlTagInput
{

  /**
   * Html Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = ['<input type="checkbox"', '/>'];

  /**
   * @suppress PHP0418
   */
  protected function beforeOpenTag()
  {
    echo Html::input(
    [
      'type' => 'hidden',
      'name' => $this->name,
      'value' => false
    ]
    );
  }

  public function checked($value)
  {
    if (in_array(strtolower($value), ['', 'false', '0', 'no', 'n', 'off', null])) {
      $this->attributes['checked'] = null;
      return $this;
    }

    $this->attributes['checked'] = 'checked';

    return $this;
  }

}