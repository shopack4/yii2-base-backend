<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\backend\rest;

use Yii;

class RestServerQuery extends \yii\db\ActiveQuery
{
  public function addFileUrl($fullFileUrlParamName, $fileRelationName = null)
  {
    if (empty($fileRelationName) == false) {
      $this->joinWith("{$fileRelationName} f{$fileRelationName}");
      $myTableName = "f{$fileRelationName}";
    } else {
      $fileRelationName = 'file';
      $myTableName = 'tbl_AAA_UploadFile';
    }

    $this
      ->leftJoin("(
        SELECT ROW_NUMBER() OVER(PARTITION BY uquFileID ORDER BY RAND()) AS row_num
             , uquFileID
             , uquGatewayID
          FROM tbl_AAA_UploadQueue
         WHERE uquStatus = 'S'
               ) AS q{$fileRelationName}",
        "q{$fileRelationName}.uquFileID = {$myTableName}.uflID AND q{$fileRelationName}.row_num = 1")
      ->leftJoin("tbl_AAA_Gateway g{$fileRelationName}", "g{$fileRelationName}.gtwID = q{$fileRelationName}.uquGatewayID")
      ->addSelect(new \yii\db\Expression(<<<SQL
CASE JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.type'))
  WHEN 's3' THEN
    CASE WHEN IFNULL(JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.EndpointIsVirtualHosted')), 0)
      THEN CONCAT(
        IF(
          LEFT(JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.endpoint')), 5) = 'http:',
          'http://',
          'https://'
        ),
        JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.bucket')),
        '.',
        IF(
          SUBSTRING(JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.endpoint')), 5, 1) = ':',
          SUBSTRING(JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.endpoint')), 8),
          SUBSTRING(JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.endpoint')), 9)
        ),
        '/',
        {$myTableName}.uflPath,
        '/',
        {$myTableName}.uflStoredFileName
      )
      ELSE CONCAT(
        JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.endpoint')),
        '/',
        JSON_UNQUOTE(JSON_EXTRACT(g{$fileRelationName}.gtwPluginParameters, '$.bucket')),
        '/',
        {$myTableName}.uflPath,
        '/',
        {$myTableName}.uflStoredFileName
      )
    END
  WHEN 'nfs' THEN
    'NFS PATH'
  ELSE NULL
END AS {$fullFileUrlParamName}
SQL
      ))
    ;

    return $this;
  }

}
