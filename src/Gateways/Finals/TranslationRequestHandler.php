<?php
/**
 * +----------------------------------------------------------------------
 * | laravel-translate [ File Description ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015~2019 http://www.wmt.ltd All rights reserved.
 * +----------------------------------------------------------------------
 * | 版权所有：贵州鸿宇叁柒柒科技有限公司
 * +----------------------------------------------------------------------
 * | Author: shadow <admin@hongyuvip.com>  QQ: 1527200768
 * +----------------------------------------------------------------------
 * | Version: v1.0.0  Date:2019-05-23 Time:14:48
 * +----------------------------------------------------------------------
 */

namespace Hongyukeji\LaravelTranslate\Gateways\Finals;


use Hongyukeji\LaravelTranslate\Gateways\Interfaces\RequestHandlerInterface;
use Hongyukeji\LaravelTranslate\Gateways\Interfaces\TranslationConfigInterface;

final class TranslationRequestHandler implements RequestHandlerInterface
{
    const SEPARATOR = ',';

    private $api_endpoint;
    private $appId;
    private $key;

    private $translation;

    public function __construct(string $api_endpoint = null, string $appId = null, string $key = null, TranslationConfigInterface $translation)
    {
        $this->api_endpoint = $api_endpoint;
        $this->appId = $appId;
        $this->key = $key;
        $this->translation = $translation;
    }

    public function getMethod(): string
    {
        return RequestHandlerInterface::METHOD_POST;
    }

    public function getPath(): string
    {
        return $this->api_endpoint;
    }

    public function getBody(): array
    {
        $salt = $this->create_uuid();
        $curtime = strtotime("now");
        $q = $this->translation->getText();
        $sign = $this->calculate_sign($this->appId, $this->key, $q, $salt, $curtime);
        return [
            'form_params' => array_filter(
                [
                    'q'        => $this->translation->getText(),
                    'from'     => strtolower($this->translation->getSourceLang()),
                    'to'       => strtolower($this->translation->getTargetLang()),
                    'appKey'   => $this->appId,
                    'salt'     => $salt,
                    'sign'     => $sign,
                    'signType' => 'v3',
                    'curtime'  => $curtime,
                    'vocabId' => '您的用户词表ID'

                ]
            )
        ];
    }

    public function create_uuid()
    {
        $str = md5(uniqid(mt_rand(), true));
        $uuid = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $uuid;
    }

    public function calculate_sign($appKey, $appSecret, $q, $salt, $curtime)
    {
        $strSrc = $appKey . $this->get_input($q) . $salt . $curtime . $appSecret;
        return hash("sha256", $strSrc);
    }

    public function get_input($q)
    {
        if (empty($q)) {
            return null;
        }
        $len = mb_strlen($q, 'utf-8');
        return $len <= 20 ? $q : (mb_substr($q, 0, 10) . $len . mb_substr($q, $len - 10, $len));
    }

    function truncate($q)
    {
        $len = strlen($q);
        return $len <= 20 ? $q : (substr($q, 0, 10) . $len . substr($q, $len - 10, $len));
    }
}