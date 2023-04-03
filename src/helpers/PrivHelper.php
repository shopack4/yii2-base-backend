<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\helpers;

use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\ForbiddenHttpException;

class PrivHelper
{
	static function digestPrivs(&$privs)
	{
		if (array_key_exists('*', $privs))
		{
			$privs = ['*' => $privs['*']];
			return;
		}

		foreach ($privs as $key => &$value)
		{
			if (is_array($value))
				static::digestPrivs($value);
		}
	}

	static function hasPriv($path, $priv='1')
	{
		if (empty($path))
			throw new ServerErrorHttpException('path is empty');

		if (Yii::$app->user->accessToken == null)
			return false;

		$privs = Yii::$app->user->accessToken->claims()->get('privs', []);
		if (empty($privs))
			return false;

		$pathParts = explode('/', $path);
		$pathPartsCount = count($pathParts);
		$currPartCounter = 0;

		$privsForLookup = $privs;
		foreach ($pathParts as $pathItem) {
			if (is_array($privsForLookup) == false)
				throw new ServerErrorHttpException('checking part of priv is not array');

			// just '*':1
			if (isset($privsForLookup['*']) && $privsForLookup['*'])
				return true;

			if (empty($privs[$pathItem]))
				return false;

			$privsForLookup = $privs[$pathItem];
			++$currPartCounter;

			if ($currPartCounter == $pathPartsCount)
			{
				if (is_array($privsForLookup))
					throw new ServerErrorHttpException('last checking part of priv is not leaf');

				if ($pathItem == 'crud') {
					if (is_string($privsForLookup) == false)
						throw new ServerErrorHttpException('defined priv is not string');

					//all source digits are 0
					if (intval($privsForLookup) == 0)
						return 0;

					if (is_string($priv) == false)
						throw new ServerErrorHttpException('checking priv is not string');

					//all target digits are 0
					if (intval($priv) == 0)
						throw new ServerErrorHttpException('all digits of priv are zero');

					if (strlen($privsForLookup) != strlen($priv))
						throw new ServerErrorHttpException('length of compare privs are not equal');

					for ($i=0; $i<strlen($priv); $i++) {
						if ($priv[$i] == '1' && $privsForLookup[$i] != 1)
							return false;
					}

					return true;
				}

				return $privsForLookup;
			}
		}

		return false;
	}

	static function checkPriv($path, $priv='1')
	{
		if (static::hasPriv($path, $priv) == false)
			throw new ForbiddenHttpException('access denied');

		return true;
	}

}
