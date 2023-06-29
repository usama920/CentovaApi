<?php

function format_size($size)
{
    if ($size >= 1073741824) {
        $size = number_format($size / 1073741824, 2) . ' GB';
    } elseif ($size >= 1048576) {
        $size = number_format($size / 1048576, 2) . ' MB';
    } elseif ($size >= 1024) {
        $size = number_format($size / 1024, 2) . ' KB';
    } elseif ($size > 1) {
        $size = $size . ' Bytes';
    } elseif ($size == 1) {
        $size = $size . ' Byte';
    } else {
        $size = '0 Bytes';
    }
    return $size;
}
