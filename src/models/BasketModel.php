<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\models;

use Yii;
use yii\base\Model;
use yii\db\Expression;

class BasketModel extends Model
{
	use \shopack\base\common\models\BasketModelTrait;

	//convert to json and sign it
	public function getPrevoucher()
	{

	}

}
