<?php
/**
 *
 */
class videoModel extends Model
{
    public function getLiveVideoByUserId($user_id, $field = '')
    {
        return $this->field($field)->selectOne(array('user_id' => intval($user_id), 'live_in' => 1));
    }
}
