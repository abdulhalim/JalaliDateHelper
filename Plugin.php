<?php
/**
 * پلاگین افزودن تابع کمکی تاریخ شمسی برای پوسته Typecho با پشتیبانی از منطقه زمانی
 * @package JalaliDateHelper
 * @author پوردریایی
 * @link https://pourdaryaei.ir/
 * @version 1.3
 * @description افزودن تابع jalali_date به پوسته برای تبدیل و نمایش تاریخ شمسی با پشتیبانی از منطقه زمانی
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class JalaliDateHelper_Plugin implements Typecho_Plugin_Interface
{
    // تنظیمات پیش‌فرض
    private static $defaultSettings = [
        'timezone_enabled' => '1',
        'timezone' => 'Asia/Tehran',
        'show_seconds' => '0',
        'date_format' => 'Y/m-d H:i'
    ];

    /**
     * فعال کردن پلاگین
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->beforeRender = array('JalaliDateHelper_Plugin', 'addHelper');
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('JalaliDateHelper_Plugin', 'adjustCommentTimezone');
        
        return 'پلاگین تاریخ شمسی با پشتیبانی از منطقه زمانی فعال شد.';
    }

    /**
     * غیرفعال کردن پلاگین
     */
    public static function deactivate()
    {
        return 'پلاگین تاریخ شمسی غیرفعال شد.';
    }

    /**
     * تنظیمات پلاگین
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // تنظیم منطقه زمانی
        $timezoneEnabled = new Typecho_Widget_Helper_Form_Element_Radio(
            'timezone_enabled', 
            ['1' => 'فعال', '0' => 'غیرفعال'], 
            self::$defaultSettings['timezone_enabled'],
            'فعال‌سازی منطقه زمانی',
            'در صورت فعال بودن، منطقه زمانی تنظیم می‌شود.'
        );
        $form->addInput($timezoneEnabled);

        // انتخاب منطقه زمانی (ایران، افغانستان، تاجیکستان)
        $timezones = [
            'Asia/Tehran' => 'تهران (ایران)',
            'Asia/Kabul' => 'کابل (افغانستان)',
            'Asia/Dushanbe' => 'دوشنبه (تاجیکستان)',
            'UTC' => 'UTC (زمان جهانی)'
        ];
        
        $timezone = new Typecho_Widget_Helper_Form_Element_Select(
            'timezone', 
            $timezones, 
            self::$defaultSettings['timezone'],
            'منطقه زمانی',
            'منطقه زمانی مورد نظر را انتخاب کنید.'
        );
        $form->addInput($timezone);

        // نمایش ثانیه
        $showSeconds = new Typecho_Widget_Helper_Form_Element_Radio(
            'show_seconds', 
            ['1' => 'نمایش', '0' => 'مخفی'], 
            self::$defaultSettings['show_seconds'],
            'نمایش ثانیه در تاریخ',
            'در صورت فعال بودن، ثانیه در فرمت تاریخ نمایش داده می‌شود.'
        );
        $form->addInput($showSeconds);

        // فرمت پیشنهادی تاریخ
        $dateFormat = new Typecho_Widget_Helper_Form_Element_Text(
            'date_format',
            NULL,
            self::$defaultSettings['date_format'],
            'فرمت پیش‌فرض تاریخ',
            'فرمت نمایش تاریخ (مثال: Y/m-d H:i:s)'
        );
        $form->addInput($dateFormat);
    }

    /**
     * تنظیمات شخصی کاربران
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * افزودن تابع کمکی به پوسته
     */
    public static function addHelper($archive)
    {
        if (!function_exists('jalali_date')) {
            /**
             * تبدیل تاریخ میلادی به شمسی
             */
            function jalali_date($format, $timestamp = null)
            {
                // دریافت تنظیمات پلاگین
                $options = Typecho_Widget::widget('Widget_Options');
                $pluginOptions = $options->plugin('JalaliDateHelper');
                
                // تعیین مقادیر پیش‌فرض به صورت مستقیم
                $tzEnabled = isset($pluginOptions->timezone_enabled) ? $pluginOptions->timezone_enabled : '1';
                $timezone = isset($pluginOptions->timezone) ? $pluginOptions->timezone : 'Asia/Tehran';
                
                // تنظیم منطقه زمانی
                if ($tzEnabled == '1') {
                    if (in_array($timezone, timezone_identifiers_list())) {
                        date_default_timezone_set($timezone);
                        
                        if (class_exists('DateTime')) {
                            try {
                                $dtz = new DateTimeZone($timezone);
                                if (is_null($timestamp)) {
                                    $dt = new DateTime('now', $dtz);
                                    $timestamp = $dt->getTimestamp();
                                } else {
                                    $dt = new DateTime('@' . $timestamp);
                                    $dt->setTimezone($dtz);
                                    $timestamp = $dt->getTimestamp();
                                }
                            } catch (Exception $e) {
                                date_default_timezone_set($timezone);
                            }
                        }
                    }
                }
                
                if (is_null($timestamp)) {
                    $timestamp = time();
                }

                $timestamp = intval($timestamp);
                
                // محاسبات تبدیل تاریخ
                $g_y = date('Y', $timestamp);
                $g_m = date('m', $timestamp);
                $g_d = date('d', $timestamp);

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

                $months = array(
                    1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
                    5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
                    9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
                );

                $weekdays = array(
                    'Saturday' => 'شنبه', 'Sunday' => 'یکشنبه', 'Monday' => 'دوشنبه',
                    'Tuesday' => 'سه‌شنبه', 'Wednesday' => 'چهارشنبه', 'Thursday' => 'پنجشنبه',
                    'Friday' => 'جمعه'
                );

                $out = $format;
                $replacements = array(
                    'F' => $months[(int)$jm],
                    'Y' => $jy, 'y' => substr($jy, -2),
                    'm' => str_pad($jm, 2, '0', STR_PAD_LEFT), 'n' => $jm,
                    'd' => str_pad($jd, 2, '0', STR_PAD_LEFT), 'j' => $jd,
                    'H' => date('H', $timestamp), 'h' => date('h', $timestamp),
                    'i' => date('i', $timestamp), 's' => date('s', $timestamp),
                    'A' => (date('A', $timestamp) == 'AM' ? 'ق.ظ' : 'ب.ظ'),
                    'a' => (date('a', $timestamp) == 'am' ? 'ق.ظ' : 'ب.ظ'),
                    'l' => $weekdays[date('l', $timestamp)],
                    'D' => mb_substr($weekdays[date('l', $timestamp)], 0, 1, 'UTF-8')
                );

                foreach ($replacements as $key => $value) {
                    $out = str_replace($key, $value, $out);
                }

                return $out;
            }
        }
    }

    /**
     * تنظیم منطقه زمانی برای کامنت‌ها
     */
    public static function adjustCommentTimezone($comment)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $pluginOptions = $options->plugin('JalaliDateHelper');
        
        if (isset($pluginOptions->timezone_enabled) && $pluginOptions->timezone_enabled == '1') {
            $timezone = isset($pluginOptions->timezone) ? $pluginOptions->timezone : self::$defaultSettings['timezone'];
            
            if (in_array($timezone, timezone_identifiers_list())) {
                $oldTimezone = date_default_timezone_get();
                date_default_timezone_set($timezone);
                time();
                date_default_timezone_set($oldTimezone);
            }
        }
        return $comment;
    }

    /**
     * دریافت تنظیمات پلاگین
     */
    public static function getSettings()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $pluginOptions = $options->plugin('JalaliDateHelper');
        
        return array(
            'timezone_enabled' => isset($pluginOptions->timezone_enabled) ? $pluginOptions->timezone_enabled : self::$defaultSettings['timezone_enabled'],
            'timezone' => isset($pluginOptions->timezone) ? $pluginOptions->timezone : self::$defaultSettings['timezone'],
            'show_seconds' => isset($pluginOptions->show_seconds) ? $pluginOptions->show_seconds : self::$defaultSettings['show_seconds'],
            'date_format' => isset($pluginOptions->date_format) ? $pluginOptions->date_format : self::$defaultSettings['date_format']
        );
    }
}
