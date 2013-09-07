<?php

class RegexForNumRange {

    static public function range($min_, $max_) {
        return RegexForNumRange::generateNumericRangeRegex($min_, $max_);
    }

    static private function generateNumericRangeRegex($min_, $max_, $capturing = False) {
        if ($min_ > $max_) {
            throw new Exception('ValueError(' . $min_ . '>' . $max_ . ')');
        }

        return RegexForNumRange::generateWordBoundedRegex($min_, $max_);
    }

    static private function generateWordBoundedRegex($min_, $max_, $capturing = False) {
        $format = ($capturing) ? '(%s)' : '(?:%s)';
        $values = RegexForNumRange::GenerateRegex($min_, $max_);
        $str = sprintf($format, $values);
        return $str;
    }

    static private function GenerateRegex($min_, $max_) {
        $nr_dig_min = strlen($min_);
        $nr_dig_max = strlen($max_);

        if ($nr_dig_min != $nr_dig_max) {
            $middle_parts = [];

            $range = range($nr_dig_min, $nr_dig_max - 1);

            foreach ($range as $value) {
                if ($value - 1) {
                    $middle_parts[] = '[1-9]' . str_repeat('[0-9]', ($value - 1));
                }
            }

            $middle = implode('|', $middle_parts);
            $starting = RegexForNumRange::generateToBound($min_, 'upper');
            $ending = RegexForNumRange::generateForSameLenNr('1' . str_repeat('0', (strlen($max_) - 1)), $max_);

            if (!empty($middle)) {
                return implode('|', [$starting, $middle, $ending]);
            } else {
                return implode('|', [$starting, $ending]);
            }
        } else {
            return RegexForNumRange::generateForSameLenNr($min_, $max_);
        }
    }

    static private function getFirstDigitAndRest($num) {
        if (strlen($num) > 1) {
            return [substr($num, 0, 1), substr($num, 1)];
        }

        if (strlen($num) == 1) {
            return [$num, ''];
        }

        return ['', ''];
    }

    static private function generateHead($first, $zeros, $rest, $bound) {
        $parts = [];

        $string = RegexForNumRange::generateToBound($rest, $bound);
        $bounds = explode('|', $string);

        foreach ($bounds as $reg) {
            $parts[] = $first . $zeros . $reg;
        }

        return implode('|', $parts);
    }

    static private function stripLeftRepeatedDigit($num, $digit) {

        $str = str_split((string) $num);

        foreach ($str as $key => $char) {
            if ($char != $digit) {
                return [str_repeat($digit, $key), substr($num, $key)];
            }
        }

        return [$num, ''];
    }

    static private function generateToBound($num, $bound) {
        if (!in_array($bound, ['upper', 'lower'])) {
            throw new Exception('Bound ' . $bound . ' not in [\'upper\', \'lower\']');
        }

        if ($num === '') {
            return '';
        }

        $no_range_exit = ($bound == 'lower') ? '0' : '9';

        if (strlen($num) == 1 && intval($num) == intval($no_range_exit)) {
            return $no_range_exit;
        }

        if (strlen($num) == 1 && 0 <= intval($num) && intval($num) < 10) {
            if ($bound == "lower") {
                return '[0-' . $num . ']';
            } else {
                return '[' . $num . '-9]';
            }
        }

        list($first_digit, $rest1) = RegexForNumRange::getFirstDigitAndRest($num);
        list($repeated, $rest) = RegexForNumRange::stripLeftRepeatedDigit($rest1, $no_range_exit);
        $head = RegexForNumRange::generateHead($first_digit, $repeated, $rest, $bound);

        $tail = '';
        if ($bound == "lower") {
            if (intval($first_digit) > 1) {
                $tail = '[0-' . (intval($first_digit) - 1) . ']';
                $tail .= str_repeat('[0-9]', (strlen($num) - 1));
            } else if (intval($first_digit) == 1) {
                $tail = '0' . str_repeat('[0-9]', (strlen($num) - 1));
            }
        } else {
            if (intval($first_digit) < 8) {
                $tail = '[' . (intval($first_digit) + 1) . '-9]';
                $tail .= str_repeat('[0-9]', (strlen($num) - 1));
            } else {
                $tail = '9' . str_repeat('[0-9]', (strlen($num) - 1));
            }
        }

        if ($tail == '') {
            return $head;
        }

        return $head . '|' . $tail;
    }

    static private function extractCommon($min_, $max_) {
        list($fdMin, $restMin) = RegexForNumRange::getFirstDigitAndRest($min_);
        list($fdMax, $restMax) = RegexForNumRange::getFirstDigitAndRest($max_);

        $common = '';
        while ($fdMin == $fdMax && $fdMin != '') {
            $common .= $fdMin;

            list($fdMin, $restMin) = RegexForNumRange::getFirstDigitAndRest($restMin);
            list($fdMax, $restMax) = RegexForNumRange::getFirstDigitAndRest($restMax);
        }

        return [$common, $fdMin, $restMin, $fdMax, $restMax];
    }

    static private function generateForSameLenNr($min_, $max_) {
        if (strlen($min_) != strlen($max_)) {
            throw new Exception(strlen($min_) . ' != ' . strlen($max_));
        }

        list($common, $fd_min, $rest_min, $fd_max, $rest_max) = RegexForNumRange::extractCommon($min_, $max_);

        if ($rest_min === '') {
            $starting = $common . $fd_min;
        } else {
            $buf = [];
            $str = RegexForNumRange::generateToBound($rest_min, "upper");
            $upper = explode('|', $str);

            foreach ($upper as $value) {
                $buf[] = $common . $fd_min . $value;
            }

            $starting = implode('|', $buf);
        }

        if ($rest_max === '') {
            $ending = $common . $fd_max;
        } else {
            $buf = [];
            $str = RegexForNumRange::generateToBound($rest_max, "lower");
            $lower = explode('|', $str);

            foreach ($lower as $value) {
                $buf[] = $common . $fd_max . $value;
            }

            $ending = implode('|', $buf);
        }

        if ($fd_min !== '' && $fd_max !== '' && intval($fd_min) + 1 > intval($fd_max) - 1) {
            if ($starting === '' || $ending === '') {
                throw new Exception($starting . ' && ' . $ending);
            }

            return $starting . '|' . $ending;
        }

        if ($fd_min !== '' && $fd_max !== '' && intval($fd_min) + 1 == intval($fd_max) - 1) {
            $middle = $common . (intval($fd_min) + 1);
        } else if ($fd_min !== '' && $fd_max !== '') {
            $middle = $common . sprintf('[%d-%d]', (intval($fd_min) + 1), (intval($fd_max) - 1));
        } else {
            $middle = $common;
        }

        $middle .= str_repeat('[0-9]', strlen($rest_min));
        return implode('|', [$starting, $middle, $ending]);
    }

}
