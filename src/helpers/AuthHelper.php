<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\helpers;

use Yii;
use shopack\base\backend\helpers\PrivHelper;
use shopack\base\backend\helpers\PhoneHelper;
use yii\web\UnauthorizedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
use shopack\base\common\helpers\ArrayHelper;
use shopack\aaa\backend\models\UserModel;
use shopack\aaa\backend\models\SessionModel;
use shopack\aaa\backend\models\RoleModel;
use shopack\aaa\common\enums\enuRole;
use shopack\aaa\common\enums\enuUserStatus;
use shopack\aaa\common\enums\enuSessionStatus;

class AuthHelper
{
  const PHRASETYPE_EMAIL  = 'E';
  const PHRASETYPE_MOBILE = 'M';
  const PHRASETYPE_SSID   = 'S';
  const PHRASETYPE_NONE   = 'N';

  static function isEmail($email)
  {
    if (strpos($email, '@') !== false) {
      if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
        return true;

      throw new UnprocessableEntityHttpException('Invalid email address');
    }

    return false;
  }

  static function recognizeLoginPhrase($input, $checkSSID = true)
  {
    $input = strtolower(trim($input));

    if (empty($input))
      return [$input, static::PHRASETYPE_NONE];

    //email
    if (static::isEmail($input))
      return [$input, static::PHRASETYPE_EMAIL];

    //mobile
    try {
      $phone = PhoneHelper::normalizePhoneNumber($input);
      if ($phone)
        return [$phone, static::PHRASETYPE_MOBILE];
    } catch(\Exception $exp) {
      $message = $exp->getMessage();
    }

    //ssid
    if ($checkSSID) {
      $sidMatched = preg_match('/^[0-9]{8,10}$/', $input);
      if ($sidMatched === 1)
        return [$input, static::PHRASETYPE_SSID];
    }

    //
    return [$input, static::PHRASETYPE_NONE];
  }

  static function checkLoginPhrase($input, $checkSSID = true)
  {
    list ($normalizedInput, $type) = static::recognizeLoginPhrase($input, $checkSSID);

    if ($type == AuthHelper::PHRASETYPE_NONE)
      throw new UnprocessableEntityHttpException('Invalid input');

    return [$normalizedInput, $type];
  }

  static function doLogin($user, bool $rememberMe = false, ?Array $additionalInfo = [])
  {
    if ($user->usrStatus == enuUserStatus::NewForLoginByMobile) {
      $user->usrStatus = enuUserStatus::Active;
      $user->save();
    }

    //create session
    //-----------------------
    $sessionModel = new SessionModel();
    $sessionModel->ssnUserID = $user->usrID;
    if ($sessionModel->save() == false)
      throw new UnauthorizedHttpException(implode("\n", $sessionModel->getFirstErrors()));

    //privs
    //-----------------------
    $privs = [];

    if ($user->usrStatus != enuUserStatus::NewForLoginByMobile) {
      if ((empty($user->usrEmail) == false && empty($user->usrEmailApprovedAt))
        || (empty($user->usrMobile) == false && empty($user->usrMobileApprovedAt))
      ) {
        //set to user role until signup email or mobile approved
        $role = RoleModel::findOne(['rolID' => enuRole::User]);
        if (empty($role->rolPrivs) == false)
          $privs = $role->rolPrivs;
      } else {
        if (empty($user->usrRoleID) == false) {
          $role = $user->role;
          if (empty($role->rolPrivs) == false)
            $privs = array_replace_recursive($privs, $role->rolPrivs);
        }

        if (empty($user->usrPrivs) == false)
          $privs = array_replace_recursive($privs, $user->usrPrivs);
      }

      PrivHelper::digestPrivs($privs);
    }

    //token
    //-----------------------
    $settings = Yii::$app->params['settings'];
    $ttl = ArrayHelper::getValue($settings['AAA']['jwt'], 'ttl', 5 * 60);

    $now = new \DateTimeImmutable();
    $expire = $now->modify("+{$ttl} second");

    $token = Yii::$app->jwt->getBuilder()
      ->identifiedBy($sessionModel->ssnID) //Yii::$app->session->id)	// Configures the id (jti claim)
      ->issuedAt($now)
      ->expiresAt($expire)
      ->withClaim('privs', $privs)
      ->withClaim('uid', $user->usrID)
      ->withClaim('email', $user->usrEmail)
      ->withClaim('mobile', $user->usrMobile)
      // ->withClaim('firstName', $model->user->usrFirstName)
      // ->withClaim('lastName', $model->user->usrLastName)
    ;

    if ($rememberMe)
      $token->withClaim('rmmbr', 1);

    if (empty($additionalInfo) == false) {
      foreach ($additionalInfo as $k => $v) {
        $token->withClaim($k, $v);
      }
    }

    $mustApprove = [];
    if ($user->usrStatus != enuUserStatus::NewForLoginByMobile) {
      if (empty($user->usrEmail) == false && empty($user->usrEmailApprovedAt))
        $mustApprove[] = 'email';
      if (empty($user->usrMobile) == false && empty($user->usrMobileApprovedAt))
        $mustApprove[] = 'mobile';
      if (empty($mustApprove) == false)
        $token->withClaim('mustApprove', implode(',', $mustApprove));
    }

    $token = $token
      ->getToken(
        Yii::$app->jwt->getConfiguration()->signer(),
        Yii::$app->jwt->getConfiguration()->signingKey()
      )
      ->toString();

    //update session
    //-----------------------
    $sessionModel->ssnJWT = $token;
    $sessionModel->ssnStatus = ($user->usrStatus == enuUserStatus::NewForLoginByMobile
      ? enuSessionStatus::ForLoginByMobile
      : enuSessionStatus::Active);
    $sessionModel->ssnExpireAt = $expire->format('Y-m-d H:i:s');
    $sessionModel->save();

    //-----------------------
    return [$token, $mustApprove, $sessionModel];
  }

  static function logout()
  {
    if (!Yii::$app->user->accessToken)
      return;

    $sessionID = Yii::$app->user->accessToken->claims()->get(\Lcobucci\JWT\Token\RegisteredClaims::ID);
    if ($sessionID == null)
      throw new NotFoundHttpException("Session not found");

    $rowsAffected = SessionModel::deleteAll([
      'ssnID' => $sessionID,
    ]);

    if ($rowsAffected != 1)
      throw new NotFoundHttpException("Could not log out");

    Yii::$app->user->accessToken = null;
  }

}
