<?php

namespace hsmfdata;

class LogReported
{
    protected $filePath;
    protected $uploadUrl;
    protected $linesPer;
    protected $timeThreshold;

    public function __construct($filePath, $uploadUrl, $linesPer, $timeThreshold)
    {
        $this->filePath = isset($filePath) ? $filePath : './';
        $this->uploadUrl = isset($uploadUrl) ? $uploadUrl : '';
        $this->linesPer = isset($linesPer) ? $linesPer : 100;
        $this->timeThreshold = isset($timeThreshold) ? $timeThreshold : 300;
    }

    public function handle()
    {
        // 获取当前日期（格式：YYYY-MM-DD）
        $currentDate = date('Y_m_d');

        $reportedFile = $this->filePath . 'reported_' . $currentDate . '.txt'; //上报文件
        $requestLogFile = $this->filePath . 'request_' . $currentDate . '.log'; // 上报日志文件
        $lockFile = $this->filePath . 'lock_' . $currentDate . '.lock'; // 锁文件
        $cursorFile = $this->filePath . 'cursor_' . $currentDate . '.txt'; // 游标文件

        // 检查上报文件，不存在结束
        if (!file_exists($reportedFile)) {
            echo '上报文件不存在';
            exit;
        }

        // 检查游标文件，不存在创建
        if (!file_exists($cursorFile)) {
            fclose(fopen($cursorFile, 'w'));
        }

        // 检查锁文件，不存在创建
        if (!file_exists($lockFile)) {
            fclose(fopen($lockFile, 'w'));
        }

        //检查上报日志文件，不存在创建
        if (!file_exists($requestLogFile)) {
            fclose(fopen($requestLogFile, 'w'));
        }

        $lock = $this->_acquireLock($lockFile);

        while ($currentDate == date('Y_m_d')) {

            $cursorData = json_decode(file_get_contents($cursorFile), true);
            $cursor = isset($cursorData['position']) ? (int)$cursorData['position'] : 0;
            $fileSize = isset($cursorData['size']) ? (int)$cursorData['size'] : 0;

            // 在获取文件大小之前清除文件状态缓存
            clearstatcache();
            // 获取当前文件大小
            $currentFileSize = filesize($reportedFile);

            // 文件发生变化
            if (filesize($reportedFile) != $fileSize) {

                //打开上报文件
                $handle = fopen($reportedFile, 'r');

                $startTime = time();

                while (!feof($handle)) {
                    $chunk = [];

                    // 移动文件指针到对应位置
                    fseek($handle, $cursor);

                    for ($i = 0; $i < $this->linesPer; $i++) {
                        $line = fgets($handle);
                        if ($line) {
                            $chunk[] = (string)$line;
                        }
                    }

                    if (count($chunk) == $this->linesPer || (time() - $startTime) >= $this->timeThreshold) {
                        var_dump('满足条件上报');

                        //curl上报
                        $postData = ['data' => $chunk];
                        $postData = http_build_query($postData);
                        $result = $this->_curlPostRequest($postData, $this->uploadUrl);

                        if ($result['success']) {

                            //更新游标
                            $cursor = ftell($handle);

                            $cursorData = [
                                'size' => $currentFileSize,
                                'position' => $cursor,
                            ];
                            file_put_contents($cursorFile, json_encode($cursorData));
                            file_put_contents($requestLogFile, "上报成功，httpCode：{$result['http_code']}\n" . "result：" . print_r($result, true) . "\n", FILE_APPEND | LOCK_EX);
                            $startTime = time(); // 重置计时器
                            //阻塞
                            sleep(1);

                        } else {
                            file_put_contents($requestLogFile, "上报失败，httpCode：{$result['http_code']}\n" . "result：" . print_r($result, true) . "\n", FILE_APPEND | LOCK_EX);
                            //重置指针位置
                            fseek($handle, $cursor);
                            //阻塞
                            sleep(5);
                        }


                    } else {
                        var_dump('未满足条件不上报');
                        //重置指针位置
                        fseek($handle, $cursor);
                        //阻塞
                        sleep(5);
                    }

                }

                var_dump('文件无可上报内容');
            }

            sleep(10);
        }

        $this->_releaseLock($lock);

    }

    /**
     * @param $lockFile
     * @return false|resource
     */
    private function _acquireLock($lockFile)
    {
        $lock = fopen($lockFile, 'w');
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            echo "无法获取锁。另一个进程正在运行.\n";
            exit;
        }
        return $lock;
    }

    /**
     * @param $lock
     */
    private function _releaseLock($lock)
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }


    /**
     * @param $postData
     * @param $uploadUrl
     * @return array
     */
    private function _curlPostRequest($postData, $uploadUrl)
    {
        $ch = curl_init($uploadUrl);

        // 设置curl选项
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // 执行curl请求
        $response = curl_exec($ch);
        // 获取http状态码
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            // 请求失败
            $errorCode = curl_errno($ch);
            $errorMessage = curl_error($ch);
            curl_close($ch);

            return array(
                'success' => false,
                'http_code' => $httpCode,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            );
        } else {
            curl_close($ch);

            // 判断http状态码
            if ($httpCode >= 200 && $httpCode < 300) {
                // http状态码正常，继续判断返回结构
                $responseData = json_decode($response, true);

                if (isset($responseData['err_no']) && $responseData['err_no'] === 0) {
                    // api返回结果中的err_no为0表示成功
                    return array(
                        'success' => true,
                        'http_code' => $httpCode,
                        'response' => $responseData,
                    );
                } else {
                    // api返回结果中的err_no不为0表示失败
                    return array(
                        'success' => false,
                        'http_code' => $httpCode,
                        'error_code' => isset($responseData['err_no']) ? $responseData['err_no'] : null,
                        'error_message' => isset($responseData['err_msg']) ? $responseData['err_msg'] : '未知错误',
                    );
                }
            } else {
                // http状态码不正常
                return array(
                    'success' => false,
                    'http_code' => $httpCode,
                    'error_message' => 'http请求失败，状态码：' . $httpCode,
                );
            }
        }
    }
}