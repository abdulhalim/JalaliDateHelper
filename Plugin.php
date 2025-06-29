<?php
/**
 * پلاگین افزودن تابع کمکی تاریخ شمسی برای قالب Typecho
 * @package JalaliDateHelper
 * @author پوردریایی
 * @version 1.0
 * @description افزودن تابع jalali_date به قالب برای تبدیل و نمایش تاریخ شمسی با پشتیبانی نام ماه فارسی
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class JalaliDateHelper_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->beforeRender = array('JalaliDateHelper_Plugin', 'addHelper');
        return 'تابع کمکی jalali_date اضافه شد.';
    }
    public static function deactivate() {}
    public static function config(Typecho_Widget_Helper_Form $form) {}
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    public static function addHelper($archive)
    {
        if (!function_exists('jalali_date')) {
            function jalali_date($format, $timestamp = null)
            {
                if (is_null($timestamp)) $timestamp = time();
                $g_y = date('Y', $timestamp);
                $g_m = date('m', $timestamp);
                $g_d = date('d', $timestamp);

                // تبدیل میلادی به شمسی
                $g_days_in_month = array(31,28,31,30,31,30,31,31,30,31,30,31);
                $j_days_in_month = array(31,31,31,31,31,31,30,30,30,30,30,29);

                $gy = (int)$g_y-1600;
                $gm = (int)$g_m-1;
                $gd = (int)$g_d-1;

                $g_day_no = 365*$gy+intval(($gy+3)/4)-intval(($gy+99)/100)+intval(($gy+399)/400);
                for ($i=0; $i<$gm; ++$i)
                    $g_day_no += $g_days_in_month[$i];
                if ($gm>1 && (($gy%4==0 && $gy%100!=0)||($gy%400==0)))
                    $g_day_no++;
                $g_day_no += $gd;

                $j_day_no = $g_day_no-79;
                $j_np = intval($j_day_no/12053);
                $j_day_no = $j_day_no%12053;

                $jy = 979+33*$j_np+4*intval($j_day_no/1461);
                $j_day_no %= 1461;

                if ($j_day_no >= 366) {
                    $jy += intval(($j_day_no-1)/365);
                    $j_day_no = ($j_day_no-1)%365;
                }

                for ($i=0; $i<11 && $j_day_no>=$j_days_in_month[$i]; ++$i)
                    $j_day_no -= $j_days_in_month[$i];
                $jm = $i+1;
                $jd = $j_day_no+1;

                // آرایه ماه‌های فارسی
                $months = array(
                    1 => 'فروردین',
                    2 => 'اردیبهشت',
                    3 => 'خرداد',
                    4 => 'تیر',
                    5 => 'مرداد',
                    6 => 'شهریور',
                    7 => 'مهر',
                    8 => 'آبان',
                    9 => 'آذر',
                    10 => 'دی',
                    11 => 'بهمن',
                    12 => 'اسفند'
                );

                $out = $format;
                $out = str_replace('F', $months[(int)$jm], $out);
                $out = str_replace('Y', $jy, $out);
                $out = str_replace('m', str_pad($jm, 2, '0', STR_PAD_LEFT), $out);
                $out = str_replace('d', str_pad($jd, 2, '0', STR_PAD_LEFT), $out);
                $out = str_replace('H', date('H', $timestamp), $out);
                $out = str_replace('i', date('i', $timestamp), $out);
                $out = str_replace('s', date('s', $timestamp), $out);

                return $out;
            }
        }
    }
}
