<?php

namespace Tanjiu\Log;

use Monolog\Formatter\LineFormatter;


class LogFormatter extends LineFormatter
{

    public function __construct(?string $format = null, ?string $dateFormat = null, bool $allowInlineLineBreaks = false, bool $ignoreEmptyContextAndExtra = false)
    {
        //带毫秒的时间戳
        $mtimestamp = sprintf("%.3f", microtime(true));
        $milliseconds = substr($mtimestamp, -3, 3);
        $datetime = date("Y-m-d H:i:s"). '.' . $milliseconds;

        $dateFormat = 'Y-m-d H:i:s u';
        $ignoreEmptyContextAndExtra = true;

        $traceId = $this->getSkyWalkingTraceId();

        //[发生时间：y-m-d h:i:s sss][服务名][告警级别:debug|error|fatal|info][traceid] 主体内容 %message% %context% %extra%\n
        $format = sprintf("%s%s%s%s %s",
            '['.$datetime.']',
            '['.env('APP_NAME','VHOME-NCENTER').']',
            '[%level_name%]',
            '['.$traceId.']',"%message% %context% %extra%\n"
        );

        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * 获取SKY walking traceid
     * @return string
     */
    private function getSkyWalkingTraceId()
    {
        if(function_exists('skywalking_trace_id')){
            return skywalking_trace_id();
        }

        return '0';
    }
}