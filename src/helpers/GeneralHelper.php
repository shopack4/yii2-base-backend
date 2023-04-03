<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\helpers;

// use yii\web\UnprocessableEntityHttpException;

class GeneralHelper
{
  static function formatTimeFromSeconds($seconds)
  {
    $days = intval($seconds / (24 * 60 * 60));
    $seconds -= $days * (24 * 60 * 60);

    $hours = intval($seconds / (60 * 60));
    $seconds -= $hours * (60 * 60);

    $minutes = intval($seconds / 60);
    $seconds -= $minutes * 60;

    $parts = [];

    if ($days > 0)
      $parts[] = $days;

    if (($days > 0) || ($hours > 0))
      $parts[] = $hours;

    if (($days > 0) || ($hours > 0) || ($minutes > 0))
      $parts[] = $minutes;

    $parts[] = $seconds;

    $result = implode(':', $parts);

    if (count($parts) == 1)
      $result = '0:' . $result;

    return $result;
  }

}
