<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class MorphableHandler
{
  const SEPARATOR = '.';

  static function make(): self
  {
    return new self();
  }

  function getId(Model $model): string
  {
    return $this->buildId($model->getMorphClass(), $model->id);
    // return $model->getMorphClass() . self::SEPARATOR . $model->id;
  }

  function buildId($morphClass, $id): string
  {
    return $morphClass . self::SEPARATOR . $id;
  }

  function buildIdFromCourseable($courseable): string
  {
    return $courseable->courseable_type .
      self::SEPARATOR .
      $courseable->courseable_id;
  }

  function getData(string $urlParam)
  {
    $arr = explode(self::SEPARATOR, $urlParam);
    if (count($arr) !== 2) {
      throw abort(404, 'Invalid morphable data supplied');
    }
    return [$arr[0], $arr[1]];
  }

  function getModel(string $urlParam): Model
  {
    [$morphName, $id] = $this->getData($urlParam);

    $model = Relation::getMorphedModel($morphName);

    return $model::findOrFail($id);
  }
}
