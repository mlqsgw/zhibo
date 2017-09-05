<?php

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/Config.php';

use OSS\OssClient;
use OSS\Core\OssException;

class Common
{
    const endpoint = \Config::OSS_ENDPOINT;
    const accessKeyId = \Config::OSS_ACCESS_ID;
    const accessKeySecret = \Config::OSS_ACCESS_KEY;
    const bucket = \Config::OSS_BUCKET;

    /**
     * 根据Config配置，得到一个OssClient实例
     *
     * @return OssClient 一个OssClient实例
     */
    public static function getOssClient()
    {
        try {
            $ossClient = new OssClient(self::accessKeyId, self::accessKeySecret, self::endpoint, false);
        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }

    public static function getBucketName()
    {
        return self::bucket;
    }

    /**
     * 工具方法，创建一个存储空间，如果发生异常直接exit
     */
    public static function createBucket()
    {
        $ossClient = self::getOssClient();
        if (is_null($ossClient)) exit(1);
        $bucket = self::getBucketName();
        $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ;
        try {
            $ossClient->createBucket($bucket, $acl);
        } catch (OssException $e) {

            $message = $e->getMessage();
            if (\OSS\Core\OssUtil::startsWith($message, 'http status: 403')) {
                echo "Please Check your AccessKeyId and AccessKeySecret" . "\n";
                exit(0);
            } elseif (strpos($message, "BucketAlreadyExists") !== false) {
                echo "Bucket already exists. Please check whether the bucket belongs to you, or it was visited with correct endpoint. " . "\n";
                exit(0);
            }
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }

    public static function println($message)
    {
        if (!empty($message)) {
            echo strval($message) . "\n";
        }
    }
}

$bucket = \Common::getBucketName();
$ossClient = \Common::getOssClient();
$dir_name = "public/attachment/".date("Ym")."/".date("d")."/".date("H").'/';
$name=time().rand(0,10000);
$file=current($_FILES);
$ext_arr=explode('.',is_array($file['name'])?$file['name'][0]:$file['name']);
$ext=$ext_arr[count($ext_arr)-1];
$object=$dir_name.$name.'.'.$ext;
$ossClient->uploadFile($bucket, $object, is_array($file['tmp_name'])?$file['tmp_name'][0]:$file['tmp_name']);
if($_GET['upload_type']==0)
{
    $res=array('error' => 0, 'url' => $object,'host'=>\Config::OSS_HOST);
}else{
    $res=array('error' => 0, 'url'=>$object,'thumb_url'=>$object,'host'=>\Config::OSS_HOST);
}
header("Content-Type:text/html; charset=utf-8");
echo(json_encode($res));