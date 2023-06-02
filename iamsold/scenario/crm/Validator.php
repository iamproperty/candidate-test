<?php

namespace Buan\scenario\crm;
class Validator
{
    /**
     * @param string $str
     * @param bool $fullCheck
     *
     * @return bool
     */
    public static function isValidIdFormat(string $str, bool $fullCheck = false): bool
    {
        if (strlen($str) !== 32) {
            return false;
        }

        return !$fullCheck || preg_match('/[a-f0-9]{32}/', $str);
    }
}
