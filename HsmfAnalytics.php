<?php

class HsmfAnalytics
{
    private $_consumer;

    public function __construct($consumer)
    {
        $this->_consumer = $consumer;
    }

    /**
     * 上报数据
     * @param string $identifier 用户的唯一标识
     * @param string $prefix uuid前缀
     * @param string $event_name 事件名称。
     * @param array $properties 事件的属性。
     * @return bool|void
     */
    public function track($identifier, $prefix, $event_name, $properties = array())
    {
        try {
            $data = array(
                'type' => 'track',
                'properties' => $properties,
                'identifier' => $identifier,
                'event' => $event_name,
                'uuid' => uniqid($prefix),
                'maritime' => microtime(),
            );
            // 检查 identifier
            if (!isset($data['identifier']) or strlen($data['identifier']) == 0) {
                throw new \Exception("property [identifier] must not be empty");
            }
            if (strlen($data['identifier']) > 255) {
                throw new \Exception("the max length of [identifier] is 255");
            }
            $data['identifier'] = strval($data['identifier']);

            // 检查 properties
            if (isset($data['properties']) && is_array($data['properties'])) {
                if (count($data['properties']) == 0) {
                    $data['properties'] = new \ArrayObject();
                }
            } else {
                throw new \Exception("property must be an array.");
            }
            return $this->_consumer->send(json_encode($data));

        } catch (\Exception $e) {
            echo '<br>' . $e . '<br>';
        }
    }

    public function close()
    {
        return $this->_consumer->close();
    }
}


class HsmfFileConsumer
{
    private $file_handler;

    public function __construct($filePatch)
    {
        if (!file_exists($filePatch)) {
            mkdir($filePatch, 0777, true);
        }
        $date = date('Y_m_d');
        $filename = $filePatch . '/' . 'reported_' . $date . '.txt';
        $this->file_handler = fopen($filename, 'a+');
    }

    /**
     * @param $msg
     * @return bool
     */
    public function send($msg)
    {
        if ($this->file_handler === null) {
            return false;
        }
        return !(fwrite($this->file_handler, $msg . "\n") === false);
    }

    public function close()
    {
        if ($this->file_handler === null) {
            return false;
        }
        return fclose($this->file_handler);
    }
}