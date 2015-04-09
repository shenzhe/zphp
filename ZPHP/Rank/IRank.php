<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */

namespace ZPHP\Rank;

interface IRank
{
    /**
     * 添加一个排行
     * @param $rankType   排行类别
     * @param $key        key
     * @param $score      分数
     * @param int $length 最大排行人数， 0=不限制
     * @return bool
     */
    public function addRank($rankType, $key, $score, $length = 0);

    /**
     * 获取一段排行
     * @param $rankType     排行类别
     * @param int $start    起始名次
     * @param int $limit    结束名次
     * @param bool $score   是否返回分数
     * @param int $desc     是否返回倒
     * @return mixed
     */
    public function getRank($rankType, $start = 0, $limit = 100, $score = true, $desc=0);

    /**
     * 返回某一区间分数排行
     * @param $rankType     排行类别
     * @param $start        起始分数
     * @param $end          结束分数
     * @param $scores       是否显示分数
     * @param $offset       偏移
     * @param $count        总数
     * @return mixed
     */
    public function getRankByScore($rankType, $start, $end, $scores = true, $offset = 0, $count = 0);

    /**
     * 返回某一区间分数 排行的人数
     * @param $rankType     排行类别
     * @param $start        起始分数
     * @param $end          结束分数
     * @return mixed
     */
    public function getRankBetweenCount($rankType, $start, $end);

    /**
     *
     * 返回排行榜总人数
     * @param type $rankType    排行类别
     */
    public function getRankCount($rankType);

    /**
     *
     * 获取指定key的排行
     * @param type $rankType
     * @param type $key
     */
    public function getRankByKey($rankType, $key, $desc);

    /**
     *
     * 更新指定key的排行
     * @param type $rankType
     * @param type $key
     * @param type $score
     * @return type
     */
    public function updateRankByKey($rankType, $key, $score);

    /**
     * 删除指定key的排行
     * @param $rankType
     * @param $key
     * @return mixed
     */
    public function zDelete($rankType, $key);

    /**
     * 删除某类排行
     * @param $rankType
     * @return mixed
     */
    public function deleteRank($rankType);

    /**
     * 获取指定key的分数
     * @param $rankType
     * @param $key
     * @return mixed
     */
    public function getScoreByKey($rankType, $key);
}