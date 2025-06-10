<?php

namespace Ondapresswp\WPBones\Html;

use Ondapresswp\WPBones\Support\Traits\HasAttributes;

abstract class HtmlTag
{
  use HasAttributes;

  /**
   * Global common HTML tag attributes.
   *
   * See http://www.w3schools.com/tags/default.asp for definitions
   *
   * @var array
   */
  protected $globalAttributes = [
    'accesskey'       => null,
    'contenteditable' => null,
    'contextmenu'     => null,
    'dir'             => null,
    'draggable'       => null,
    'dropzone'        => null,
    'hidden'          => null,
    'id'              => null,
    'lang'            => null,
    'spellcheck'      => null,
    'style'           => null,
    'tabindex'        => null,
    'title'           => null,
  ];

  /**
   * HTML tag attributes.
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * Callable fluent HTML tag attributes but not formatted.
   *
   * @var array
   */
  protected $guardedAttributes = [];

  /**
   * HTML Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [];

  /**
   * This is the content of a Html tag, suc as <div>{content}</div>
   *
   * @var string
   */
  protected $content = '';

  /**
   * Class attribute stack.
   *
   * @var array
   */
  private $_class = [];

  /**
   * Data attribute stack.
   *
   * @var array
   */
  private $_data = [];

  /*
  |--------------------------------------------------------------------------
  | Custom attributes
  |--------------------------------------------------------------------------
  |
  | You can use the ::attributes to get all attributes or set you own attributes.
  |
  */

  /**
   * HtmlTag constructor.
   *
   * @param array $arguments
   */
  public function __construct($arguments = [])
  {
    if (!empty($arguments)) {
      if (is_array($arguments)) {
        foreach ($arguments as $key => $value) {
          if (in_array($key, array_keys($this->globalAttributes))) {
            $this->globalAttributes[$key] = $value;
          } elseif (in_array($key, array_keys($this->attributes))) {
            $this->attributes[$key] = $value;
          } elseif ('content' == $key) {
            $this->content = $value;
          } elseif ('class' == $key) {
            $this->class = $value;
          }
        }
      } elseif (is_string($arguments)) {
        $this->content = $arguments;
      }
    }
  }

  protected function getAttributesAttribute(): array
  {
    return $this->attributes;
  }

  protected function getStyleAttribute()
  {
    if (empty($this->globalAttributes['style'])) {
      return '';
    }

    $styles = explode(';', $this->globalAttributes['style']);

    $stack = [];
    foreach ($styles as $style) {
      [$key, $value] = explode(':', $style, 2);
      $stack[$key] = $value;
    }

    return $stack;
  }

  protected function getClassAttribute(): string
  {
    return implode(' ', $this->_class);
  }

  protected function setClassAttribute($value)
  {
    if (is_string($value)) {
      $value = explode(' ', $value);
    }

    $this->_class = array_unique(array_merge($this->_class, $value));
  }

  protected function getAcceptcharsetAttribute()
  {
    return $this->attributes['accept-charset'];
  }

  protected function setAcceptcharsetAttribute($value)
  {
    $this->attributes['accept-charset'] = $value;
  }

  protected function afterCloseTag()
  {
    return '';
  }

  public function attributes($values)
  {
    if (is_array($values)) {
      $this->attributes = array_merge($this->attributes, $values);
    } elseif (is_string($values) && func_num_args() > 1) {
      $this->attributes[$values] = func_get_arg(1);
    }

    return $this;
  }

  /*
  |--------------------------------------------------------------------------
  | Special attributes
  |--------------------------------------------------------------------------
  |
  | Here you'll find some special attributes.
  |
  */

  public function style()
  {
    if (func_num_args() > 1) {
      $stack = [];
      $args  = array_chunk(func_get_args(), 2);
      foreach ($args as $style) {
        $stack[$style[0]] = $style[1];
      }
    } elseif (is_array(func_get_arg(0))) {
      $stack = func_get_arg(0);
    }

    // convert the array to styles, eg: "color:#fff;border:none;"
    $styles = [];
    foreach ($stack as $key => $value) {
      $styles[] = sprintf('%s:%s', $key, $value);
    }

    $this->globalAttributes['style'] = implode(';', $styles);

    return $this;
  }

  public function data()
  {
    if (func_num_args() > 1) {
      $args = array_chunk(func_get_args(), 2);
      foreach ($args as $data) {
        $this->_data[$data[0]] = $data[1];
      }
    } elseif (is_array(func_get_arg(0))) {
      foreach (func_get_arg(0) as $key => $value) {
        $this->_data[$key] = $value;
      }
    }

    $this->_data = array_unique($this->_data, SORT_REGULAR);

    return $this;
  }

  public function getDataAttribute()
  {
    return $this->_data;
  }

  public function __get($name)
  {
    if ($this->hasGetMutator($name)) {
      return $this->mutateAttribute($name);
    }

    if (in_array($name, array_keys($this->globalAttributes))) {
      return is_null($this->globalAttributes[$name]) ? '' : $this->globalAttributes[$name];
    }

    if (in_array($name, array_keys($this->attributes))) {
      return is_null($this->attributes[$name]) ? '' : $this->attributes[$name];
    }

    if (in_array($name, array_keys($this->guardedAttributes))) {
      return is_null($this->guardedAttributes[$name]) ? '' : $this->guardedAttributes[$name];
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Common
  |--------------------------------------------------------------------------
  |
  |
  */

  public function __set($name, $value)
  {
    if ($this->hasSetMutator($name)) {
      return $this->setMutatedAttributeValue($name, $value);
    }

    if (in_array($name, array_keys($this->globalAttributes))) {
      return $this->globalAttributes[$name] = $value;
    }

    if (in_array($name, array_keys($this->attributes))) {
      return $this->attributes[$name] = $value;
    }

    if (in_array($name, array_keys($this->guardedAttributes))) {
      return $this->guardedAttributes[$name] = $value;
    }
  }

  /**
   * Get the string presentation of the tag.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->html();
  }

  /**
   * Get the HTML output.
   *
   * @return string
   */
  public function html()
  {
    ob_start();

    // before open tag
    echo $this->beforeOpenTag();

    // open tag
    $this->echo_space($this->openTag());

    echo $this->formatGlobalAttributes();

    echo $this->formatAttributes();

    echo $this->formatDataAttributes();

    // class
    if (!empty($this->_class)) {
      $this->echo_space(sprintf('class="%s"', implode(' ', $this->_class)));
    }

    // close and put content content
    $this->closeTagWithContent();

    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  protected function beforeOpenTag()
  {
    return '';
  }

  private function echo_space($value)
  {
    echo $value . ' ';
  }

  private function openTag()
  {
    return $this->markup[0];
  }

  public function formatGlobalAttributes()
  {
    // global attributes
    $stack = [];
    foreach ($this->globalAttributes as $attribute => $value) {
      if (!is_null($value)) {
        $stack[] = sprintf('%s="%s"', $attribute, htmlspecialchars(stripslashes($value)));
      }
    }

    return implode(' ', $stack) . ' ';
  }

  // You can override this method

  public function formatAttributes()
  {
    // html tag attributes
    $stack = [];
    foreach ($this->attributes as $attribute => $value) {
      if (!is_null($value) && !is_array($value)) {
        $stack[] = sprintf('%s="%s"', $attribute, htmlspecialchars(stripslashes($value)));
      }
    }

    return implode(' ', $stack) . ' ';
  }

  // You can override this method

  public function formatDataAttributes()
  {
    // data attributes
    $stack = [];
    foreach ($this->_data as $attribute => $value) {
      if (!is_null($value)) {
        $stack[] = sprintf('data-%s="%s"', $attribute, htmlspecialchars(stripslashes($value)));
      }
    }

    return implode(' ', $stack) . ' ';
  }

  private function closeTagWithContent()
  {
    if ('/>' == $this->closeTag()) {
      echo $this->closeTag();
      echo $this->content;
    } else {
      echo '>';
      echo $this->content;
      echo $this->closeTag();
    }
  }

  private function closeTag()
  {
    return $this->markup[1];
  }

  public function __call($name, $arguments)
  {
    if (in_array($name, array_keys($this->globalAttributes))) {
      $this->globalAttributes[$name] = $arguments[0];
    } elseif (in_array($name, array_keys($this->attributes))) {
      $this->attributes[$name] = $arguments[0];
    } elseif (in_array($name, array_keys($this->guardedAttributes))) {
      $this->guardedAttributes[$name] = $arguments[0];
    } else {
      $this->__set($name, $arguments[0]);
    }

    return $this;
  }

  /**
   * Display the HTML output.
   *
   */
  public function render()
  {
    echo $this->html();
  }
}
