<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Queue;

use ZPHP\Manager;

class Beanstalk
{
    private $beanstalk;

    public function __construct($config)
    {
        if (empty($this->beanstalk)) {
            $this->beanstalk = Manager\BeanStakl::getInstance($config);
        }
    }

    public function addQueue($key, $data)
    {
        return $this->beanstalk->put($key, $data);
    }

    public function getQueue($key)
    {
        $job = $this->beanstalk->reserve($key);
        $this->beanstalk->delete($job['id'], $key);
        return $job;
    }
}