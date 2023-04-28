<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\controller;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use shopack\base\backend\controller\BaseController;
use shopack\base\backend\auth\JwtHttpBearerAuth;

class BaseRestController extends BaseController
{
	const BEHAVIOR_AUTHENTICATOR = 'authenticator';

	public function behaviors()
	{
		$behaviors = parent::behaviors();

		$behaviors[static::BEHAVIOR_AUTHENTICATOR] = [
			'class' => JwtHttpBearerAuth::class,
		];

		return $behaviors;
	}

	public function queryAllToResponse($query)
	{
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
		]);

		$totalCount = $dataProvider->getTotalCount();
		Yii::$app->response->headers->add('X-Pagination-Total-Count', $totalCount);

		if (Yii::$app->request->getMethod() == 'HEAD') {
			// $totalCount = $query->count();
			return null;
		}

		return [
			'data' => $dataProvider->getModels(),
			'pagination' => [
				'totalCount' => $totalCount,
			],
		];
  }

  public function modelToResponse($model)
  {
		if ($model == null)
			throw new NotFoundHttpException('The requested item not exist.');

		return $model;
  }

}
