<?php

declare(strict_types=1);

namespace LaravelHelperObjectTools\Helpers;

use LaravelHelperObjectTools\Helpers\compareDirectories\compareDirectories;
use LaravelHelperObjectTools\Helpers\compareImages\compareImages;
use LaravelHelperObjectTools\Helpers\Sentence\Sentence;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Carbon\Carbon;
use DateTime;
use DOMDocument;
use DonatelloZa\RakePlus\RakePlus;
use Error;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Nahid\Linkify\Facades\Linkify;
use PDF;
use PhpOffice\PhpWord\IOFactory;
use PhpPptTree;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SEO;
use Smalot\PdfParser\Parser;
use Stringizer\Stringizer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TypeError;
use ZipArchive;

/**
 * Import the PDF Parser class
 */
define('DECIMALS', 2);

define('DEC_POINT', '.');

define('THOUSANDS_SEP', ',');

class Helper
{
    public string $naira = '&#8358;';
    protected string $alphabet;
    protected int $alphabetLength;

    public function __construct(string $alphabet = '')
    {
        if ($alphabet !== '') {
            $this->setAlphabet($alphabet);
        } else {
            $this->setAlphabet(implode(range('a', 'z')) . implode(range('A', 'Z')) . implode(range(0, 9)));
        }
    }

    public function setAlphabet(string $alphabet): void
    {
        $this->alphabet = $alphabet;
        $this->alphabetLength = strlen($alphabet);
    }

    /**
     * underscoreToCamelCase
     * Covert lower_underscored mysql notation into Camel/Pascal case notation
     *
     * @param    $string     string to convert into Camel/Pascal case notation
     * @param bool $pascalCase If true the result is PascalCase
     * @return string
     */
    public static function underscoreToCamelCase($string, $pascalCase = false)
    {
        $string = strtolower($string);

        if ($pascalCase === true) {
            $string[0] = strtoupper($string[0]);
        }
        $func = function ($c) {
            return strtoupper($c[1]);
        };

        return preg_replace_callback('/_([a-z])/', $func, $string);
    }

    public static function bodyClass(): string
    {
        $body_classes = [];
        $class = 'page';
        if (Auth::guest()) {
            $class .= ' not-logged-in';
        }

        foreach (Request::segments() as $segment) {
            if (is_numeric($segment) || empty($segment)) {
                continue;
            }

            $class .= !empty($class) ? ' ' . $segment : $segment;

            $body_classes[] = $class;
        }

        return !empty($body_classes) ? implode(' ', $body_classes) : 'home';
    }

    /**
     * @param $path
     * @return string
     */
    public static function headless_url($path): string
    {
        $url = substr(url('/'), 5) . '/' . $path;
        if (str_ends_with($url, '//')) {
            return substr($url, 0, -1);
        }

        return $url;
    }

    /**
     * @param $path
     * @return string
     */
    public static function assignActivePath($path): string
    {
        return Request::is($path) ? ' active' : '';
    }

    /**
     * @param $route
     * @return string
     */
    public static function assignActiveRoute($route): string
    {
        return Route::currentRouteName() === $route ? ' active' : '';
    }


    public static function mainKeywords(): string
    {
        return 'legitcar, Verify vehicle, Verify Vin, vin Verify, vin number, vin, verify car, confirm car, confirm vehicle, legitcar nigeria, legitcar ng, report car, report vehicle, report missing vehicle, report missing vehicle nigeria, find missing car, find missing vehicle, how to buy a car, how to buy a vehicle, how to buy a car nigeria, how to buy a vehicle nigeria, how to buy a geniune car, how to buy a geniune vehicle, how to buy a geniune car nigeria, how to buy a geniune vehicle nigeria, naija stolen cars, 9ja stolen cars, how to avoid buying a stolen car in Nigeria, how to verify a car before purchase in Nigeria, how to confirm a car is not stolen in Nigeria, how to buy a used car in Nigeria, report a vehicle as stolen in Nigeria, how to report a missing vehicle in Nigeria';
    }

    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    public static function validateDate($date, string $format = 'Y-m-d H:i:s'): bool
    {
        //note: $d->format($format) is something like 12-02-2011
        //date like 12-2-2011 will fail. so pad date before using this function (ie put 0 infront of 2)
        //TODO: https://stackoverflow.com/a/31924668 add comment to SO when I get enough reputation
        return $date === date($format, strtotime($date));
    }

    /**
     * Generate v4 UUID. Version 4 UUIDs are pseudo-random.
     */
    public static function uuidV4(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            random_int(0, 0xFFFF),
            random_int(0, 0xFFFF),

            // 16 bits for "time_mid"
            random_int(0, 0xFFFF),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            random_int(0, 0x0FFF) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            random_int(0, 0x3FFF) | 0x8000,

            // 48 bits for "node"
            random_int(0, 0xFFFF),
            random_int(0, 0xFFFF),
            random_int(0, 0xFFFF)
        );
    }

    public static function isLastFridayOfMonth(): bool
    {
        $lastFriday = date('Y-m-d', strtotime('last friday of this month'));

        return date('Y-m-d') === $lastFriday;
    }

    public static function mainClass(string $pageID = '', bool $makeStatic = true): string
    {
        $padeIDs = [
            'contact', 'blog', 'support_us',
        ];
        $classes = 'content-wrap access container-fluid';
        if (in_array($pageID, $padeIDs)) {
            return $classes;
        }
        if ($makeStatic === false) {
            return $classes;
        }

        return $classes . ' static';
    }

    /**
     * compare two strings, ignoring case
     */
    public static function areTheSame(string $str1, string $str2): bool
    {
        return self::equalIgnoreCase($str1, $str2);
    }

    /**
     * compare two strings, ignoring case
     */
    public static function equalIgnoreCase(string $str1, string $str2): bool
    {
        return strcasecmp(trim($str1), trim($str2)) === 0;
    }

    /**
     * @param $email
     * @return string
     */
    public static function firstNameFromEmail($email): string
    {
        $names = static::strBefore($email, '@');
        if (Str::contains($names, '-')) {
            $name = static::strBefore($names, '-');
            if (!empty($name)) {
                return Str::ucfirst($name);
            }

            return Str::ucfirst(static::strBefore($names, '-'));
        }
        if (Str::contains($names, '.')) {
            $name = static::strBefore($names, '.');
            if (!empty($name)) {
                return Str::ucfirst($name);
            }

            return Str::ucfirst(static::strBefore($names, '.'));
        }
        if (!Str::contains($names, '_')) {
            return Str::ucfirst($names);
        }
        $name = static::strBefore($names, '_');
        if (!empty($name)) {
            return Str::ucfirst($name);
        }

        return Str::ucfirst(static::strBefore($names, '_'));
    }

    /**
     * Get the portion of a string before a given value.
     */
    public static function strBefore(string $subject, string $search): string
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }

    /**
     * @param $value
     * @param $needle
     * @return bool|mixed
     */
    public function contains($value, $needle): mixed
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->contains($needle);
    }

    /**
     * Unlike standard empty function isEmpty also assigns true if value contains whitespaces, newlines, tabs
     */
    public function isEmpty(mixed $value): bool
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (is_array($value)) {
            if (count(array_filter($value)) === 0) {
                return true;
            }
        } elseif (!isset($value) || empty($value) || $value === '' || is_null($value)) {
            return true;
        }

        return false;
    }

    /**
     * @param $email
     * @return string
     */
    public static function lastNameFromEmail($email): string
    {
        $names = static::strBefore($email, '@');
        if (Str::contains($names, '-')) {
            $name = Str::after($names, '-');
            if (!empty($name)) {
                return Str::ucfirst($name);
            }

            return Str::ucfirst(Str::after($names, '-'));
        }
        if (Str::contains($names, '.')) {
            $name = Str::after($names, '.');
            if (!empty($name)) {
                return Str::ucfirst($name);
            }

            return Str::ucfirst(Str::after($names, '.'));
        }
        if (!Str::contains($names, '_')) {
            return Str::ucfirst($names);
        }
        $name = Str::after($names, '_');
        if (!empty($name)) {
            return Str::ucfirst($name);
        }

        return Str::ucfirst(Str::after($names, '_'));
    }

    /**
     * @param $string
     * @return bool
     */
    public static function containsHtml($string): bool
    {
        return preg_match('/<[^<]+>/', $string, $m) !== 0;
    }

    /**
     * Search for the contents of an array in a given string
     *
     * @param string $haystack //The string to search
     * @param mixed(Array/String) $arr //The array to whose contents will be searched for
     *
     * @return bool true on success, false on failure
     */
    public function stringSearch(string $haystack, $arr): bool
    {
        $found = false;
        if (is_array($arr)) {
            foreach ($arr as $value) {
                $found = $this->searchWholeWord($haystack, $value);
                if ($found) {
                    break;
                }
            }
        } else {
            $found = $this->searchWholeWord($haystack, $arr);
        }

        return $found;
    }

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public function searchWholeWord($haystack, $needle): bool
    {
        return preg_match("/\b$needle\b/i", $haystack) === 1;
    }

    /**
     * Check if string contains array item
     *
     * @param array $words
     */
    public function containsArrayItem(string $str, array $words): bool
    {
        if (!is_string($str)) {
            return false;
        }
        foreach ($words as $word) {
            if (is_string($word) && stripos($str, $word) === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the filename with the extension attached
     *
     * @return array|string|string[]
     */
    public function getFilenameWithExtension(mixed $targetFile): array|string
    {
        return pathinfo($targetFile, PATHINFO_FILENAME);
    }

    /**
     * @param $file
     * @return string
     */
    public function getFileIcon($file)
    {
        return asset("frontend/images/icons/{$this->getFileExtension($file)}.png");
    }

    /**
     * Get the extension of the file
     *
     * @param mixed $targetFile
     * @return array|string|string[]
     */
    public function getFileExtension(mixed $targetFile): array|string
    {
        return pathinfo($targetFile, PATHINFO_EXTENSION);
    }

    /**
     * @param $directory
     * @return int
     */
    public function countFiles($directory)
    {
        $files = File::files(public_path($directory));

        $filecount = 0;
        if ($files !== false) {
            $filecount = count($files);
        }

        return $filecount;
    }

    /**
     * @param $value
     * @return string
     */
    public function encrypt($value)
    {
        return encrypt($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function decrypt($value)
    {
        return decrypt($value);
    }

    /**
     * @param $string
     * @return string
     */
    public function string2Hex($string): string
    {
        $hex = '';
        $iMax = strlen($string);
        for ($i = 0; $i < $iMax; $i++) {
            $hex .= dechex(ord($string[$i]));
        }

        return $hex;
    }

    /**
     * @param $hex
     * @return string
     */
    public function hex2String($hex): string
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }

        return $string;
    }

    /**
     * Change date format between MySQL and System date
     *
     * @param string $date
     * @return string
     */
    public function changeDateFormat(string $date): string
    {
        return date('Y-m-d H:i:s', strtotime($date));
    }

    /**
     * Change time format
     *
     * @param string $time
     * @return string
     */
    public function changeTimeFormat(string $time): string
    {
        return date('H:i:s', strtotime($time));
    }

    /**
     * Determines if $number is between $min and $max
     *
     * @param int $number The number to test
     * @param int $min The minimum value in the range
     * @param int $max The maximum value in the range
     * @param bool $inclusive Whether the range should be inclusive or not
     *
     * @return bool              Whether the number was in the range
     */
    public function numberInRange(int $number, int $min, int $max, bool $inclusive = true): bool
    {
        return $inclusive ? ($number >= $min && $number <= $max) : ($number > $min && $number < $max);
    }

    /**
     * Display number in Naira format
     */
    public function showMoney(int|float $amount): string
    {
        return $this->naira . $this->formatDecimal($amount);
    }

    /**
     * Format a number into decimal places
     */
    public function formatDecimal(int $amount): string
    {
        return number_format($amount, DECIMALS, DEC_POINT, THOUSANDS_SEP);
    }

    /**
     * function, receives string, returns SEO friendly version for that strings,
     * sample: 'Hotels in Buenos Aires' => 'hotels-in-buenos-aires'
     * - converts all alpha chars to lowercase
     * - converts any char that is not digit, letter or - into - symbols into "-"
     * - not allow two "-" chars continued, convert them into only one single "-"
     */
    public function URLify(string $vp_string): string
    {
        $vp_string = trim($vp_string);
        $vp_string = html_entity_decode($vp_string);
        $vp_string = strip_tags($vp_string);
        $vp_string = strtolower($vp_string);
        $vp_string = preg_replace('~[^ a-z0-9_.]~', ' ', $vp_string);
        $vp_string = preg_replace('~ ~', '-', $vp_string);

        return preg_replace('~-+~', '-', $vp_string);
    }

    /**
     * Converts URLs and email addresses into clickable links
     */
    public function LINKify(string $text): string
    {
        return (new Linkify())->process($text, ['attr' => ['style' => 'font-weight: bold; color: #0d59af;']]);
    }

    /**
     * @param $value
     * @param $left
     * @param $right
     * @return mixed|string
     */
    public function betweenValues($value, $left, $right)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->between($left, $right)->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function camelize($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->camelize()->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function camelToSnake($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->camelToSnake()->getString();
    }

    /**
     * @param $value
     * @param $index
     * @return mixed|string
     */
    public function charAt($value, $index)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->charAt($index)->getString();
    }

    /**
     * @param $value
     * @return array|false|mixed|string[]
     */
    public function chars($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->chars();
    }

    /**
     * @param $value
     * @param $prefix
     * @return mixed|string
     */
    public function chopLeft($value, $prefix)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->chopLeft($prefix)->getString();
    }

    /**
     * @param $value
     * @param $prefix
     * @return mixed|string
     */
    public function chopRight($value, $prefix)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->chopRight(
            $prefix
        )->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function collapseWhitespace($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->collapseWhitespace()->getString();
    }

    /**
     * Append 2 String values
     *
     * @param string $preAppend
     *            flag when true to prepend value
     */
    public function concat(string $value, $value2, $prepend = false): Stringizer
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->concat($value2, $prepend)->getString();
    }

    /**
     * @param $value
     * @param $needle
     * @return mixed
     */
    public function containsIncaseSensitive($value, $needle): mixed
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->containsIncaseSensitive($needle);
    }

    /**
     * @param $value
     * @param $needle
     * @return int|mixed
     */
    public function containsCount($value, $needle)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->containsCount($needle);
    }

    /**
     * @param $value
     * @param $needle
     * @return mixed
     */
    public function containsCountIncaseSensitive($value, $needle)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->containsCountIncaseSensitive($needle);
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function dasherize($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->dasherize()->getString();
    }

    /**
     * @param $value
     * @param $needle
     * @return bool|mixed
     */
    public function endsWith($value, $needle)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->endsWith($needle);
    }

    /**
     * @param $value
     * @param $prefix
     * @return mixed|string
     */
    public function ensureLeft($value, $prefix)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->ensureLeft($prefix)->getString();
    }

    /**
     * @param $value
     * @param $suffix
     * @return mixed|string
     */
    public function ensureRight($value, $suffix)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->ensureRight(
            $suffix
        )->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function hashCode($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->hashCode()->getString();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function hasLowercase($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->hasLowercase();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function hasUppercase($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->hasUppercase();
    }

    /**
     * @param    $value
     * @param    $needle
     * @param int $offset
     * @return mixed
     */
    public function indexOf($value, $needle, int $offset = 0)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->indexOf($needle, $offset)->getString();
    }

    /**
     * @param    $value
     * @param    $needle
     * @param int $offset
     * @return mixed
     */
    public function indexOfCaseInsensitive($value, $needle, $offset = 0)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->indexOfCaseInsensitive($needle, $offset)->getString();
    }

    /**
     * @param $value
     * @param $left
     * @param $right
     * @return string
     */
    public function insertBetween($value, $left, $right)
    {
        return $left . $value . $right;
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isAlpha($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isAlpha();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isAlphaNumeric($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isAlphaNumeric();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isAlphaNumericSpace($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isAlphaNumericSpace();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isAlphaNumericSpaceDash($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isAlphaNumericSpaceDash();
    }

    /**
     * @param    $value
     * @param false $isPrintableOnly
     * @return bool|mixed
     */
    public function isAscii($value, $isPrintableOnly = false)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isAscii($isPrintableOnly);
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isBase64($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isBase64();
    }

    /**
     * Alias for isEmpty
     */
    public function isBlank($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isBlank();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isDate($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isDate();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isDecimal($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isDecimal();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isEmail($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isEmail();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isHexColor($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isHexColor();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isHexDecimal($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isHexDecimal();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isIsbn10($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isIsbn10();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isIsbn13($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isIsbn13();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isIpv4($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isIpv4();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isIpv6($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isIpv6();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isJson($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isJson();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isLatitude($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isLatitude();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isLongitude($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isLongitude();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isMultiByte($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isMultiByte();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isNumber($value)
    {
        if ($this->isEmpty($value) && ($value !== 0)) {
            return $value;
        }
        return (new Stringizer($value))->isNumber();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isRgbColor($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isRgbColor();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function isSemver($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->isSemver();
    }

    /**
     * @param    $value
     * @param string $separator
     * @return mixed|string
     */
    public function join($value, $separator = ',')
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->join($value, $separator)->getString();
    }

    /**
     * @param $value
     * @param $numberOfCharacters
     * @return mixed|string
     */
    public function last($value, $numberOfCharacters)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->last($numberOfCharacters)->getString();
    }

    /**
     * @param    $value
     * @param    $needle
     * @param int $offset
     * @return false|int|mixed
     */
    public function lastIndexOf($value, $needle, $offset = 0)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->lastIndexOf(
            $needle,
            $offset
        );
    }

    /**
     * @param    $value
     * @param    $needle
     * @param int $offset
     * @return mixed
     */
    public function lastIndexOfCaseInsensitive($value, $needle, $offset = 0)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->lastIndexOfCaseInsensitive(
            $needle,
            $offset
        );
    }

    /**
     * @param $value
     * @return int|mixed
     */
    public function lineCount($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->lineCount();
    }

    /**
     * Length
     *
     * @return int length of string
     */
    public function length($value): int
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->length();
    }

    /**
     * @param    $value
     * @param false $ignoreUppercaseFirst
     * @return mixed|string
     */
    public function lowercaseFirst($value, $ignoreUppercaseFirst = false)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->lowercaseFirst(
            $ignoreUppercaseFirst
        )->getString();
    }

    /**
     * @param $value
     * @param $padValue
     * @param $padAmount
     * @return mixed|string
     */
    public function padBoth($value, $padValue, $padAmount)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->padBoth($padValue, $padAmount)->getString();
    }

    /**
     * @param $value
     * @param $padValue
     * @param $padAmount
     * @return mixed|string
     */
    public function padLeft($value, $padValue, $padAmount)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->padLeft($padValue, $padAmount)->getString();
    }

    /**
     * @param $value
     * @param $padValue
     * @param $padAmount
     * @return mixed|string
     */
    public function padRight($value, $padValue, $padAmount)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->padRight($padValue, $padAmount)->getString();
    }

    /**
     * @param    $value
     * @param int $length
     * @return mixed|string
     */
    public function randomAlpha($value, $length = 10)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->randomAlpha($length)->getString();
    }

    /**
     * @param    $value
     * @param int $length
     * @return mixed|string
     */
    public function randomAlphanumeric($value, $length = 10)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->randomAlphanumeric($length)->getString();
    }

    /**
     * @param    $value
     * @param int $length
     * @return mixed|string
     */
    public function randomNumeric($value, $length = 10)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->randomNumeric($length)->getString();
    }

    /**
     * @param $value
     * @param $repeatNumber
     * @return mixed|string
     */
    public function repeat($value, $repeatNumber)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->repeat($repeatNumber)->getString();
    }

    /**
     * @param $search
     * @param $replace
     * @param $value
     * @return mixed|string
     */
    public function replace($search, $replace, $value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->replace($search, $replace)->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function replaceAccents($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->replaceAccents()->getString();
    }

    /**
     * @param $search
     * @param $replace
     * @param $value
     * @return mixed|string
     */
    public function replaceIncaseSensitive($search, $replace, $value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->replaceIncaseSensitive($search, $replace)->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function removeNonAscii($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->removeNonAscii()->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function removeWhitespace($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->removeWhitespace()->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function reverse($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->reverse()->getString();
    }

    /**
     * @param $value
     * @return int|mixed
     */
    public function sentenceCount($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->sentenceCount();
    }

    /**
     * @param $value
     * @param $needle
     * @return bool|mixed
     */
    public function startsWith($value, $needle)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->startsWith($needle);
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function stripPunctuation($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->stripPunctuation()->getString();
    }

    /**
     * @param    $value
     * @param    $start
     * @param null $length
     * @return mixed|string
     */
    public function subString($value, $start, $length = null)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->subString($start, $length)->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function swapCase($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->swapCase()->getString();
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    public function toBoolean($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->toBoolean();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function trim($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->trim()->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function trimLeft($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->trimLeft()->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function trimRight($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->trimRight()->getString();
    }

    /**
     * Truncate remove the number of indicated values at the end of the string
     *
     * @throws InvalidArgumentException
     */
    public function truncate($value, int $numberToTruncate): Stringizer
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->truncate($numberToTruncate)->getString();
    }

    /**
     * @param    $value
     * @param    $stringToMatch
     * @param false $truncateBefore
     * @return mixed|string
     */
    public function truncateMatch($value, $stringToMatch, $truncateBefore = false)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->truncateMatch($stringToMatch, $truncateBefore)->getString();
    }

    /**
     * @param    $value
     * @param    $stringToMatch
     * @param false $truncateBefore
     * @return mixed|string
     */
    public function truncateMatchCaseInsensitive($value, $stringToMatch, $truncateBefore = false)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->truncateMatchCaseInsensitive($stringToMatch, $truncateBefore)->getString();
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function uppercase($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->uppercase()->getString();
    }

    /**
     * @param    $value
     * @param bool $ignoreLowercaseFirst
     * @return mixed|string
     */
    public function uppercaseFirst($value, bool $ignoreLowercaseFirst = false): mixed
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->uppercaseFirst($ignoreLowercaseFirst)->getString();
    }

    /**
     * @param $value
     * @return array|mixed|string|string[]
     */
    public function uppercaseWords($value): mixed
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $value = str_replace(array(',', '. ,', ' ,', '  '), array(', ', ', ', ', ', ' '), $value);
        $s = new Stringizer($value);
        $value = $s->uppercaseWords()->getString();
        $delimiters = ['-', '\'', '/', '(', "'", '.', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        foreach ($delimiters as $delimiter) {
            if (str_contains($value, $delimiter)) {
                $value = implode($delimiter, array_map('ucfirst', explode($delimiter, $value)));
            }
        }
        return str_replace('.', '. ', $value);
    }

    /**
     * @param $value
     * @return false|int|mixed
     */
    public function width($value): mixed
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->width();
    }

    /* alternative of var_dump with pre or json formating */

    /**
     * @param $value
     * @return int|mixed
     */
    public function wordCount($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        return (new Stringizer($value))->wordCount();
    }

    /**
     * @param string $encoding
     *
     * @return null
     * @throws Exception
     */
    public function setEncoding(string $encoding = 'UTF-8')
    {
        return (new Stringizer('dummy-value'))->setEncoding($encoding);
    }

    public function getEncoding(): string
    {
        return (new Stringizer('dummy-value'))->getEncoding();
    }

    /**
     * @param    $var
     * @param null $task
     * @return void|null
     */
    public function vardump($var, $task = null)
    {
        if ($this->isEmpty($var)) {
            return null;
        }
        if (!empty($task)) {
            if ($task === 'pre') {
                echo '<div><pre>';
                var_dump($var, true);
                echo '</pre></div>';
            } elseif ($task === 'json') {
                $json = json_encode((array)$var);
                echo $json;
            }
        } else {
            var_dump($var);
        }
    }

    /**
     * Generate random password
     */
    public function generateRandomPassword(int $len = 10): string
    {
        $alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789[{(*%+-_^$#&!=)}]';
        $pass = [];
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $len; $i++) {
            $n = random_int(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }

        return implode($pass);
    }

    /**
     * Generate a string of random characters
     *
     * @return string of random characters
     */
    public function generateRandomString(int $numberOfCharacters = 32): string
    {
        $string = null;
        try {
            $string = random_bytes($numberOfCharacters);
        } catch (TypeError $e) {
            // Well, it's an integer, so this IS unexpected.
            exit('An unexpected error has occurred');
        } catch (Error $e) {
            // This is also unexpected because 32 is a reasonable integer.
            exit('An unexpected error has occurred');
        } catch (Exception $e) {
            // If you get this message, the CSPRNG failed hard.
            exit('Could not generate a random string. Is our OS secure?');
        }

        return $string;
    }

    /**
     * Generate a random integer between two given integers (inclusive)
     *
     * @param int $start
     * @param int $end
     *
     * @return int of random numbers
     */
    public function generateRandomNumber($min = 0, $max = 255): int
    {
        $int = 0;
        if (!$this->isEmpty($min) && !$this->isEmpty($max)) {
            $min = intval($min);
            $max = intval($max);
            if ($min > $max) {
                $tmp = $max;
                $max = $min;
                $min = $tmp;
            }
            $int = random_int($min, $max);
        } else {
            $int = random_int($min, $max);
        }

        return $int;
    }

    /**
     * Generate a random float between two given integers (inclusive)
     *
     * @param int $min
     * @param int $max
     * @return float of random numbers
     * @throws Exception
     */
    public function generateRandomFloat(int $min = 0, int $max = 1): float
    {
        if ($min > $max) {
            $tmp = $max;
            $max = $min;
            $min = $tmp;
        }

        return $min + random_int($min, $max) / mt_getrandmax() * ($max - $min);
    }

    /**
     * Convert time stamp to date
     */
    public function timestampToDate(int $timestamp, string $dateTimeFormat = 'Y-m-d H:i:s'): string
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);

        return $date->format($dateTimeFormat);
    }

    /**
     * Copy file to array
     *
     * @param string $file F
     *
     * @return array
     */
    public function fileToArray(string $file): array
    {
        $codes = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            @[$key, $val] = explode(' ', $line, 2);
            $codes[$key] = $val;
        }

        return $codes;
    }

    /**
     * Return local images as base64 encrypted code
     *
     * @param string $filetype
     */
    public function encodeImageToBase64(string $filename): string
    {
        $retVal = null;
        if ($this->isEmpty($filename)) {
            return $retVal;
        }
        $check = getimagesize($filename);
        if ($check !== false) {
            $data = base64_encode(file_get_contents($filename));
            $retVal = 'data:' . $check['mime'] . ';base64,' . $data;
        } else {
            $retVal = 'File is not an image.';
        }

        return $retVal;
    }

    /**
     * Return local file as base64 encrypted
     */
    public function encodeFileTobase64(string $file): string
    {
        if (!file_exists($file)) {
            throw new RuntimeException('File does not exist');
        }

        $vidbinary = fread(fopen($file, 'rb'), filesize($file));
        $filetype = $this->getMimeType($file);

        return 'data:' . $filetype . ';base64,' . base64_encode($vidbinary);
    }

    public function getMimeType(string $file): bool|string
    {
        $mimetype = '';
        if ($this->getFileExtension($file) === 'mp3') {
            $mimetype = 'audio/mp3';
        }
        if (!$this->isEmpty($mimetype)) {
            return $mimetype;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimetype = finfo_file($finfo, $file);
            finfo_close($finfo);
        } else {
            $mimetype = mime_content_type($file);
        }
        if ($this->isEmpty($mimetype)) {
            $mimetype = 'application/octet-stream';
        }

        return $mimetype;
    }

    /**
     * Computes the difference of arrays with additional index check.
     *
     * @param array $array1 the array to compare from
     * @param array $arrays an array(s) to compare against
     *
     * @return array an array containing all the values from
     *               array1 that are not present in any of the other arrays
     */
    public function arrayDiff(array $array1, array ...$arrays): array
    {
        $difference = [];
        foreach ($arrays as $array2) {
            foreach ($array1 as $key => $value) {
                if (is_array($value)) {
                    if (!isset($array2[$key]) || !is_array($array2[$key])) {
                        $difference[$key] = $value;
                    } else {
                        $new_diff = static::diff($value, $array2[$key]);
                        if (!empty($new_diff)) {
                            $difference[$key] = $new_diff;
                        }
                    }
                } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                    $difference[$key] = $value;
                }
            }
        }

        return $difference;
    }

    public function csvToArray(string $filename = '', string $delimiter = ','): array|false
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = [];
        $data = [];
        if (($handle = fopen($filename, 'rb')) === false) {
            return $data;
        }
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            if (!$header) {
                $header = $row;
            } else {
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);

        return $data;
    }

    /**
     * Get a random value from an array, with the ability to skew the results.
     * Example: array_rand_weighted(['foo' => 1, 'bar' => 2]) has a 66% chance of returning bar.
     *
     * @param array $array
     */
    public function arrayRandWeighted(array $array): mixed
    {
        $options = [];

        foreach ($array as $option => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $options[] = $option;
            }
        }

        return $this->arrayRandValue($options);
    }

    /**
     * Get a random value from an array.
     *
     * @param array $array
     * @param int $numReq The amount of values to return
     * @return mixed
     * @throws Exception
     */
    public function arrayRandValue(array $array, int $numReq = 1): mixed
    {
        if (!count($array)) {
            throw new RuntimeException('Array is empty');
        }

        $keys = array_rand($array, $numReq);

        if ($numReq === 1) {
            return $array[$keys];
        }

        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Determine if all given needles are present in the haystack as array keys.
     *
     * @param array|string $needles
     * @param array $haystack
     * @return bool
     */
    public function arrayKeysExist(array|string $needles, array $haystack): bool
    {
        if (!is_array($needles)) {
            return array_key_exists($needles, $haystack);
        }

        return $this->valuesInArray($needles, array_keys($haystack));
    }

    /**
     * Determine if all given needles are present in the haystack.
     *
     * @param array|string $needles
     * @param array $haystack
     * @return bool
     */
    public function valuesInArray(array|string $needles, array $haystack): bool
    {
        if (!is_array($needles)) {
            $needles = [$needles];
        }

        return count(array_intersect($needles, $haystack)) === count($needles);
    }

    /**
     * Returns an array with two elements.
     *
     * Iterates over each value in the array passing them to the callback function.
     * If the callback function returns true, the current value from array is returned in the first
     * element of result array. If not, it is return in the second element of result array.
     *
     * Array keys are preserved.
     *
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public function arraySplitFilter(array $array, callable $callback): array
    {
        $passesFilter = array_filter($array, $callback);

        $negatedCallback = function ($item) use ($callback) {
            return !$callback($item);
        };

        $doesNotPassFilter = array_filter($array, $negatedCallback);

        return [$passesFilter, $doesNotPassFilter];
    }

    /**
     * Split an array in the given amount of pieces.
     *
     * @param array $array
     * @param int $numberOfPieces
     * @param bool $preserveKeys
     * @return array
     */
    public function arraySplit(array $array, int $numberOfPieces = 2, bool $preserveKeys = false): array
    {
        if (count($array) === 0) {
            return [];
        }

        $splitSize = ceil(count($array) / $numberOfPieces);

        return array_chunk($array, (int)$splitSize, $preserveKeys);
    }

    /**
     * Returns an array with the unique values from all the given arrays.
     *
     * @param array<\array> $arrays
     *
     * @return array
     */
    public function arrayMergeValues(array ...$arrays): array
    {
        $allValues = array_reduce($arrays, function ($carry, $array) {
            return array_merge($carry, $array);
        }, []);

        return array_values(array_unique($allValues));
    }

    /**
     * Flatten an array of arrays. The `$levels` parameter specifies how deep you want to
     * recurse in the array. If `$levels` is -1, the function will recurse infinitely.
     *
     * @param array $array
     * @param int $levels
     *
     * @return array
     */
    public function arrayFlatten(array $array): array
    {
        $result = [];
        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $result[] = $this->arrayFlatten($item);
            }
        }

        return $result;
    }

    /**
     * @param $filename
     * @param $numberOfWordsPerPage
     * @return float|int
     */
    public function numberOfPagesInFile($filename, $numberOfWordsPerPage)
    {
        return $this->countWordsInfile($filename) / $numberOfWordsPerPage;
    }

    /**
     * @param $filename
     * @return int|string
     */
    public function countWordsInfile($filename)
    {
        $retVal = $this->readFile($filename);

        if (strpos($retVal, 'Invalid') !== false) {
            return $retVal;
        }

        return $this->count_words($retVal);
    }

    /**
     * Read file
     *
     * @param string $file File contents
     */
    public function readFile(string $file)
    {
        $retVal = 'Invalid file selected';
        if ($this->isEmpty($file)) {
            return $retVal;
        }
        $text = new file2Text($file);
        $file_extn = $this->getFileExtension($file);
        if ($file_extn === 'doc' || $file_extn === 'docx') {
            $text = new Filetotext($file);
            $contents = $text->convertToText();
        } elseif ($file_extn === 'rtf') {
            $contents = $text->rtf2text($file);
        } elseif ($file_extn === 'pdf') {
            // Parse pdf file and build necessary objects.
            $parser = new Parser();
            $pdf = $parser->parseFile($file);

            // Retrieve all pages from the pdf file.
            $pages = $pdf->getPages();
            $val = [];
            // Loop over each page to extract text.
            foreach ($pages as $page) {
                $val[] = $page->getText();
            }
            $contents = implode(' ', $val);
        } elseif ($file_extn === 'odt') {
            $contents = $text->odt2text();
        } elseif ($file_extn === 'txt') {
            $contents = file_get_contents($file);
        } elseif ($file_extn === 'odp') {
            $pptReader = \PhpOffice\PhpPresentation\IOFactory::createReader('ODPresentation');
            $oPHPPresentation = $pptReader->load($file);
            $oTree = new PhpPptTree($oPHPPresentation);
            $contents = $oTree->display();
        } elseif ($file_extn === 'ppt') {
            $pptReader = \PhpOffice\PhpPresentation\IOFactory::createReader('PowerPoint97');
            $oPHPPresentation = $pptReader->load($file);
            $oTree = new PhpPptTree($oPHPPresentation);
            $contents = $oTree->display();
        } elseif ($file_extn === 'pptx') {
            $pptReader = \PhpOffice\PhpPresentation\IOFactory::createReader('PowerPoint2007');
            $oPHPPresentation = $pptReader->load($file);
            $oTree = new PhpPptTree($oPHPPresentation);
            $contents = $oTree->display;
        } elseif ($file_extn === 'phppt') {
            $pptReader = \PhpOffice\PhpPresentation\IOFactory::createReader('Serialized');
            $oPHPPresentation = $pptReader->load($file);
            $oTree = new PhpPptTree($oPHPPresentation);
            $contents = $oTree->display;
        } else {
            $contents = file_get_contents($file);
        }

        return strip_tags($contents);
    }

    /**
     * @param $filename
     * @return array|false|string|string[]|void|null
     */
    public function convertToText($filename)
    {
        if (isset($filename) && !file_exists($filename)) {
            return 'File Not exists';
        }

        $fileArray = pathinfo($filename);
        $file_ext = $fileArray['extension'];
        if (!($file_ext === 'doc' || $file_ext === 'docx' || $file_ext === 'xlsx' || $file_ext === 'pptx')) {
            return 'Invalid File Type';
        }
        if ($file_ext === 'doc') {
            return $this->readDoc($filename);
        }

        if ($file_ext === 'docx') {
            return $this->readDocx($filename);
        }

        if ($file_ext === 'xlsx') {
            return $this->xlsx_to_text($filename);
        }

        if ($file_ext === 'pptx') {
            return $this->pptx_to_text($filename);
        }
    }

    /**
     * @param $filename
     * @return array|string|string[]|null
     */
    private function readDoc($filename)
    {
        $fileHandle = fopen($filename, 'rb');
        $line = @fread($fileHandle, filesize($filename));
        $lines = explode(chr(0x0D), $line);
        $outtext = '';
        foreach ($lines as $thisline) {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== false) || (strlen($thisline) === 0)) {
                continue;
            }

            $outtext .= $thisline . ' ';
        }
        return preg_replace("/[^a-zA-Z0-9\s,\.\-\n\r\t@\/\_\(\)]/", '', $outtext);
    }

    /**
     * @param $filename
     * @return false|string
     */
    private function readDocx($filename)
    {
        $striped_content = '';
        $content = '';

        $zip = zip_open($filename);

        if (!$zip || is_numeric($zip)) {
            return false;
        }

        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) === false) {
                continue;
            }

            if (zip_entry_name($zip_entry) !== 'word/document.xml') {
                continue;
            }
            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        } // end while

        zip_close($zip);

        $content = str_replace(array('</w:r></w:p></w:tc><w:tc>', '</w:r></w:p>'), array(' ', "\r\n"), $content);
        return strip_tags($content);
    }

    /************************excel sheet************************************/

    private function xlsx_to_text($inputFile)
    {
        $xml_filename = 'xl/sharedStrings.xml'; //content file name
        $zipHandle = new ZipArchive();
        $output_text = '';
        if ($zipHandle->open($inputFile) === true) {
            if (($xml_index = $zipHandle->locateName($xml_filename)) !== false) {
                $xml_datas = $zipHandle->getFromIndex($xml_index);
                $xml_handle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $output_text = strip_tags($xml_handle->saveXML());
            } else {
                $output_text .= '';
            }
            $zipHandle->close();
        } else {
            $output_text .= '';
        }

        return $output_text;
    }

    /*************************power point files*****************************/
    private function pptx_to_text($inputFile)
    {
        $zipHandle = new ZipArchive();
        $output_text = '';
        if ($zipHandle->open($inputFile) === true) {
            $slideNumber = 1; //loop through slide files
            while (($xmlIndex = $zipHandle->locateName('ppt/slides/slide' . $slideNumber . '.xml')) !== false) {
                $xml_datas = $zipHandle->getFromIndex($xmlIndex);
                $xmlHandle = DOMDocument::loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $output_text .= strip_tags($xmlHandle->saveXML());
                $slideNumber++;
            }
            if ($slideNumber === 1) {
                $output_text .= '';
            }
            $zipHandle->close();
        } else {
            $output_text .= '';
        }

        return $output_text;
    }

    /**
     * @param $string
     * @return int
     */
    private function count_words($string)
    {
        // Return the number of words in a string.
        $string = str_replace('&#039;', "'", $string);
        $t = [' ', "\t", '=', '+', '-', '*', '/', '\\', ',', '.', ';', ':', '[', ']', '{', '}', '(', ')', '<', '>', '&', '%', '$', '@', '#', '^', '!', '?', '~']; // separators
        $string = str_replace($t, ' ', $string);
        $string = trim(preg_replace("/\s+/", ' ', $string));
        $num = 0;
        if ($this->my_strlen($string) > 0) {
            $word_array = explode(' ', $string);
            $num = count($word_array);
        }

        return $num;
    }

    /**
     * @param $s
     * @return false|int
     */
    private function my_strlen($s)
    {
        // Return mb_strlen with encoding UTF-8.
        return mb_strlen($s, 'UTF-8');
    }

    /**
     * @param $source
     * @return int
     */
    public function countWords($source)
    {
        $uploadedText = null;
        $phpword = IOFactory::load($source);
        $sections = $phpword->getSections();
        foreach ($sections as $section) {
            $elements = $section->getElements();
            foreach ($elements as $element) {
                if ($element::class === 'PhpOffice\PhpWord\Element\Text') {
                    $uploadedText .= $element->getText();
                    $uploadedText .= ' ';
                } elseif ($element::class === 'PhpOffice\PhpWord\Element\TextRun') {
                    $textRunElements = $element->getElements();
                    foreach ($textRunElements as $textRunElement) {
                        if (method_exists($textRunElement, 'getText')) {
                            $uploadedText .= $textRunElement->getText();
                        }
                        $uploadedText .= ' ';
                    }
                } elseif ($element::class === 'PhpOffice\PhpWord\Element\TextBreak') {
                    $uploadedText .= ' ';
                }
                // else {
                //     throw new Exception('Unknown class type ' . get_class($element));
                // }
            }
        }
        $uploadedText = str_replace('&nbsp;', '', $uploadedText);
        $uploadedText = str_replace('•', '', $uploadedText);
        $uploadedText = preg_split('/\s+/', $uploadedText);
        return count($uploadedText);
    }

    /**
     * @param array $array
     * @param    $search
     * @return int|mixed
     */
    public function getArrayValueByKey(array $array, $search)
    {
        foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $key => $value) {
            if ($search === $key) {
                return $value;
            }
        }

        return -1;
    }

    /**
     * @param    $search
     * @param array $array
     * @return bool|float|int|string|null
     */
    public function getArrayKeyByValue($search, array $array)
    {
        foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $key => $value) {
            if ($search === $value) {
                return $key;
            }
        }

        return -1;
    }

    /**
     * Get the next value in an array after the provided key of the previous value
     *
     * @param array $array
     */
    public function getArrayNextValueAfterKey(string $key, array $array): mixed
    {
        $currentKey = key($array);
        while ($currentKey !== null && $currentKey !== $key) {
            next($array);
            $currentKey = key($array);
        }

        return next($array);
    }

    /**
     * Get the previous value in an array before the provided key of the current value
     *
     * @param array $array
     */
    public function getArrayPreviousValueAfterKey(string $key, array $array): mixed
    {
        $currentKey = key($array);
        while ($currentKey !== null && $currentKey !== $key) {
            prev($array);
            $currentKey = key($array);
        }

        return next($array);
    }

    /**
     * @param    $search
     * @param array $array
     * @param string $mode
     * @return bool
     */
    public function inArray($search, array $array, $mode = 'value')
    {
        return $this->searchNestedArray($search, $array, $mode);
    }

    /**
     * @param    $search
     * @param array $array
     * @param string $mode
     * @return bool
     */
    public function searchNestedArray($search, array $array, $mode = 'value')
    {
        foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $key => $value) {
            if ($search === ${${'mode'}}) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $array
     * @param $value
     * @return int
     */
    public function countItemsInArray($array, $value)
    {
        $count = 0;
        foreach ($array as $val) {
            if ($val === $value) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array $array
     */
    public function isMultiDimensionalArray(array $array): bool
    {
        $flag = false;
        foreach ($array as $value) {
            if (is_array($value)) {
                $flag = true;
            }
        }

        return $flag;
    }

    /**
     * @param $word
     * @return array|mixed|string|string[]|null
     */
    public function removeStopWords($word)
    {
        if (is_array($word)) {
            $word = $this->removeElementFromArray($word, $this->getStopWords());
        } else {
            foreach ($this->getStopWords() as $stopWord) {
                $newStopWords = "/\b$stopWord\b/";
                $word = preg_replace($newStopWords, '', $word);
            }
        }

        return $word;
    }

    /**
     * Remove elements from array
     *
     * @param array $array
     * @param array $to_remove
     *
     * @return array
     */
    public function removeElementFromArray(array $array, array $toRemove): array
    {
        return array_diff($this->removeEmptyArrayElements($array), $toRemove);
    }

    /**
     * @param $array
     * @return array
     */
    public function removeEmptyArrayElements($array)
    {
        return array_values(array_filter($array));
    }

    /**
     * @param $word
     * @return bool
     */
    public function isStopWord($word)
    {
        $word = $this->lowercase($word);
        foreach ($this->getStopWords() as $value) {
            if ($word === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert entire string to lowercase
     */
    public function lowercase($value): Stringizer
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->lowercase()->getString();
    }

    /**
     * Find the largest value in array
     *
     * @param array $array
     */
    public function getLargestArrayValue(array $array): mixed
    {
        return array_reduce($array, function ($carry, $item) {
            return $item > $carry ? $item : $carry;
        });
    }

    /**
     * Find the smallest value in array
     *
     * @param array $array
     */
    public function getSmallestArrayValue(array $array): mixed
    {
        return array_reduce($array, function ($carry, $item) {
            return $item < $carry ? $item : $carry;
        });
    }

    /**
     * Calculate the sum of values in array
     *
     * @param array $array
     */
    public function getSumOfArrayValues(array $array): mixed
    {
        return array_reduce($array, function ($carry, $item) {
            return $carry + $item;
        });
    }

    public function toFloat(string $value): float
    {
        return floatval($this->replaceComma($value));
    }

    /**
     * @param $string
     * @return array|string|string[]
     */
    public function replaceComma($string)
    {
        return str_replace(',', '', $string);
    }

    /**
     * @param $value
     * @return float|int
     */
    public function nairaToKobo($value)
    {
        return $value * 100;
    }

    /**
     * @param $value
     * @return float|int
     */
    public function koboToNaira($value)
    {
        return $value / 100;
    }

    /**
     * Replaces backslash present into MySQL strings which containing apostrophes.
     *
     * @param string $field The field to replace
     *
     * @return string the field without backslash for the apostrophes
     */
    public function replaceAposBackSlash(string $field): string
    {
        $r1 = str_replace("\'", "'", $field);
        return str_replace('\\\\', '\\', $r1);
    }

    /**
     * replace dashes with underscore
     */
    public function replaceDash(string $string): string
    {
        return str_replace('-', '_', $string);
    }

    public function detectBrowserLanguage(): string
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        } else {
            $lang = 'en';
        }

        return $lang;
    }

    /**
     * @param $text
     * @return array
     */
    public function getPhrases($text)
    {
        // Note: en_US is the default language.
        $rake = RakePlus::create(strip_tags($text), 'en_US');

        // 'asc' is optional and is the default sort order
        return $rake->sort('asc')->get();
    }

    /**
     * Get the keywords in the text
     *
     * @param string $text //text to extract keywords from
     *
     * @return array
     */
    public function getKeywords(string $text): array
    {
        $keywords = RakePlus::create(strip_tags($text))->keywords();
        $keywords = $this->removeDuplicatesInArray($keywords);

        return $keywords;
    }

    /**
     * Remove duplicate values in array
     *
     * @param array $arr
     *
     * @return mixed array/string
     */
    public function removeDuplicatesInArray(array $array, ?string $glue = null): mixed
    {
        $result = array_unique($this->removeEmptyArrayElements($array));
        if (!$this->isEmpty($glue)) {
            $result = $this->implodeArray($glue, $result);
        }

        return $result;
    }

    /**
     * Combine the values of an array using a glue
     *
     * @param array $array
     *
     * @return array
     */
    public function implodeArray(string $glue, array $array): array
    {
        return array_reduce($array, function ($carry, $item) use ($glue) {
            return !$carry ? $item : ($carry . $glue . $item);
        });
    }

    /**
     * @return array<string>
     */
    public function randomColor(): array
    {
        $result = ['rgb' => '', 'hex' => ''];
        foreach (['r', 'b', 'g'] as $col) {
            $rand = random_int(0, 255);
            $result['rgb'][$col] = $rand;
            $dechex = dechex($rand);
            if (strlen($dechex) < 2) {
                $dechex = '0' . $dechex;
            }
            $result['hex'] .= "#$dechex";
        }

        return $result;
    }

    /**
     * @param null $folder
     * @param null $filename
     */
    public function deleteFile($folder = null, $filename = null): void
    {
        $disk = 'public';
        Storage::disk($disk)->delete($folder . $filename);
    }

    /**
     * @param null $folder
     * @param null $filename
     */
    public function uploadFile(UploadedFile $uploadedFile, $folder = null, $filename = null): false|string
    {
        $disk = 'public';
        $name = !$this->isEmpty($filename) ? $filename : str_random(25);

        return $uploadedFile->storeAs($folder, $name . '.' . $uploadedFile->getClientOriginalExtension(), $disk);
    }

    /**
     * @param $path
     * @return mixed|string
     */
    public function checkFile($path)
    {
        $new_path = $path;
        $filename = $this->getFilename($path);
        $extension = $this->getFileExtension($path);
        $dir = $this->getFileDirectoryName($path);
        $i = 1;
        while (file_exists($new_path)) {
            // add and combine the filename, iterator, extension
            $new_path = implode('/', [$dir, $filename . '_' . $i . '.' . $extension]);
            $i++;
        }

        return $new_path;
    }

    /**
     * Get file name
     *
     * @return void
     */
    public function getFilename(mixed $targetFile)
    {
        return pathinfo($targetFile, PATHINFO_FILENAME);
    }

    /**
     * @param $targetFile
     * @return array|string|string[]
     */
    public function getFileDirectoryName($targetFile)
    {
        return pathinfo($targetFile, PATHINFO_DIRNAME);
    }

    /**
     * Check if it is a file
     */
    public function isFile(string $file): bool
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        return strlen($ext) > 0 ? true : false;
    }

    /**
     * Check if upload is a video file
     *
     * @param $filename
     * @return bool
     */
    public function isVideo($filename): bool
    {
        if ($this->isEmpty($filename)) {
            return false;
        }
        $EXT_LIST = ['mp4', 'mov', 'mpg', 'mpeg', 'wmv', 'mkv', 'ogg', 'webm'];

        return $this->contains($filename, 'video') || in_array(strtolower($this->getFileExtension($filename)), $EXT_LIST);
    }

    /**
     * @param $filename
     * @return bool
     */
    public function isImage($filename)
    {
        if ($this->isEmpty($filename)) {
            return false;
        }
        $EXT_LIST = ['jpg', 'png', 'bmp', 'jpeg', 'gif'];

        return $this->contains($filename, 'image') || in_array(strtolower($this->getFileExtension($filename)), $EXT_LIST);
    }

    /**
     * @param $filename
     * @return bool
     */
    public function isAudio($filename)
    {
        if ($this->isEmpty($filename)) {
            return false;
        }
        $EXT_LIST = ['ogg', 'mp3', 'wav', 'wmv'];

        return $this->contains($filename, 'audio') || in_array(strtolower($this->getFileExtension($filename)), $EXT_LIST);
    }

    /**
     * @param $filename
     * @return bool
     */
    public function isDocument($filename)
    {
        if ($this->isEmpty($filename)) {
            return false;
        }
        $EXT_LIST = [
            'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'pdf',
        ];

        return in_array(strtolower($this->getFileExtension($filename)), $EXT_LIST);
    }

    /**
     * @param $text
     * @return mixed|string
     */
    public function getFirstWord($text)
    {
        if ($this->isEmpty($text)) {
            return $text;
        }
        $text = $this->stripTags($text);
        $words = str_word_count($text, 1);

        return $words[0];
    }

    /**
     * @param    $value
     * @param string $allowableTags
     * @return mixed|string
     */
    public function stripTags($value, $allowableTags = '')
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->stripTags($allowableTags)->getString();
    }

    /**
     * Get the first url in the text
     */
    public function getFirstUrl(string $text): ?string
    {
        return $this->extractUrls($text)[0] ?? null;
    }

    /**
     * Extract all the urls in the text
     */
    public function extractUrls(string $text): ?string
    {
        $url = [];
        preg_match_all('/(https?|ssh|ftp):\/\/[^\s"]+/', $text, $url);
        return $url[0] ?? null;
    }

    /**
     * @param $string
     * @return bool
     */
    public function isLowerCase($string): bool
    {
        return $string === strtolower($string);
    }

    /**
     * @param $string
     * @return bool
     */
    public function isUpperCase($string)
    {
        return $string === strtoupper($string);
    }

    /**
     * @param $string1
     * @param $string2
     * @return bool
     */
    public function isAnagram($string1, $string2)
    {
        return count_chars($string1, 1) === count_chars($string2, 1);
    }

    /**
     * @param $string
     * @return bool
     */
    public function palindrome($string)
    {
        return strrev($string) === $string;
    }

    /**
     * @param $haystack
     * @param $start
     * @param $end
     * @return string
     */
    public function firstStringBetween($haystack, $start, $end)
    {
        $char = strpos($haystack, $start);
        if (!$char) {
            return '';
        }

        $char += strlen($start);
        $len = strpos($haystack, $end, $char) - $char;

        return substr($haystack, $char, $len);
    }

    /**
     * @param $functions
     * @return mixed
     */
    public function compose($functions)
    {
        return array_reduce(
            $functions,
            function ($carry, $function) {
                return function ($x) use ($carry, $function) {
                    return $function($carry($x));
                };
            },
            function ($x) {
                return $x;
            }
        );
    }

    /**
     * Formats paragraphs around given text for all line breaks
     *  <br /> added for single line return
     *  <p> added for double line return
     *
     * @param string $text Text
     *
     * @return string The text with proper <p> and <br /> tags
     *
     * @link https://book.cakephp.org/3.0/en/views/helpers/text.html#converting-text-into-paragraphs
     */
    public function autoParagraph(string $text): string
    {
        if (trim($text) === '') {
            return $text;
        }
        $text = preg_replace('|<br[^>]*>\s*<br[^>]*>|i', "\n\n", $text . "\n");
        $text = preg_replace("/\n\n+/", "\n\n", str_replace(["\r\n", "\r"], "\n", $text));
        $texts = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $text = '';
        foreach ($texts as $txt) {
            $text .= '<p>' . nl2br(trim($txt, "\n")) . "</p>\n";
        }
        $text = preg_replace('|<p>\s*</p>|', '', $text);

        return $text;
    }

    /**
     * @param $text
     * @return string|null
     */
    public function getFirstSentence($text)
    {
        // Create a new instance
        $Sentence = new Sentence();
        $text = $this->stripTags($text);
        // Split into array of sentences
        $sentences = $Sentence->split($text);
        return isset($sentences[0]) ? $sentences[0] : null;
    }

    /**
     * @param    $value
     * @param string $delimiter
     * @return array|mixed
     */
    public function split($value, $delimiter = ',')
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->split($delimiter);
    }

    /**
     * @param $img1
     * @param $img2
     * @return bool
     */
    public function isSimilarImages($img1, $img2)
    {
        $images = new compareImages();
        return ($images->compare($img1, $img2) === 0) && ($this->isSimilarFiles($img1, $img2));
    }

    /**
     * @param $file1
     * @param $file2
     * @return bool
     */
    public function isSimilarFiles($file1, $file2)
    {
        return md5_file($file1) === md5_file($file2);
    }

    /**
     * @param $srcDir
     * @param $destDir
     * @return array
     */
    public function compareDirectories($srcDir, $destDir)
    {
        $cmp = new compareDirectories(); // Initialize the class set up the source and update(pristine) directories:
        $cmp->set_source($srcDir . '\Source'); // Directory where Source files are
        $cmp->set_update($destDir . '\Update'); // Directory where pristeen files are do the compare:
        $cmp->do_compare(); // Do the compare and get the results:
        $dir['removed'] = $cmp->get_removed(); // Get the results
        $dir['added'] = $cmp->get_added(); // ...
        $dir['changed'] = $cmp->get_changed(); // ...

        return $dir;
    }

    /**
     * @param $text1
     * @param $text2
     * @return bool
     */
    public function isSimilarText($text1, $text2)
    {
        $percent = floatval(0);
        similar_text($text1, $text2, $percent);

        return $percent === 100;
    }

    /**
     * @param $items
     * @param $func
     * @return bool
     */
    public function all($items, $func)
    {
        return count(array_filter($items, $func)) === count($items);
    }

    /**
     * @param $items
     * @param $func
     * @return bool
     */
    public function any($items, $func)
    {
        return count(array_filter($items, $func)) > 0;
    }

    /**
     * @param $items
     * @param $size
     * @return array
     */
    public function chunk($items, $size)
    {
        return array_chunk($items, $size);
    }

    /**
     * @param $items
     * @return array
     */
    public function flatten($items)
    {
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $result = array_merge($result, array_values($item));
            }
        }

        return $result;
    }

    /**
     * @param $items
     * @return array
     */
    public function deepFlatten($items)
    {
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $result = array_merge($result, $this->deepFlatten($item));
            }
        }

        return $result;
    }

    /**
     * @param    $items
     * @param int $n
     * @return array
     */
    public function drop($items, $n = 1)
    {
        return array_slice($items, $n);
    }

    /**
     * @param $items
     * @param $func
     * @return mixed|null
     */
    public function findLast($items, $func)
    {
        $filteredItems = array_filter($items, $func);

        return array_pop($filteredItems);
    }

    /**
     * @param $items
     * @param $func
     * @return int|string|null
     */
    public function findLastIndex($items, $func)
    {
        $keys = array_keys(array_filter($items, $func));

        return array_pop($keys);
    }

    /**
     * @param $items
     * @return false|mixed
     */
    public function head($items)
    {
        return reset($items);
    }

    /**
     * @param $items
     * @return array|mixed
     */
    public function tail($items)
    {
        return count($items) > 1 ? array_slice($items, 1) : $items;
    }

    /**
     * @param $items
     * @return false|mixed
     */
    public function lastArrayItem($items)
    {
        return end($items);
    }

    /**
     * @param $items
     * @param ...$params
     * @return array
     */
    public function pull(&$items, ...$params)
    {
        $items = array_values(array_diff($items, $params));

        return $items;
    }

    /**
     * @param $items
     * @param $key
     * @return array
     */
    public function pluck($items, $key)
    {
        return array_map(function ($item) use ($key) {
            return is_object($item) ? $item->$key : $item[$key];
        }, $items);
    }

    /**
     * @param $items
     * @param $func
     * @return array
     */
    public function reject($items, $func)
    {
        return array_values(array_diff($items, array_filter($items, $func)));
    }

    /**
     * @param $items
     * @param $func
     * @return array
     */
    public function remove($items, $func)
    {
        $filtered = array_filter($items, $func);

        return array_diff_key($items, $filtered);
    }

    /**
     * @param    $items
     * @param int $n
     * @return array
     */
    public function take($items, $n = 1)
    {
        return array_slice($items, 0, $n);
    }

    /**
     * @param $items
     * @param ...$params
     * @return array
     */
    public function without($items, ...$params)
    {
        return array_values(array_diff($items, $params));
    }

    /**
     * @param $items
     * @return bool
     */
    public function hasDuplicates($items)
    {
        return count($items) > count(array_unique($items));
    }

    /**
     * @param $items
     * @param $func
     * @return array
     */
    public function groupBy($items, $func)
    {
        $group = [];
        foreach ($items as $item) {
            if ((!is_string($func) && is_callable($func)) || function_exists($func)) {
                $key = call_user_func($func, $item);
                $group[$key][] = $item;
            } elseif (is_object($item)) {
                $group[$item->{$func}][] = $item;
            } elseif (isset($item[$func])) {
                $group[$item[$func]][] = $item;
            }
        }

        return $group;
    }

    /**
     * Get the average of values in array
     *
     * @param array $items
     */
    public function averageOfArrayValues(array $items): int
    {
        return count($items) === 0 ? 0 : array_sum($items) / count($items);
    }

    /**
     * @param $n
     * @return float|int
     */
    public function factorial($n)
    {
        if ($n <= 1) {
            return 1;
        }

        return $n * $this->factorial($n - 1);
    }

    /**
     * @param $n
     * @return int[]
     */
    public function fibonacci($n)
    {
        $sequence = [0, 1];

        for ($i = 0; $i < $n - 2; $i++) {
            array_push($sequence, array_sum(array_slice($sequence, -2, 2, true)));
        }

        return $sequence;
    }

    /**
     * @param array $numbers
     * @return mixed
     */
    public function lcm(array $numbers): mixed
    {
        $ans = $numbers[0];
        $iMax = count($numbers);
        for ($i = 1; $i < $iMax; $i++) {
            $ans = $numbers[$i] * $ans / $this->gcd($numbers[$i], $ans);
        }

        return $ans;
    }

    /**
     * @param array $numbers
     */
    public function gcd(array $numbers): mixed
    {
        if (count($numbers) > 2) {
            return array_reduce($numbers, 'gcd');
        }

        $r = $numbers[0] % $numbers[1];

        return $r === 0 ? abs($numbers[1]) : $this->gcd($numbers[1], $r);
    }

    /**
     * @param $number
     * @return bool
     */
    public function isPrime($number)
    {
        $boundary = floor(sqrt($number));
        for ($i = 2; $i <= $boundary; $i++) {
            if ($number % $i === 0) {
                return false;
            }
        }

        return $number >= 2;
    }

    /**
     * @param $number
     * @return bool
     */
    public function isEven($number)
    {
        return $number % 2 === 0;
    }

    /**
     * @param $numbers
     * @return float|int|mixed
     */
    public function median($numbers)
    {
        sort($numbers);
        $totalNumbers = count($numbers);
        $mid = floor($totalNumbers / 2);

        return $totalNumbers % 2 === 0 ? ($numbers[$mid - 1] + $numbers[$mid]) / 2 : $numbers[$mid];
    }

    /**
     * @param $date
     * @return string
     */
    public function timeAgo($date)
    {
        $now = new Carbon();
        $dt = new Carbon($date);

        return $dt->diffForHumans($now);
    }

    /**
     * Encode a string making sure that there are no symbols in retval
     */
    public function encodeString(string $value): string
    {
        $value = $this->urlsafe_b64encode($value);

        return $value;
    }

    /**
     * @param $string
     * @return array|mixed|string|string[]
     */
    public function urlsafe_b64encode($string)
    {
        $data = $this->base64Encode($string);
        $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);

        return $data;
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function base64Encode($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->base64Encode()->getString();
    }

    /**
     * Decode a string making sure that there are no symbols in retval
     */
    public function decodeString(string $value): string
    {
        $value = $this->urlsafe_b64decode($value);

        return $value;
    }

    /**
     * @param $string
     * @return mixed|string
     */
    public function urlsafe_b64decode($string)
    {
        $data = str_replace(['-', '_', ''], ['+', '/', '='], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return $this->base64Decode($data);
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function base64Decode($value)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->base64Decode()->getString();
    }

    /**
     * @param $heading
     * @return string
     */
    public function headingDivider($heading)
    {
        return <<< HEAD
        <div class="heading-divider"><span></span><span>$heading</span><span></span></div>
HEAD;
    }

    /**
     * Add active class to navigation links
     */
    public function active(string $routeName, string $className = 'active'): string
    {
        return Route::current()->getName() === $routeName ? $className : '';
    }

    /**
     * Return a formatted Carbon date.
     */
    public function humanize_date(Carbon $date, string $format = 'd F Y, H:i'): string
    {
        return $date->format($format);
    }

    /**
     * @param $url
     * @return bool
     */
    public function isRoute($url)
    {
        if ($this->isUrl($url)) {
            if (\Request::is($url)) {
                // show companies menu or something
                return true;
            }
        } else {
            if (\Route::current()->getName() === 'comp') {
                // We are on a correct route!
                return true;
            }
        }

        return false;
    }

    /**
     * @param    $value
     * @param false $santize
     * @return bool|mixed
     */
    public function isUrl($value, $santize = false)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->isUrl($santize);
    }

    /**
     * @param $file
     * @return BinaryFileResponse
     */
    public function getDownload($file)
    {
        $headers = [
            'Content-Type' => 'application/pdf',
        ];

        return response()->download($file, str_random(15) . '.' . $this->getFileExtension($file), $headers);
    }

    /**
     * @param    $view
     * @param    $data
     * @param null $outputFilename
     * @return Response
     */
    public function demoGeneratePDF($view, $data, $outputFilename = null)
    {
        if ($this->isEmpty($outputFilename)) {
            $outputFilename = str_random(25);
        }
        $pdf = PDF::loadView($view, $data);

        return $pdf->download($outputFilename . '.pdf');
    }

    public function getSessionId(): string
    {
        return Session::getId();
    }

    public function getReferenceCode(): string
    {
        // Call method to generate random string.
        return $this->generate();
    }

    public function generate(int $length = 50): string
    {
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $randomKey = $this->getRandomInteger(0, $this->alphabetLength);
            $token .= $this->alphabet[$randomKey];
        }

        return $token;
    }

    protected function getRandomInteger(int $min, int $max): int
    {
        $range = $max - $min;

        if ($range < 0) {
            // Not so random...
            return $min;
        }

        $log = log($range, 2);

        // Length in bytes.
        $bytes = (int)($log / 8) + 1;

        // Length in bits.
        $bits = (int)$log + 1;

        // Set all lower bits to 1.
        $filter = (1 << $bits) - 1;
        $strong_result = true;
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes, $strong_result)));

            // Discard irrelevant bits.
            $rnd &= $filter;
        } while ($rnd >= $range);

        return $min + $rnd;
    }

    /**
     * Convert $_FILES to array
     *
     * @param array $filePost
     *
     * @return array
     */
    public function reArrayFiles(array &$filePost): array
    {
        $file_ary = [];
        $multiple = is_array($filePost['name']);
        $file_count = $multiple ? count($filePost['name']) : 1;
        $file_keys = array_keys($filePost);
        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $multiple ? $filePost[$key][$i] : $filePost[$key];
            }
        }

        return $file_ary;
    }

    /**
     * @param array $array
     */
    public function convertToObject(array $array): mixed
    {
        return json_decode(json_encode($array));
    }

    /**
     * @param $title
     * @param $description
     * @param $type
     */
    public function seoIndex($title, $description, $type)
    {
        SEO::setTitle($title);
        SEO::setDescription($description);
        SEO::opengraph()->setUrl('www.pharmacytherapon.com');
        SEO::setCanonical(url()->current());
        SEO::opengraph()->addProperty('type', $type);
        SEO::twitter()->setSite('@PharmaTherapon');
    }

    /**
     * @param    $title
     * @param    $description
     * @param    $type
     * @param array $property
     */
    public function seoPage($title, $description, $type, array $property)
    {
        $property = collect($property);

        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::addMeta('article:published_time', $property->created_at->toW3CString(), 'property');
        SEOMeta::addMeta('article:section', $property->category, 'property');
        SEOMeta::addKeyword($property->keywords);

        OpenGraph::setDescription($description);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl(url()->current());
        OpenGraph::addProperty('type', $type);
        OpenGraph::addProperty('locale', 'pt-br');
        OpenGraph::addProperty('locale:alternate', ['pt-pt', 'en-us']);

        OpenGraph::addImage(url($property->cover_image));

        if (strtolower($type) === 'video') {
            // og:video
            OpenGraph::addVideo(url($property->path), [
                'secure_url' => secure_url($property->path),
                'type' => $property->mimeType,
                'width' => 400,
                'height' => 300,
            ]);
        } elseif (strtolower($type) === 'audio') {
            // og:audio
            OpenGraph::addAudio(url($property->path), [
                'secure_url' => secure_url($property->path),
                'type' => $property->mimeType,
            ]);
        } elseif (strtolower($type) === 'article') {
            // article
            OpenGraph::setTitle($title)
                ->setDescription($description)
                ->setType($type)
                ->setArticle([
                    'published_time' => $property->created_at,
                    'modified_time' => $property->updated_at,
                    'author' => $property->author,
                    'section' => $property->category,
                    'tag' => $property->tag,
                ]);
        } elseif (strtolower($type) === 'download') {
            // book
            OpenGraph::setTitle($title)
                ->setDescription($description)
                ->setType($type)
                ->setBook([
                    'author' => $property->author,
                    'isbn' => $property->isbn,
                    'release_date' => $property->release_date,
                    'tag' => $property->tag,
                ]);
        } elseif (strtolower($type) === 'profile') {
            // book
            OpenGraph::setTitle($title)
                ->setDescription($description)
                ->setType($type)
                ->setBook([
                    'first_name' => $property->firstname,
                    'last_name' => $property->lastname,
                ]);
        }
    }

    public function seoView(): void
    {
        echo SEO::generate(true);
    }

    /**
     * Form Helper
     */

    /**
     * Pluralizes a word if quantity is not one.
     *
     * @param int $quantity Number of items
     * @param string $singular Singular form of word
     * @param string $plural Plural form of word; function will attempt to deduce plural form from singular if not provided
     *
     * @return string Pluralized word if quantity is not one, otherwise singular
     */
    public function pluralize(int $quantity, string $singular, ?string $plural = null): string
    {
        if ($quantity === 1 || !strlen($singular)) {
            return $singular;
        }
        if ($plural !== null) {
            return $plural;
        }

        $lastLetter = strtolower($singular[strlen($singular) - 1]);
        switch ($lastLetter) {
            case 'y':
                $secondToLastLetter = strtolower($singular[strlen($singular) - 2]);
                if ($secondToLastLetter === 'a') {
                    return $singular . 's';
                }

                return substr($singular, 0, -1) . 'ies';

            case 's':
                return $singular . 'es';
            default:
                return $singular . 's';
        }
    }

    /**
     * @param    $class
     * @param    $method
     * @param null $arg
     * @return mixed
     */
    public function callClassMethod($class, $method, $arg = null)
    {
        $obj = new $class();

        return $obj->$method($arg);
    }

    /**
     * @param    $obj
     * @param    $method
     * @param null $arg
     * @return mixed
     */
    public function callMethod($obj, $method, $arg = null)
    {
        return $obj->$method($arg);
    }

    /**
     * End Form Helper
     */

    /**
     * @param array $array
     */
    public function toObject(array $array): object
    {
        return (object)$array;
    }

    /**
     * Builds a file path with the appropriate directory separator.
     *
     * @param string $segments,... unlimited number of path segments
     *
     * @return string Path
     */
    public function fileBuildPath(string ...$segments): string
    {
        return implode(DIRECTORY_SEPARATOR, $segments);
    }

    public function pageJsonData(): string
    {
        $jobModalOpen = false;
        if (session('job_validation_fails')) {
            $jobModalOpen = true;
        }

        $data = [
            'home_url' => route('home'),
            'asset_url' => asset('assets'),
            'csrf_token' => csrf_token(),
            'jobModalOpen' => $jobModalOpen,
            'flag_job_validation_fails' => session('flag_job_validation_fails'),
            'share_job_validation_fails' => session('share_job_validation_fails'),
            //'my_dashboard' => route('my_dashboard'),
        ];

        $routeLists = Route::getRoutes();

        $routes = [];
        foreach ($routeLists as $route) {
            $routes[$route->getName()] = $data['home_url'] . '/' . $route->uri;
        }
        $data['routes'] = $routes;

        return json_encode($data);
    }

    /**
     * @param string $title
     * @param    $model
     * @return string
     */
    public function uniqueSlug($title = '', $model = 'Job', $col = 'slug')
    {
        $slug = str_slug($title);
        if ($slug === '') {
            $string = mb_strtolower($title, 'UTF-8');
            $string = preg_replace("/[\/\.]/", ' ', $string);
            $string = preg_replace("/[\s-]+/", ' ', $string);
            $slug = preg_replace("/[\s_]/", '-', $string);
        }

        //get unique slug...
        $nSlug = $slug;
        $i = 0;

        $model = str_replace(' ', '', "\App\ " . $model);
        while (($model::where($col, '=', $nSlug)->count()) > 0) {
            $i++;
            $nSlug = $slug . '-' . $i;
        }
        if ($i > 0) {
            $newSlug = substr($nSlug, 0, strlen($slug)) . '-' . $i;
        } else {
            $newSlug = $slug;
        }

        return $newSlug;
    }

    /**
     * @param    $errors
     * @param string $field
     * @return string
     */
    public function formError($errors, $field = '')
    {
        return $errors->has($field) ? '<span class="invalid-feedback" role="alert"><strong>' . $errors->first($field) . '</strong></span>' : '';
    }

    /**
     * @param $value
     * @param $numberOfCharacters
     * @return mixed|string
     */
    public function first($value, $numberOfCharacters)
    {
        if ($this->isEmpty($value)) {
            return $value;
        }
        $s = new Stringizer($value);

        return $s->first($numberOfCharacters)->getString();
    }

    /**
     * @param    $errors
     * @param string $field
     * @return string
     */
    public function formInvalidClass($errors, $field = '')
    {
        return $errors->has($field) ? ' is-invalid' : '';
    }

    public function getAmount(int $amount = 0, $currency = null): string
    {
        $currency_position = $this->getOption('currency_position');

        if (!$currency) {
            $currency = $this->getOption('currency_sign');
        }

        $currency_sign = $this->get_currency_symbol($currency);
        $get_price = $this->getAmountRaw($amount);

        if ($currency_position === 'right') {
            $show_price = $get_price . $currency_sign;
        } else {
            $show_price = $currency_sign . $get_price;
        }

        return $show_price;
    }

    public function getOption(string $option_key = '', $default = false): string
    {
        $options = config('options');
        if (isset($options[$option_key])) {
            return $options[$option_key];
        }

        return $default;
    }

    public function getAmountRaw(int $amount = 0): int|string
    {
        $get_price = '0.00';
        $none_decimal_currencies = $this->getZeroDecimalCurrency();

        if (in_array($this->getOption('currency_sign'), $none_decimal_currencies)) {
            $get_price = (int)$amount;
        } else {
            if ($amount > 0) {
                $get_price = number_format($amount, 2);
            }
        }

        return $get_price;
    }

    /**
     * @return array<string>
     */
    public function getZeroDecimalCurrency(): array
    {
        return [
            'BIF',
            'MGA',
            'CLP',
            'PYG',
            'DJF',
            'RWF',
            'GNF',
            'UGX',
            'JPY',
            'VND',
            'VUV',
            'KMF',
            'XAF',
            'KRW',
            'XOF',
            'XPF',
        ];
    }

    public function getStripeAmount(int $amount = 0, string $type = 'to_cents'): mixed
    {
        if (!$amount) {
            return $amount;
        }

        $non_decimal_currency = $this->getZeroDecimalCurrency();

        if (in_array($this->getOption('currency_sign'), $non_decimal_currency)) {
            return $amount;
        }

        if ($type === 'to_cents') {
            return $amount * 100;
        }

        return $amount / 100;
    }

    /**
     * @return array
     *
     * Get currencies
     */
    public function getCurrencies(): array
    {
        return [
            'USD' => 'United States dollar',
            'EUR' => 'Euro',
            'AED' => 'United Arab Emirates dirham',
            'AFN' => 'Afghan afghani',
            'ALL' => 'Albanian lek',
            'AMD' => 'Armenian dram',
            'ANG' => 'Netherlands Antillean guilder',
            'AOA' => 'Angolan kwanza',
            'ARS' => 'Argentine peso',
            'AUD' => 'Australian dollar',
            'AWG' => 'Aruban florin',
            'AZN' => 'Azerbaijani manat',
            'BAM' => 'Bosnia and Herzegovina convertible mark',
            'BBD' => 'Barbadian dollar',
            'BDT' => 'Bangladeshi taka',
            'BGN' => 'Bulgarian lev',
            'BHD' => 'Bahraini dinar',
            'BIF' => 'Burundian franc',
            'BMD' => 'Bermudian dollar',
            'BND' => 'Brunei dollar',
            'BOB' => 'Bolivian boliviano',
            'BRL' => 'Brazilian real',
            'BSD' => 'Bahamian dollar',
            'BTC' => 'Bitcoin',
            'BTN' => 'Bhutanese ngultrum',
            'BWP' => 'Botswana pula',
            'BYR' => 'Belarusian ruble',
            'BZD' => 'Belize dollar',
            'CAD' => 'Canadian dollar',
            'CDF' => 'Congolese franc',
            'CHF' => 'Swiss franc',
            'CLP' => 'Chilean peso',
            'CNY' => 'Chinese yuan',
            'COP' => 'Colombian peso',
            'CRC' => 'Costa Rican colon',
            'CUC' => 'Cuban convertible peso',
            'CUP' => 'Cuban peso',
            'CVE' => 'Cape Verdean escudo',
            'CZK' => 'Czech koruna',
            'DJF' => 'Djiboutian franc',
            'DKK' => 'Danish krone',
            'DOP' => 'Dominican peso',
            'DZD' => 'Algerian dinar',
            'EGP' => 'Egyptian pound',
            'ERN' => 'Eritrean nakfa',
            'ETB' => 'Ethiopian birr',
            'FJD' => 'Fijian dollar',
            'FKP' => 'Falkland Islands pound',
            'GBP' => 'Pound sterling',
            'GEL' => 'Georgian lari',
            'GGP' => 'Guernsey pound',
            'GHS' => 'Ghana cedi',
            'GIP' => 'Gibraltar pound',
            'GMD' => 'Gambian dalasi',
            'GNF' => 'Guinean franc',
            'GTQ' => 'Guatemalan quetzal',
            'GYD' => 'Guyanese dollar',
            'HKD' => 'Hong Kong dollar',
            'HNL' => 'Honduran lempira',
            'HRK' => 'Croatian kuna',
            'HTG' => 'Haitian gourde',
            'HUF' => 'Hungarian forint',
            'IDR' => 'Indonesian rupiah',
            'ILS' => 'Israeli new shekel',
            'IMP' => 'Manx pound',
            'INR' => 'Indian rupee',
            'IQD' => 'Iraqi dinar',
            'IRR' => 'Iranian rial',
            'ISK' => 'Icelandic krona',
            'JEP' => 'Jersey pound',
            'JMD' => 'Jamaican dollar',
            'JOD' => 'Jordanian dinar',
            'JPY' => 'Japanese yen',
            'KES' => 'Kenyan shilling',
            'KGS' => 'Kyrgyzstani som',
            'KHR' => 'Cambodian riel',
            'KMF' => 'Comorian franc',
            'KPW' => 'North Korean won',
            'KRW' => 'South Korean won',
            'KWD' => 'Kuwaiti dinar',
            'KYD' => 'Cayman Islands dollar',
            'KZT' => 'Kazakhstani tenge',
            'LAK' => 'Lao kip',
            'LBP' => 'Lebanese pound',
            'LKR' => 'Sri Lankan rupee',
            'LRD' => 'Liberian dollar',
            'LSL' => 'Lesotho loti',
            'LYD' => 'Libyan dinar',
            'MAD' => 'Moroccan dirham',
            'MDL' => 'Moldovan leu',
            'MGA' => 'Malagasy ariary',
            'MKD' => 'Macedonian denar',
            'MMK' => 'Burmese kyat',
            'MNT' => 'Mongolian t&ouml;gr&ouml;g',
            'MOP' => 'Macanese pataca',
            'MRO' => 'Mauritanian ouguiya',
            'MUR' => 'Mauritian rupee',
            'MVR' => 'Maldivian rufiyaa',
            'MWK' => 'Malawian kwacha',
            'MXN' => 'Mexican peso',
            'MYR' => 'Malaysian ringgit',
            'MZN' => 'Mozambican metical',
            'NAD' => 'Namibian dollar',
            'NGN' => 'Nigerian naira',
            'NIO' => 'Nicaraguan c&oacute;rdoba',
            'NOK' => 'Norwegian krone',
            'NPR' => 'Nepalese rupee',
            'NZD' => 'New Zealand dollar',
            'OMR' => 'Omani rial',
            'PAB' => 'Panamanian balboa',
            'PEN' => 'Peruvian nuevo sol',
            'PGK' => 'Papua New Guinean kina',
            'PHP' => 'Philippine peso',
            'PKR' => 'Pakistani rupee',
            'PLN' => 'Polish z&#x142;oty',
            'PRB' => 'Transnistrian ruble',
            'PYG' => 'Paraguayan guaran&iacute;',
            'QAR' => 'Qatari riyal',
            'RON' => 'Romanian leu',
            'RSD' => 'Serbian dinar',
            'RUB' => 'Russian ruble',
            'RWF' => 'Rwandan franc',
            'SAR' => 'Saudi riyal',
            'SBD' => 'Solomon Islands dollar',
            'SCR' => 'Seychellois rupee',
            'SDG' => 'Sudanese pound',
            'SEK' => 'Swedish krona',
            'SGD' => 'Singapore dollar',
            'SHP' => 'Saint Helena pound',
            'SLL' => 'Sierra Leonean leone',
            'SOS' => 'Somali shilling',
            'SRD' => 'Surinamese dollar',
            'SSP' => 'South Sudanese pound',
            'STD' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra',
            'SYP' => 'Syrian pound',
            'SZL' => 'Swazi lilangeni',
            'THB' => 'Thai baht',
            'TJS' => 'Tajikistani somoni',
            'TMT' => 'Turkmenistan manat',
            'TND' => 'Tunisian dinar',
            'TOP' => 'Tongan pa&#x2bb;anga',
            'TRY' => 'Turkish lira',
            'TTD' => 'Trinidad and Tobago dollar',
            'TWD' => 'New Taiwan dollar',
            'TZS' => 'Tanzanian shilling',
            'UAH' => 'Ukrainian hryvnia',
            'UGX' => 'Ugandan shilling',
            'UYU' => 'Uruguayan peso',
            'UZS' => 'Uzbekistani som',
            'VEF' => 'Venezuelan bol&iacute;var',
            'VND' => 'Vietnamese &#x111;&#x1ed3;ng',
            'VUV' => 'Vanuatu vatu',
            'WST' => 'Samoan t&#x101;l&#x101;',
            'XAF' => 'Central African CFA franc',
            'XCD' => 'East Caribbean dollar',
            'XOF' => 'West African CFA franc',
            'XPF' => 'CFP franc',
            'YER' => 'Yemeni rial',
            'ZAR' => 'South African rand',
            'ZMW' => 'Zambian kwacha',
        ];
    }

    /**
     * Get Currency symbol.
     *
     * @param string $currency (default: '')
     */
    public function getCurrencySymbol(string $currency = ''): string
    {
        if (!$currency) {
            $currency = 'USD';
        }

        $symbols = [
            'AED' => '&#x62f;.&#x625;',
            'AFN' => '&#x60b;',
            'ALL' => 'L',
            'AMD' => 'AMD',
            'ANG' => '&fnof;',
            'AOA' => 'Kz',
            'ARS' => '&#36;',
            'AUD' => '&#36;',
            'AWG' => '&fnof;',
            'AZN' => 'AZN',
            'BAM' => 'KM',
            'BBD' => '&#36;',
            'BDT' => '&#2547;&nbsp;',
            'BGN' => '&#1083;&#1074;.',
            'BHD' => '.&#x62f;.&#x628;',
            'BIF' => 'Fr',
            'BMD' => '&#36;',
            'BND' => '&#36;',
            'BOB' => 'Bs.',
            'BRL' => '&#82;&#36;',
            'BSD' => '&#36;',
            'BTC' => '&#3647;',
            'BTN' => 'Nu.',
            'BWP' => 'P',
            'BYR' => 'Br',
            'BZD' => '&#36;',
            'CAD' => '&#36;',
            'CDF' => 'Fr',
            'CHF' => '&#67;&#72;&#70;',
            'CLP' => '&#36;',
            'CNY' => '&yen;',
            'COP' => '&#36;',
            'CRC' => '&#x20a1;',
            'CUC' => '&#36;',
            'CUP' => '&#36;',
            'CVE' => '&#36;',
            'CZK' => '&#75;&#269;',
            'DJF' => 'Fr',
            'DKK' => 'DKK',
            'DOP' => 'RD&#36;',
            'DZD' => '&#x62f;.&#x62c;',
            'EGP' => 'EGP',
            'ERN' => 'Nfk',
            'ETB' => 'Br',
            'EUR' => '&euro;',
            'FJD' => '&#36;',
            'FKP' => '&pound;',
            'GBP' => '&pound;',
            'GEL' => '&#x10da;',
            'GGP' => '&pound;',
            'GHS' => '&#x20b5;',
            'GIP' => '&pound;',
            'GMD' => 'D',
            'GNF' => 'Fr',
            'GTQ' => 'Q',
            'GYD' => '&#36;',
            'HKD' => '&#36;',
            'HNL' => 'L',
            'HRK' => 'Kn',
            'HTG' => 'G',
            'HUF' => '&#70;&#116;',
            'IDR' => 'Rp',
            'ILS' => '&#8362;',
            'IMP' => '&pound;',
            'INR' => '&#8377;',
            'IQD' => '&#x639;.&#x62f;',
            'IRR' => '&#xfdfc;',
            'ISK' => 'kr.',
            'JEP' => '&pound;',
            'JMD' => '&#36;',
            'JOD' => '&#x62f;.&#x627;',
            'JPY' => '&yen;',
            'KES' => 'KSh',
            'KGS' => '&#x441;&#x43e;&#x43c;',
            'KHR' => '&#x17db;',
            'KMF' => 'Fr',
            'KPW' => '&#x20a9;',
            'KRW' => '&#8361;',
            'KWD' => '&#x62f;.&#x643;',
            'KYD' => '&#36;',
            'KZT' => 'KZT',
            'LAK' => '&#8365;',
            'LBP' => '&#x644;.&#x644;',
            'LKR' => '&#xdbb;&#xdd4;',
            'LRD' => '&#36;',
            'LSL' => 'L',
            'LYD' => '&#x644;.&#x62f;',
            'MAD' => '&#x62f;. &#x645;.',
            'MDL' => 'L',
            'MGA' => 'Ar',
            'MKD' => '&#x434;&#x435;&#x43d;',
            'MMK' => 'Ks',
            'MNT' => '&#x20ae;',
            'MOP' => 'P',
            'MRO' => 'UM',
            'MUR' => '&#x20a8;',
            'MVR' => '.&#x783;',
            'MWK' => 'MK',
            'MXN' => '&#36;',
            'MYR' => '&#82;&#77;',
            'MZN' => 'MT',
            'NAD' => '&#36;',
            'NGN' => '&#8358;',
            'NIO' => 'C&#36;',
            'NOK' => '&#107;&#114;',
            'NPR' => '&#8360;',
            'NZD' => '&#36;',
            'OMR' => '&#x631;.&#x639;.',
            'PAB' => 'B/.',
            'PEN' => 'S/.',
            'PGK' => 'K',
            'PHP' => '&#8369;',
            'PKR' => '&#8360;',
            'PLN' => '&#122;&#322;',
            'PRB' => '&#x440;.',
            'PYG' => '&#8370;',
            'QAR' => '&#x631;.&#x642;',
            'RMB' => '&yen;',
            'RON' => 'lei',
            'RSD' => '&#x434;&#x438;&#x43d;.',
            'RUB' => '&#8381;',
            'RWF' => 'Fr',
            'SAR' => '&#x631;.&#x633;',
            'SBD' => '&#36;',
            'SCR' => '&#x20a8;',
            'SDG' => '&#x62c;.&#x633;.',
            'SEK' => '&#107;&#114;',
            'SGD' => '&#36;',
            'SHP' => '&pound;',
            'SLL' => 'Le',
            'SOS' => 'Sh',
            'SRD' => '&#36;',
            'SSP' => '&pound;',
            'STD' => 'Db',
            'SYP' => '&#x644;.&#x633;',
            'SZL' => 'L',
            'THB' => '&#3647;',
            'TJS' => '&#x405;&#x41c;',
            'TMT' => 'm',
            'TND' => '&#x62f;.&#x62a;',
            'TOP' => 'T&#36;',
            'TRY' => '&#8378;',
            'TTD' => '&#36;',
            'TWD' => '&#78;&#84;&#36;',
            'TZS' => 'Sh',
            'UAH' => '&#8372;',
            'UGX' => 'UGX',
            'USD' => '&#36;',
            'UYU' => '&#36;',
            'UZS' => 'UZS',
            'VEF' => 'Bs F',
            'VND' => '&#8363;',
            'VUV' => 'Vt',
            'WST' => 'T',
            'XAF' => 'Fr',
            'XCD' => '&#36;',
            'XOF' => 'Fr',
            'XPF' => 'Fr',
            'YER' => '&#xfdfc;',
            'ZAR' => '&#82;',
            'ZMW' => 'ZK',
        ];

        return isset($symbols[$currency]) ? $symbols[$currency] : '';
    }

    /**
     * @param    $checked
     * @param bool $current
     * @param bool $echo
     * @return string
     */
    public function checked($checked, $current = true, $echo = true)
    {
        return $this->checkedSelectedHelper($checked, $current, $echo, 'checked');
    }

    /**
     * @param $helper
     * @param $current
     * @param $echo
     * @param $type
     * @return string
     */
    public function checkedSelectedHelper($helper, $current, $echo, $type)
    {
        if ((string)$helper === (string)$current) {
            $result = " $type='$type'";
        } else {
            $result = '';
        }

        if ($echo) {
            echo $result;
        }

        return $result;
    }

    /**
     * @param    $selected
     * @param bool $current
     * @param bool $echo
     * @return string
     */
    public function selected($selected, $current = true, $echo = true)
    {
        return $this->checkedSelectedHelper($selected, $current, $echo, 'selected');
    }

    /**
     * @param null $code
     *
     * @return array|mixed
     *
     * Get Company Size
     */
    public function companySize($code = null): mixed
    {
        $size = [
            'A' => __('app.1-10'),
            'B' => __('app.11-50'),
            'C' => __('app.51-200'),
            'D' => __('app.201-500'),
            'E' => __('app.501-1000'),
            'F' => __('app.1001-5000'),
            'G' => __('app.5001-10,000'),
            'H' => __('app.10,001+'),
        ];

        if ($code && isset($size[$code])) {
            return $size[$code];
        }

        return $size;
    }

    /**
     * @param string|null $text
     */
    public function limitWords(string $text = null, int $limit = 30): string
    {
        $text = strip_tags($text);
        if (str_word_count($text, 0) > $limit) {
            $words = str_word_count($text, 2);
            $pos = array_keys($words);
            $text = substr($text, 0, $pos[$limit]) . '...';
        }

        return $text;
    }

    public function get_text_tpl(string $text = ''): mixed
    {
        $tpl = ['[year]', '[copyright_sign]', '[site_name]'];
        $variable = [date('Y'), '&copy;', getOption('site_name')];

        return str_replace($tpl, $variable, $text);
    }

    /**
     * Get files from folder and upload
     */
    public function getFilesFromDirectory(string $directory): Collection
    {
        return collect(File::Files($directory))
            ->sortBy(function ($file) {
                return $file->getMTime();
            });
    }

    /**
     * Get the read duration of string
     */
    public function readDuration(string $text): int
    {
        $totalWords = str_word_count(implode(' ', explode(' ', $text)));
        $minutesToRead = round($totalWords / 200);

        return (int)max(1, $minutesToRead);
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable($model): string
    {
        if (!isset($model->table)) {
            return str_replace(
                '\\',
                '',
                Str::snake(Str::plural(class_basename($model)))
            );
        }

        return $model->table;
    }

    /**
     * @param $sizeInBytes
     * @return string
     */
    public function getHumanReadableSize($sizeInBytes): string
    {
        if ($sizeInBytes >= 1073741824) {
            return number_format($sizeInBytes / 1073741824, 2) . ' GB';
        }

        if ($sizeInBytes >= 1048576) {
            return number_format($sizeInBytes / 1048576, 2) . ' MB';
        }

        if ($sizeInBytes >= 1024) {
            return number_format($sizeInBytes / 1024, 2) . ' KB';
        }

        if ($sizeInBytes > 1) {
            return $sizeInBytes . ' bytes';
        }

        if ($sizeInBytes === 1) {
            return '1 byte';
        }
        return '0 bytes';
    }
}
