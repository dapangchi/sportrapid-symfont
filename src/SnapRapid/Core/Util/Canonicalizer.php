<?php

namespace SnapRapid\Core\Util;

class Canonicalizer
{
    public function canonicalize($string)
    {
        return mb_convert_case($string, MB_CASE_LOWER, mb_detect_encoding($string));
    }
}
