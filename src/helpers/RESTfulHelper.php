<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\helpers;

// use yii\web\UnprocessableEntityHttpException;

class RESTfulHelper
{
  static function queryAllToResponse(
    $query,
    $pageIndex,
    $pageSize
  ) {
    $rows = $query->all();
		$totalCount = $query->count();

		return [
			'totalCount' => $totalCount,
			'rows' => $rows,
      'pageIndex' => $pageIndex,
      'pageSize' => $pageSize,
		];
  }

  static function modelToResponse($model)
  {
    return [
      'data' => $model,
    ];
  }

}
