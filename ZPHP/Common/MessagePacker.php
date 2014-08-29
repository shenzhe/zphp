<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 * 
 */

namespace ZPHP\Common;

class MessagePacker
{
    public $data;
    private $offset;
    private $dataLen;

    public function __construct($data = '')
    {
        $this->data = $data;
        $this->offset = 0;
        $this->dataLen = strlen($data);
    }

    public function resetForUnPack($data)
    {
        $this->data = $data;
        $this->offset = 0;
        $this->dataLen = strlen($data);
    }

    public function resetForPack()
    {
        $this->data = '';
        $this->offset = 0;
    }

    public function resetOffset($len=0)
    {
        $this->offset = $len;
    }

    public function writeByte($d)
    {
        $this->data .= pack("C1", $d);
    }

    public function writeString($s, $len=null)
    {
        //$s = rtrim($s, "\0") . "\0";
        if(null === $len) {
            $len = strlen($s);
        }
        $this->writeInt($len);
        $this->data .= pack("a*", $s);
    }

    //写二进制数据
    public function writeBinary($b, $len=null)
    {
        if(null === $len) {
            $len = strlen($b);
        }
        $this->writeInt($len);
        $this->data .= $b;
        //$this->data .= pack('H*', $b);
    }

    public function writeBool($d)
    {
        $this->data .= pack("C1", $d);
    }

    public function writeInt($i)
    {
        //$this->data .= pack("N1", $i);
        $this->data .= pack("V1", $i);
    }

    public function writeInt16($i)
    {
        //$this->data .= pack("n1", $i);
        $this->data .= pack("v1", $i);
    }

    public function readByte()
    {
        $ret = unpack("C1ele", substr($this->data, $this->offset, 1));
        $this->offset += 1;
        return $ret['ele'];
    }

    public function readInt()
    {
        //$ret = unpack("N1ele", substr($this->data, $this->offset, 4));
        $ret = unpack("V1ele", substr($this->data, $this->offset, 4));
        $this->offset += 4;

        return $ret['ele'];
    }

    public function readInt16()
    {
        //$ret = unpack("n1ele", substr($this->data, $this->offset, 2));
        $ret = unpack("v1ele", substr($this->data, $this->offset, 2));
        $this->offset += 2;

        return $ret['ele'];
    }

    public function readString()
    {
        $len = $this->readInt();
        $ret = unpack("a*ele", substr($this->data, $this->offset, $len));
        $this->offset += $len;

        return $ret['ele'];
    }

    //读二进制
    public function readBinary()
    {
        $len = $this->readInt();
        $ret = substr($this->data, $this->offset, $len);
        $this->offset += $len;
        return $ret;
    }

    public function readBool()
    {
        $ret = unpack("C1ele", substr($this->data, $this->offset, 1));
        $this->offset += 1;

        return $ret['ele'];
    }

    public function output()
    {
        echo $this->data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getBuffer()
    {
        $len = strlen($this->data);
        if($this->offset < $len) {
            return substr($this->data, $this->offset);
        }

        return null;
    }

    public function isEnd() 
    {
        return $this->offset >= $this->dataLen;
    }
}