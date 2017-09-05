<?php

class VideoFanwe
{
    private $m_config = null;

    function __construct($m_config)
    {
        $this->m_config = $m_config;
    }

    public function Create()
    {
        $result = $this->invoke(array(
            'act' => 'create',
        ));

        if (!$result['status']) {
            ajax_return(array(
                'status' => 0,
                'error' => $result['error'],
            ));
        }

        $data = $result['data'];
        return array(
            'channel_id' => $data['stream_id'],
            'upstream_address' => $data['push_rtmp'],
            'downstream_address' => array(
                'rtmp' => $data['play_rtmp'],
                'flv' => $data['play_flv'],
                'hls' => $data['play_hls'],
            ),
        );
    }

    public function Query($stream_id)
    {
        $result = $this->invoke(array(
            'act' => 'query',
            'stream_id' => $stream_id,
        ));
        if (!$result['status']) {
            return array(
                'status' => 0,
                'error' => $result['error'],
            );
        }

        $data = $result['data'];
        return array(
            'channel_id' => $stream_id,
            'status' => $data['stream_status'],
        );
    }

    public function Stop($stream_id)
    {
        $result = $this->invoke(array(
            'act' => 'stop',
            'stream_id' => $stream_id,
        ));
        if (!$result['status']) {
            return array(
                'status' => 0,
                'error' => $result['error'],
            );
        }

        return $result['data'];
    }

    public function GetRecord($stream_id)
    {
        $result = $this->invoke(array(
            'act' => 'get_record',
            'stream_id' => $stream_id,
        ));
        if (!$result['status']) {
            return array(
                'status' => 0,
                'error' => $result['error'],
            );
        }

        return array('totalCount' => count($result['data']), 'urls' => $result['data']);
    }

    private function invoke($params)
    {
        $url = "http://fwyun.fanwe.net/video";
        fanwe_require(APP_ROOT_PATH . 'system/saas/SAASAPIClient.php');
        $client = new \SAASAPIClient($this->m_config['fwyun_access_key'], $this->m_config['fwyun_secret_key']);
        return $client->invoke($url, $params);
    }
}