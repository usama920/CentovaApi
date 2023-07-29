<?php

function prx($value)
{
    echo "<pre>";
    print_r($value);
    die;
}

function format_size($size)
{
    if ($size >= 1048576) {
        $size = number_format($size / 1048576, 2) . ' GB';
    } elseif ($size >= 1024) {
        $size = number_format($size / 1024, 2) . ' MB';
    } elseif ($size > 1) {
        $size = $size . ' KB';
    } elseif ($size == 1) {
        $size = $size . ' KB';
    } else {
        $size = '0 KB';
    }
    return $size;
}

function format_time($seconds, $sessions)
{
    if ($seconds > 59) {
        return round(($seconds / $sessions) / 60, 1) . ' minute(s)';
    } else {
        return $seconds / $sessions . ' second(s)';
    }
}
