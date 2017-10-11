<?php

namespace whotrades\rds\components;

class Time
{
    /**
     * Returns a nicely formatted date string for given Datetime string.
     *
     * @param string $dateString Datetime string
     * @param int $format Format of returned date
     * @return string Formatted date string
     */
    public static function nice($dateString = null, $format = 'D, M jS Y, H:i')
    {
        $date = ($dateString == null) ? time() : strtotime($dateString);

        return date($format, $date);
    }

    /**
     * Returns a formatted descriptive date string for given datetime string.
     *
     * If the given date is today, the returned string could be "Today, 6:54 pm".
     * If the given date was yesterday, the returned string could be "Yesterday, 6:54 pm".
     * If $dateString's year is the current year, the returned string does not
     * include mention of the year.
     *
     * @param string $dateString Datetime string or Unix timestamp
     * @return string Described, relative date string
     */
    public static function niceShort($dateString = null)
    {
        $date = ($dateString == null) ? time() : strtotime($dateString);

        $y = (self::isThisYear($date)) ? '' : ' Y';

        if (self::isToday($date)) {
            $ret = sprintf('Today, %s', date("g:i a", $date));
        } elseif (self::wasYesterday($date)) {
            $ret = sprintf('Yesterday, %s', date("g:i a", $date));
        } else {
            $ret = date("M jS{$y}, H:i", $date);
        }

        return $ret;
    }

    /**
     * Returns true if given date is today.
     *
     * @param string $date Unix timestamp
     * @return boolean True if date is today
     */
    public static function isToday($date)
    {
        return date('Y-m-d', $date) == date('Y-m-d', time());
    }

    /**
     * Returns true if given date was yesterday
     *
     * @param string $date Unix timestamp
     * @return boolean True if date was yesterday
     */
    public static function wasYesterday($date)
    {
        return date('Y-m-d', $date) == date('Y-m-d', strtotime('yesterday'));
    }

    /**
     * Returns true if given date is in this year
     *
     * @param string $date Unix timestamp
     * @return boolean True if date is in this year
     */
    public static function isThisYear($date)
    {
        return date('Y', $date) == date('Y', time());
    }

    /**
     * Returns true if given date is in this week
     *
     * @param string $date Unix timestamp
     * @return boolean True if date is in this week
     */
    public static function isThisWeek($date)
    {
        return date('W Y', $date) == date('W Y', time());
    }

    /**
     * Returns true if given date is in this month
     *
     * @param string $date Unix timestamp
     * @return boolean True if date is in this month
     */
    public static function isThisMonth($date)
    {
        return date('m Y', $date) == date('m Y', time());
    }
}
