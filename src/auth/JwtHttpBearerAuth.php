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

		$this->getJwtComponent()->assert($token);

		return $token;
	}

}