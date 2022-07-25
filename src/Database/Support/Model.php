<?php

namespace WPKirk\WPBones\Database\Support;

use WPKirk\WPBones\Database\QueryBuilder;
use WPKirk\WPBones\Support\Str;

if (!defined('ABSPATH')) {
  exit;
}

class Model
{
  /**
   * The queryBuilder instance.
   *
   * @var QueryBuilder
   */
  protected $queryBuilder;

  /**
   * A key-value array of attributes of the attributes.
   *
   * @var array
   */
  protected $attributes = [];


  public function __construct($attributes, $queryBuilder)
  {
    $this->attributes   = $attributes;
    $this->queryBuilder = $queryBuilder;
  }

  /*
  |--------------------------------------------------------------------------
  | Public static and instance methods
  |--------------------------------------------------------------------------
  |
  |
  */

  /**
   * Delete the attributes from the database.
   */
  public function delete(): QueryBuilder
  {
    return $this->newQueryBuilder()
                ->where($this->getPrimaryKey(), $this->getPrimaryKeyValue())->delete();
  }

  /**
   * Create a new queryBuilder instance.
   *
   * @return QueryBuilder
   */
  protected function newQueryBuilder()
  {
    return new QueryBuilder($this->queryBuilder->getTable(), $this->queryBuilder->getPrimaryKey());
  }

  /**
   * Return the primary key name.
   *
   * @return string
   */
  protected function getPrimaryKey()
  {
    return $this->queryBuilder->getPrimaryKey();
  }

  /*
  |--------------------------------------------------------------------------
  | Internal methods
  |--------------------------------------------------------------------------
  |
  |
  */

  /**
   * Return the primary key value.
   *
   * @return string
   */
  protected function getPrimaryKeyValue(): string
  {
    return $this->attributes[$this->queryBuilder->getPrimaryKey()];
  }

  /**
   * Save the attributes to the database.
   * It's an alias of update().
   */
  public function save()
  {
    return $this->update();
  }

  /**
   * Update the attributes in the database.
   */
  public function update($attributes = null)
  {
    $attributes = $attributes ?: $this->attributes;

    return $this->newQueryBuilder()
                ->where($this->getPrimaryKey(), $this->getPrimaryKeyValue())->update($attributes);
  }

  /**
   * Return the value of the given attribute.
   */
  public function __get($name)
  {
    $model    = $this->getParentModel();
    $accessor = 'get' . Str::studly($name) . 'Attribute';

    if (isset($this->attributes[$name])) {

      // check if an accessor exists for this attribute
      if (method_exists($model, $accessor)) {
        return $model->{$accessor}($this->attributes[$name]);
      }

      return $this->attributes[$name];
    }

    // check if an accessor exists for this attribute
    if (method_exists($model, $accessor)) {
      return $model->{$accessor}($this);
    }
  }


  /*
  |--------------------------------------------------------------------------
  | Magic methods
  |--------------------------------------------------------------------------
  |
  |
  */

  /**
   * Set the value of the given attribute.
   */
  public function __set($name, $value)
  {
    $model   = $model = $this->getParentModel();
    $mutator = 'set' . Str::studly($name) . 'Attribute';

    if (isset($this->attributes[$name])) {

      // check if an accessor exists for this attribute
      if (method_exists($model, $mutator)) {
        $model->{$mutator}($value);

        return $this->attributes = $model->attributes;
      }
    }

    $this->attributes[$name] = $value;
  }

  /**
   * Return the parent model to be able ti use accessor and mutator methods.
   * Here also we're going to inject the attributes to the model.
   *
   * @return mixed
   */
  protected function getParentModel()
  {
    $model = $this->queryBuilder->getParentModel();
    // inject the attributes to the model
    $model->attributes = $this->attributes;

    return $model;
  }

  /**
   * Return the JSON representation of the attributes.
   *
   * @return string
   */
  public function __toString()
  {
    return json_encode($this->attributes);
  }

  /**
   * Return a JSON pretty version of the attributes.
   *
   * @return string
   */
  public function dump(): string
  {
    return json_encode($this->attributes, JSON_PRETTY_PRINT);
  }
}
