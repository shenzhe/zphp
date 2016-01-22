<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Rank\Adapter;
use ZPHP\Manager,
    ZPHP\Rank\IRank;

class Redis implements IRank
{
    private $redis;

    public function __construct($config)
    {
        if (empty($this->redis)) {
            $this->redis = Manager\Redis::getInstance($config);
        }
    }

    public function addRank($rankType, $key, $score, $length = 0)
    {
        $this->redis->zAdd($rankType, $score, $key);
        if ($length > 0) { //限个数
            $all = $this->redis->zCard($rankType);
            if ($all > $length) {
                $keys = $this->redis->zRange($rankType, 0, $all - $length);
                foreach ($keys as $key) {
                    $this->redis->zDelete($rankType, $key);
                }
            }
        }

        return true;
    }

    public function getRank($rankType, $start = 0, $limit = 100, $score = true, $desc = 0)
    {
        if ($desc) {
            return $this->redis->zRevRange($rankType, $start, $start + $limit, $score);
        }
        return $this->redis->zRange($rankType, $start, $start + $limit, $score);
    }

    public function getRankByScore($rankType, $start, $end, $scores = true, $offset = 0, $count = 0)
    {
        if (!empty($offset) && !empty($count)) {
            return $this->redis->zRangeByScore($rankType, $start, $end, array('withscores' => $scores, 'limit' => array($offset, $count)));
        }
        return $this->redis->zRangeByScore($rankType, $start, $end, array('withscores' => $scores));
    }

    public function getRankBetweenCount($rankType, $start, $end)
    {
        return $this->redis->zCount($rankType, $start, $end);
    }

    public function getRankCount($rankType)
    {
        return $this->redis->zCard($rankType);
    }

    public function getRankByKey($rankType, $key, $desc = 0)
    {
        if ($desc) {
            $rank = $this->redis->zRevRank($rankType, $key);
        } else {
            $rank = $this->redis->zRank($rankType, $key);
        }

        if(false === $rank) {
            return 0;
        }
        return ++$rank;
    }

    public function updateRankByKey($rankType, $key, $score)
    {
        return $this->redis->zIncrBy($rankType, $score, $key);
    }

    public function zDelete($rankType, $key)
    {
        return $this->redis->zDelete($rankType, $key);
    }

    public function deleteRank($rankType)
    {
        return $this->redis->delete($rankType);
    }

    public function getScoreByKey($rankType, $key)
    {
        return $this->redis->zScore($rankType, $key);
    }
}
