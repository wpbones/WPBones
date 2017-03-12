<?php

namespace WPKirk\WPBones\Html;

use WPKirk\WPBones\Support\Str;

abstract class HtmlTag
{

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
  protected $attributes = [ ];

  /**
   * Callable fluent HTML tag attributes but not formatted.
   *
   * @var array
   */
  protected $guardedAttributes = [ ];

  /**
   * HTML Tag markup, open and close.
   *
   * @var array
   */
  protected $markup = [ ];

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
  private $_class = [ ];

  /**
   * Data attribute stack.
   *
   * @var array
   */
  private $_data = [ ];

  /*
  |--------------------------------------------------------------------------
  | Custom attributes
  |--------------------------------------------------------------------------
  |
  | You can use the ::attributes to get all attributes or set you own attributes.
  |
  */
  protected function getAttributesAttribute()
  {
    return $this->attributes;
  }

  public function attributes( $values )
  {
    if ( is_array( $values ) ) {
      $this->attributes = array_merge( $this->attributes, $values );
    }
    elseif ( is_string( $values ) && func_num_args() > 1 ) {
      $this->attributes[ $values ] = func_get_arg( 1 );
    }

    return $this;
  }

  public function formatAttributes(  )
  {
    // html tag attributes
    $stack = [ ];
    foreach ( $this->attributes as $attribute => $value ) {
      if ( ! is_null( $value ) ) {
        $stack[] = sprintf( '%s="%s"', $attribute, htmlspecialchars( stripslashes( $value ) ) );
      }
    }

    return implode( ' ', $stack ) . ' ';
  }

  public function formatGlobalAttributes(  )
  {
    // global attributes
    $stack = [ ];
    foreach ( $this->globalAttributes as $attribute => $value ) {
      if ( ! is_null( $value ) ) {
        $stack[] = sprintf( '%s="%s"', $attribute, htmlspecialchars( stripslashes( $value ) ) );
      }
    }

    return implode( ' ', $stack ) . ' ';
  }

  protected function getStyleAttribute()
  {
    if ( empty( $this->globalAttributes[ 'style' ] ) ) {
      return '';
    }

    $styles = explode( ';', $this->globalAttributes[ 'style' ] );

    $stack = [ ];
    foreach ( $styles as $style ) {
      list( $key, $value ) = explode( ':', $style, 2 );
      $stack[ $key ] = $value;
    }

    return $stack;
  }

  public function style()
  {
    if ( func_num_args() > 1 ) {
      $stack = [ ];
      $args  = array_chunk( func_get_args(), 2 );
      foreach ( $args as $style ) {
        $stack[ $style[ 0 ] ] = $style[ 1 ];
      }
    }
    elseif ( is_array( func_get_arg( 0 ) ) ) {
      $stack = func_get_arg( 0 );
    }

    // conver the array to styles, eg: "color:#fff;border:none;"
    $styles = [ ];
    foreach ( $stack as $key => $value ) {
      $styles[] = sprintf( '%s:%s', $key, $value );
    }

    $this->globalAttributes[ 'style' ] = implode( ';', $styles );

    return $this;
  }

  public function data() {
    if ( func_num_args() > 1 ) {
      $args  = array_chunk( func_get_args(), 2 );
      foreach ( $args as $data ) {
        $this->_data[ $data[ 0 ] ] = $data[ 1 ];
      }
    }
    elseif ( is_array( func_get_arg( 0 ) ) ) {
      foreach( func_get_arg( 0 ) as $key => $value ) {
        $this->_data[ $key ] =$value;
      }
    }

    $this->_data = array_unique( $this->_data, SORT_REGULAR );

    return $this;
  }

  public function getDataAttribute() {
    return $this->_data;
  }

  public function formatDataAttributes(  )
  {
    // data attributes
    $stack = [ ];
    foreach ( $this->_data as $attribute => $value ) {
      if ( ! is_null( $value ) ) {
        $stack[] = sprintf( 'data-%s="%s"', $attribute, htmlspecialchars( stripslashes( $value ) ) );
      }
    }

    return implode( ' ', $stack ) . ' ';
  }

  /*
  |--------------------------------------------------------------------------
  | Special attributes
  |--------------------------------------------------------------------------
  |
  | Here you'll find some special attributes.
  |
  */

  protected function getClassAttribute()
  {
    return implode( ' ', $this->_class );
  }

  protected function setClassAttribute( $value )
  {
    if ( is_string( $value ) ) {
      $value = explode( ' ', $value );
    }

    $this->_class = array_unique( array_merge( $this->_class, $value ) );
  }

  protected function getAcceptcharsetAttribute()
  {
    return $this->attributes[ 'accept-charset' ];
  }

  protected function setAcceptcharsetAttribute( $value )
  {
    $this->attributes[ 'accept-charset' ] = $value;
  }

  /*
  |--------------------------------------------------------------------------
  | Common
  |--------------------------------------------------------------------------
  |
  |
  */

  /**
   * HtmlTag constructor.
   *
   * @param array $arguments
   */
  public function __construct( $arguments = [ ] )
  {
    if ( ! empty( $arguments ) ) {
      if ( is_array( $arguments ) ) {
        foreach ( $arguments as $key => $value ) {
          if ( in_array( $key, array_keys( $this->globalAttributes ) ) ) {
            $this->globalAttributes[ $key ] = $value;
          }
          elseif ( in_array( $key, array_keys( $this->attributes ) ) ) {
            $this->attributes[ $key ] = $value;
          }
          elseif ( 'content' == $key ) {
            $this->content = $value;
          }
          elseif ( 'class' == $key ) {
            $this->class = $value;
          }
        }
      }
      elseif ( is_string( $arguments ) ) {
        $this->content = $arguments;
      }
    }
  }

  public function __get( $name )
  {
    $method = 'get' . Str::studly( $name ) . 'Attribute';
    if ( method_exists( $this, $method ) ) {
      return $this->{$method}();
    }

    if ( in_array( $name, array_keys( $this->globalAttributes ) ) ) {
      return is_null( $this->globalAttributes[ $name ] ) ? '' : $this->globalAttributes[ $name ];
    }

    if ( in_array( $name, array_keys( $this->attributes ) ) ) {
      return is_null( $this->attributes[ $name ] ) ? '' : $this->attributes[ $name ];
    }

    if ( in_array( $name, array_keys( $this->guardedAttributes ) ) ) {
      return is_null( $this->guardedAttributes[ $name ] ) ? '' : $this->guardedAttributes[ $name ];
    }
  }

  public function __set( $name, $value )
  {
    $method = 'set' . Str::studly( $name ) . 'Attribute';
    if ( method_exists( $this, $method ) ) {
      return $this->{$method}( $value );
    }

    if ( in_array( $name, array_keys( $this->globalAttributes ) ) ) {
      return $this->globalAttributes[ $name ] = $value;
    }

    if ( in_array( $name, array_keys( $this->attributes ) ) ) {
      return $this->attributes[ $name ] = $value;
    }

    if ( in_array( $name, array_keys( $this->guardedAttributes ) ) ) {
      return $this->guardedAttributes[ $name ] = $value;
    }
  }

  /**
   * Get the string rappresentation of the tag.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->html();
  }

  public function __call( $name, $arguments )
  {
    if ( in_array( $name, array_keys( $this->globalAttributes ) ) ) {
      $this->globalAttributes[ $name ] = $arguments[ 0 ];
    }
    elseif ( in_array( $name, array_keys( $this->attributes ) ) ) {
      $this->attributes[ $name ] = $arguments[ 0 ];
    }
    elseif ( in_array( $name, array_keys( $this->guardedAttributes ) ) ) {
      $this->guardedAttributes[ $name ] = $arguments[ 0 ];
    }
    else {
      $this->__set( $name, $arguments[ 0 ] );
    }

    return $this;

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
    $this->echo_space( $this->openTag() );

    echo $this->formatGlobalAttributes();

    echo $this->formatAttributes();

    echo $this->formatDataAttributes();

    // class
    if ( ! empty( $this->_class ) ) {
      $this->echo_space( sprintf( 'class="%s"', implode( ' ', $this->_class ) ) );
    }

    // close and put content content
    $this->closeTagWithContent();

    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  /**
   * Display the HTML output.
   *
   */
  public function render()
  {
    echo $this->html();
  }

  // You can override this method
  protected function beforeOpenTag()
  {
  }

  // You can override this method
  protected function afterCloseTag()
  {
  }

  private function closeTagWithContent()
  {
    if ( '/>' == $this->closeTag() ) {
      echo $this->closeTag();
      echo $this->content;
    }
    else {
      echo '>';
      echo $this->content;
      echo $this->closeTag();
    }
  }

  private function openTag()
  {
    return $this->markup[ 0 ];
  }

  private function closeTag()
  {
    return $this->markup[ 1 ];
  }

  private function echo_space( $value )
  {
    echo $value . ' ';
  }


}