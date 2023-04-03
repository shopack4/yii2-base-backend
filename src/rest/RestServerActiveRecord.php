<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\rest;

use Yii;
use shopack\base\backend\rest\RestServerQuery;

abstract class RestServerActiveRecord extends \yii\db\ActiveRecord
	implements \shopack\base\common\rest\ActiveRecordInterface
{
	use \shopack\base\common\rest\ActiveRecordTrait;

  public $filterKey = 'filter';
	public $orderByKey = 'order-by';

	public static function find() //: RestServerQuery
	{
    $query = \Yii::createObject(RestServerQuery::class, [
      get_called_class()
    ]);

    return $query;
	}

	public function fillQueryFromRequest(\yii\db\ActiveQuery $query)
	{
		$queryParams = Yii::$app->request->getQueryParams();

		//-------------
		$this->_fillQueryOrderByPart($queryParams, $query);

		//-------------
		$this->_fillQueryFilterPart($queryParams, $query);

		//-------------
		foreach ($queryParams as $k => $v) {
			if ($this->hasAttribute($k))
				$query->andWhere([$k => $v]);
		}
	}

	private function _fillQueryFilterPart(&$queryParams, &$query)
	{
		if (empty($queryParams[$this->filterKey]))
			return;

		$query->where = json_decode($queryParams[$this->filterKey], true);

		// $filters =
		// foreach ($filters as $filter) {
		// 	$query->andWhere($filter);

		// 	// if ($this->hasAttribute($k))
		// 	// 	$query->andWhere([$k => $v]);
		// }

		unset ($queryParams[$this->filterKey]);
	}

	private function _fillQueryOrderByPart(&$queryParams, &$query)
	{
		if (empty($queryParams[$this->orderByKey]))
			return;

		$orders = explode(',', $queryParams[$this->orderByKey]);

		foreach ($orders as $order) {
			if (str_starts_with($order, '-'))
				$query->addOrderBy([substr($order, 1) => SORT_DESC]);
			else
				$query->addOrderBy([$order => SORT_ASC]);
		}

		unset ($queryParams[$this->orderByKey]);
	}

}
