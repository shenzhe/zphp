<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Serialize\Adapter;
class Igbinary
{
    public function serialize($data)
    {
        return \igbinary_serialize($data);
    }

    public function unserialize($data)
    {
        return \igbinary_unserialize($data);
    }
}
