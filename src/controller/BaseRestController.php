<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\controller;

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

}
