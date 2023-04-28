<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\auth;

use bizley\jwt\JwtHttpBearerAuth as BaseJwtHttpBearerAuth;
use Lcobucci\JWT\Token;

class JwtHttpBearerAuth extends BaseJwtHttpBearerAuth
{
	public function processToken(string $data): ?Token
	{
		$token = $this->getJwtComponent()->parse($data);

		try
		{
			$this->getJwtComponent()->assert($token);
		}
		catch (\Throwable $th)
		{
			$rememberMe = $token->claims()->get('rmmbr');

			if ($rememberMe) {

			}

			throw $th;
		}

		return $token;
	}

}
