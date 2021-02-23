<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

use GusApi\GusApi;
use GusApi\RegonConstantsInterface;
use GusApi\Exception\InvalidUserKeyException;
use GusApi\ReportTypes;
use GusApi\ReportTypeMapper;

class Utils
{
    const GUS_REGON_API_RESULT_BAD_KEY = 1;
    const GUS_REGON_API_RESULT_NO_DATA = 2;

    const GUS_REGON_API_SEARCH_TYPE_TEN = 1;
    const GUS_REGON_API_SEARCH_TYPE_REGON = 2;
    const GUS_REGON_API_SEARCH_TYPE_RBE = 3;

    public static function filterIntegers(array $params)
    {
        return array_filter($params, function ($value) {
            $string = strval($value);
            if ($string[0] == '-') {
                $string = ltrim($string, '-');
            }
            return ctype_digit($string);
        });
    }

    public static function filterArrayByKeys(array $array, array $keys, $reverse = false)
    {
        $result = array();
        $keys = array_flip($keys);
        array_walk($array, function ($item, $key) use ($reverse, $keys, &$result) {
            if ($reverse) {
                if (!isset($keys[$key])) {
                    $result[$key] = $item;
                }
            } elseif (isset($keys[$key])) {
                $result[$key] = $item;
            }
        });
        return $result;
    }

    public static function array_column(array $array, $column_key, $index_key = null)
    {
        if (!is_array($array) || empty($column_key)) {
            return $array;
        }
        $result = array();
        foreach ($array as $idx => $item) {
            if (isset($index_key)) {
                $result[$item[$index_key]] = $item[$column_key];
            } else {
                $result[$idx] = $item[$column_key];
            }
        }
        return $result;
    }

    public static function array_keys_add_prefix(array $array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $result = array();

        function addkeyprefix($k)
        {
            return 'old_'.$k;
        }

        $result = array_combine(array_map('addkeyprefix', array_keys($array)), $array);
        return $result;
    }

    // taken from RoundCube
    /**
     * Generate a random string
     *
     * @param int  $length String length
     * @param bool $raw    Return RAW data instead of ascii
     *
     * @return string The generated random string
     */
    public static function randomBytes($length, $raw = false)
    {
        $hextab  = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $tabsize = strlen($hextab);

        // Use PHP7 true random generator
        if ($raw && function_exists('random_bytes')) {
            return random_bytes($length);
        }

        if (!$raw && function_exists('random_int')) {
            $result = '';
            while ($length-- > 0) {
                $result .= $hextab[random_int(0, $tabsize - 1)];
            }

            return $result;
        }

        $random = openssl_random_pseudo_bytes($length);

        if ($random === false && $length > 0) {
            throw new Exception("Failed to get random bytes");
        }

        if (!$raw) {
            for ($x = 0; $x < $length; $x++) {
                $random[$x] = $hextab[ord($random[$x]) % $tabsize];
            }
        }

        return $random;
    }

    public static function isAllowedIP($ip, $allow_from)
    {
        if (empty($allow_from)) {
            return true;
        }

        $allowedlist = explode(',', $allow_from);

        foreach ($allowedlist as $value) {
            $mask = '';

            if (strpos($value, '/') === false) {
                $net = $value;
            } else {
                list ($net, $mask) = explode('/', $value);
            }

            $net = trim($net);
            $mask = trim($mask);

            if ($mask == '') {
                $mask = '255.255.255.255';
            } elseif (is_numeric($mask)) {
                $mask = prefix2mask($mask);
            }

            if (isipinstrict($ip, $net, $mask)) {
                return true;
            }
        }

        return false;
    }

    public static function docEntityCount($entities)
    {
        return substr_count(sprintf("%b", $entities), '1');
    }

    public static function triggerError($error_msg, $error_type = E_USER_NOTICE, $context = 1)
    {
        $stack = debug_backtrace();
        for ($i = 0; $i < $context; $i++) {
            if (false === ($frame = next($stack))) {
                break;
            }
            $error_msg .= ", from " . $frame['function'] . ':' . $frame['file'] . ' line ' . $frame['line'];
        }
        return trigger_error($error_msg, $error_type);
    }

    public static function LoadMarkdownDocumentation($variable_name = null)
    {
        $markdown_documentation_file = ConfigHelper::getConfig(
            'phpui.markdown_documentation_file',
            SYS_DIR . DIRECTORY_SEPARATOR . 'doc' . DIRECTORY_SEPARATOR . 'Zmienne-konfiguracyjne-LMS-Plus.md'
        );
        if (!file_exists($markdown_documentation_file)) {
            return null;
        }

        if (isset($variable_name)) {
            $content = file_get_contents($markdown_documentation_file);
            if (($startpos = strpos($content, '## ' . $variable_name)) === false) {
                return null;
            }
            $endpos = strpos($content, '## ', $startpos + 1);
            if ($endpos === false) {
                $chunk = substr($content, $startpos);
            } else {
                $chunk = substr($content, $startpos, $endpos - $startpos);
            }
            if (($endpos = strpos($chunk, '***')) !== false) {
                $chunk = substr($chunk, 0, $endpos);
            }
            $lines = explode("\n", $chunk);
            array_shift($lines);
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            unset($line);
            return implode("\n", $lines);
        }

        $result = array();

        if (empty($markdown_documentation_file) || !file_exists($markdown_documentation_file)) {
            return $result;
        }

        $content = file($markdown_documentation_file);
        if (empty($content)) {
            return $result;
        }

        $variable = null;
        $buffer = '';
        foreach ($content as $line) {
            if (preg_match('/^##\s+(?<variable>.+)\r?\n/', $line, $m)) {
                if ($variable && $buffer) {
                    list ($section, $var) = explode('.', $variable);
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$var] = $buffer;
                }
                $variable = $m['variable'];
                $buffer = '';
            } elseif (preg_match('/^\*\*\*/', $line)) {
                if ($variable && $buffer) {
                    list ($section, $var) = explode('.', $variable);
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$var] = $buffer;
                }
                $variable = null;
                $buffer = '';
            } elseif ($variable) {
                $buffer .= $line;
            }
        }
        if ($variable && $buffer) {
            list ($section, $var) = explode('.', $variable);
            if (!isset($result[$section])) {
                $result[$section] = array();
            }
            $result[$section][$var] = $buffer;
        }

        return $result;
    }

    public static function MarkdownToHtml($markdown)
    {
        static $markdown_parser = null;
        if (!isset($markdown_parser)) {
            $markdown_parser = new Parsedown();
        }
        return $markdown_parser->Text($markdown);
    }

    public static function getDefaultCustomerConsents()
    {
        global $CCONSENTS;

        $result = array();

        $value = ConfigHelper::getConfig('phpui.default_customer_consents', 'data_processing,transfer_form', true);
        if (!empty($value)) {
            $values = array_flip(preg_split('/[\s\.,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY));
            foreach ($CCONSENTS as $consent_id => $consent) {
                if (isset($values[$consent['name']])) {
                    $result[$consent_id] = $consent_id;
                }
            }
        }

        return $result;
    }

    public static function parseCssProperties($text)
    {
        $result = array();
        $text = preg_replace('/\s/', '', $text);
        $properties = explode(';', $text);
        if (!empty($properties)) {
            foreach ($properties as $property) {
                list ($name, $value) = explode(':', $property);
                $result[$name] = $value;
            }
        }
        return $result;
    }

    public static function findNextBusinessDay($date = null)
    {
        $holidaysByYear = array();

        list ($year, $month, $day, $weekday) = explode('/', date('Y/m/j/N', $date ? $date : time()));
        $date = mktime(0, 0, 0, $month, $day, $year);

        while (true) {
            if (!isset($holidaysByYear[$year])) {
                $holidaysByYear[$year] = getHolidays($year);
            }
            if ($weekday < 6 && !isset($holidaysByYear[$year][$date])) {
                return $date;
            }
            $date = strtotime('+1 day', $date);
            list ($year, $weekday) = explode('/', date('Y/N', $date));
        }
    }

    public static function validateVat($trader_country, $trader_id, $requester_country, $requester_id)
    {
        static $vies = null;

        $trader_id = strpos($trader_id, $trader_country) == 0
            ? preg_replace('/^' . $trader_country . '/', '', $trader_id) : $trader_id;
        $requester_id = strpos($requester_id, $requester_country) == 0
            ? preg_replace('/^' . $requester_country . '/', '', $requester_id) : $requester_id;

        if (!isset($vies)) {
            $vies = new \DragonBe\Vies\Vies();
            if (!$vies->getHeartBeat()->isAlive()) {
                throw new Exception('VIES service is not available at the moment, please try again later.');
            }
        }

        $vatResult = $vies->validateVat(
            $trader_country,    // Trader country code
            $trader_id,         // Trader VAT ID
            $requester_country, // Requester country code
            $requester_id       // Requester VAT ID
        );

        return $vatResult->isValid();
    }

    public static function validatePlVat($trader_country, $trader_id)
    {
        static $curl = null;

        if (!isset($curl)) {
            if (!function_exists('curl_init')) {
                throw new Exception(trans('Curl extension not loaded!'));
            }
            $curl = curl_init();
        }

        $trader_id = strpos($trader_id, $trader_country) == 0
            ? preg_replace('/^' . $trader_country . '/', '', $trader_id) : $trader_id;

        curl_setopt($curl, CURLOPT_URL, 'https://wl-api.mf.gov.pl/api/search/nip/' . $trader_id . '?date=' . date('Y-m-d'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));

        $result = curl_exec($curl);
        if (curl_error($curl)) {
            throw new Exception('Communication error: ' . curl_error($curl));
        }

/*
        $info = curl_getinfo($curl);
        if ($info['http_code'] != '200') {
            throw new Exception('Communication error. Http code: ' . $info['http_code']);
        }
*/

        if (empty($result)) {
            return false;
        }

        $result = json_decode($result, true);
        if (empty($result) || !isset($result['result']['subject']['statusVat'])) {
            return false;
        }

        return $result['result']['subject']['statusVat'] == 'Czynny';
    }

    public static function determineAllowedCustomerStatus($value, $default = null)
    {
        global $CSTATUSES;

        if (!empty($value)) {
            $value = preg_replace('/\s+/', ',', $value);
            $value = preg_split('/\s*[,;]\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (empty($value)) {
            if (empty($default) || !is_array($default)) {
                if ($default === -1) {
                    return null;
                } else {
                    return array(
                        CSTATUS_CONNECTED,
                        CSTATUS_DEBT_COLLECTION,
                    );
                }
            } else {
                return $default;
            }
        } else {
            $all_statuses = self::array_column($CSTATUSES, 'alias');

            $normal = array();
            $negated = array();
            foreach ($value as $status) {
                if (strpos($status, '!') === 0) {
                    $negated[] = substr($status, 1);
                } else {
                    $normal[] = $status;
                }
            }

            if (empty($normal)) {
                $statuses = array_diff($all_statuses, $negated);
            } else {
                $statuses = array_diff(array_intersect($all_statuses, $normal), $negated);
            }
            if (empty($statuses)) {
                return array(
                    CSTATUS_CONNECTED,
                    CSTATUS_DEBT_COLLECTION,
                );
            }

            return array_keys($statuses);
        }
    }

    public static function removeInsecureHtml($html)
    {
        static $hm_purifier;
        if (!isset($hm_purifier)) {
            $hm_config = HTMLPurifier_Config::createDefault();
            $hm_config->set('URI.AllowedSchemes', array(
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'nntp' => true,
                'news' => true,
                'tel' => true,
                'data' => true,
            ));
            $hm_config->set('CSS.MaxImgLength', null);
            $hm_config->set('HTML.MaxImgLength', null);
            if (defined('CACHE_DIR')) {
                $hm_config->set('Cache.SerializerPath', CACHE_DIR . DIRECTORY_SEPARATOR . 'htmlpurifier');
            }
            $hm_purifier = new HTMLPurifier($hm_config);
        }

        return $hm_purifier->purify($html);
    }

    public static function getGusRegonData($type, $id)
    {
        global $LMS;

        static $gus = null;

        if (!isset($gus)) {
            $apikey = ConfigHelper::getConfig('phpui.gusapi_key', 'abcde12345abcde12345');
            if ($apikey == 'abcde12345abcde12345') {
                $env = 'dev';
            } else {
                $env = 'prod';
            }

            $gus = new GusApi(
                $apikey, // your user key / twój klucz użytkownika
                $env
            );

            try {
                if (!$gus->login()) {
                    throw new Exception(trans('Bad REGON API user key'));
                }
            } catch (InvalidUserKeyException $e) {
                return self::GUS_REGON_API_RESULT_BAD_KEY;
            } catch (\GusApi\Exception\NotFoundException $e) {
                return self::GUS_REGON_API_RESULT_NO_DATA;
            } catch (Exception $e) {
                return self::GUS_REGON_API_RESULT_UNKNOWN_ERROR;
            }
        }

        try {
            switch ($type) {
                case self::GUS_REGON_API_SEARCH_TYPE_TEN:
                    $gusReports = $gus->getByNip(preg_replace('/[^a-z0-9]/i', '', $id));
                    break;
                case self::GUS_REGON_API_SEARCH_TYPE_REGON:
                    $gusReports = $gus->getByRegon($id);
                    break;
                case self::GUS_REGON_API_SEARCH_TYPE_RBE:
                    $gusReports = $gus->getByRegon($id);
                    break;
                default:
                    throw new Exception(trans('Unsupported resource type'));
            }

            if (count($gusReports) > 1) {
                die;
            }

            $gusReport = $gusReports[0];
            $personType = $gusReport->getType();

            if ($personType == \GusApi\SearchReport::TYPE_JURIDICAL_PERSON) {
                $fullReport = $gus->getFullReport(
                    $gusReport,
                    ReportTypes::REPORT_PUBLIC_LAW
                );

                $report = reset($fullReport);

                $details = array(
                    'lastname' => $report['praw_nazwa'],
                    'name' => '',
                    'rbename' => $report['praw_organRejestrowy_Nazwa'],
                    'rbe' => $report['praw_numerWRejestrzeEwidencji'],
                    'regon' => array_key_exists('praw_regon9', $report)
                        ? $report['praw_regon9']
                        : $report['praw_regon14'],
                    'ten' => $report['praw_nip'],
                    'addresses' => array(),
                );

                $addresses = array();

                $terc = $report['praw_adSiedzWojewodztwo_Symbol']
                    . $report['praw_adSiedzPowiat_Symbol']
                    . $report['praw_adSiedzGmina_Symbol'];
                $simc = $report['praw_adSiedzMiejscowosc_Symbol'];
                $ulic = $report['praw_adSiedzUlica_Symbol'];
                $location = $LMS->TerytToLocation($terc, $simc, $ulic);

                $addresses[] = array(
                    'location_state_name' => mb_strtolower($report['praw_adSiedzWojewodztwo_Nazwa']),
                    'location_city_name' => $report['praw_adSiedzMiejscowosc_Nazwa'],
                    'location_street_name' => $report['praw_adSiedzUlica_Nazwa'],
                    'location_house' => $report['praw_adSiedzNumerNieruchomosci'],
                    'location_flat' => $report['praw_adSiedzNumerLokalu'],
                    'location_zip' => preg_replace(
                        '/^([0-9]{2})([0-9]{3})$/',
                        '$1-$2',
                        $report['praw_adSiedzKodPocztowy']
                    ),
                    'location_postoffice' => $report['praw_adSiedzMiejscowoscPoczty_Nazwa']
                    == $report['praw_adSiedzMiejscowosc_Nazwa'] ? ''
                        : $report['praw_adSiedzMiejscowoscPoczty_Nazwa'],
                    'location_state' => empty($location) ? 0 : $location['location_state'],
                    'location_city' => empty($location) ? 0 : $location['location_city'],
                    'location_street' => empty($location) ? 0 : $location['location_street'],
                );

                $details['addresses'] = $addresses;
            } elseif ($personType == \GusApi\SearchReport::TYPE_NATURAL_PERSON) {
                $silo = $gusReport->getSilo();

                $siloMapper = array(
                    1 => ReportTypes::REPORT_ACTIVITY_PHYSIC_CEIDG,
                    2 => ReportTypes::REPORT_ACTIVITY_PHYSIC_AGRO,
                    3 => ReportTypes::REPORT_ACTIVITY_PHYSIC_OTHER_PUBLIC,
                    4 => ReportTypes::REPORT_ACTIVITY_LOCAL_PHYSIC_WKR_PUBLIC,
                );

                if (!isset($siloMapper[$silo])) {
                    die;
                }

                $fullReport = $gus->getFullReport(
                    $gusReport,
                    $siloMapper[$silo]
                );

                $report = reset($fullReport);

                $details = array(
                    'lastname' => $report['fiz_nazwa'],
                    'name' => '',
                    'rbename' => $report['fizC_RodzajRejestru_Nazwa'],
                    'rbe' => $report['fizC_numerwRejestrzeEwidencji'],
                    'regon' => array_key_exists('fiz_regon9', $report)
                        ? $report['fiz_regon9']
                        : $report['fiz_regon14'],
                    'addresses' => array(),
                );

                $addresses = array();

                $terc = $report['fiz_adSiedzWojewodztwo_Symbol']
                    . $report['fiz_adSiedzPowiat_Symbol']
                    . $report['fiz_adSiedzGmina_Symbol'];
                $simc = $report['fiz_adSiedzMiejscowosc_Symbol'];
                $ulic = $report['fiz_adSiedzUlica_Symbol'];
                $location = $LMS->TerytToLocation($terc, $simc, $ulic);

                $addresses[] = array(
                    'location_state_name' => mb_strtolower($report['fiz_adSiedzWojewodztwo_Nazwa']),
                    'location_city_name' => $report['fiz_adSiedzMiejscowosc_Nazwa'],
                    'location_street_name' => $report['fiz_adSiedzUlica_Nazwa'],
                    'location_house' => $report['fiz_adSiedzNumerNieruchomosci'],
                    'location_flat' => $report['fiz_adSiedzNumerLokalu'],
                    'location_zip' => preg_replace(
                        '/^([0-9]{2})([0-9]{3})$/',
                        '$1-$2',
                        $report['fiz_adSiedzKodPocztowy']
                    ),
                    'location_postoffice' => $report['fiz_adSiedzMiejscowoscPoczty_Nazwa']
                    == $report->dane['adSiedzMiejscowosc_Nazwa'] ? ''
                        : $report['fiz_adSiedzMiejscowoscPoczty_Nazwa'],
                    'location_state' => empty($location) ? 0 : $location['location_state'],
                    'location_city' => empty($location) ? 0 : $location['location_city'],
                    'location_street' => empty($location) ? 0 : $location['location_street'],
                );

                $details['addresses'] = $addresses;

                $fullReport = $gus->getFullReport(
                    $gusReport,
                    \GusApi\ReportTypes::REPORT_ACTIVITY_PHYSIC_PERSON
                );

                $report = reset($fullReport);
                $details['ten'] = $report['fiz_nip'];
            }

            return $details;
        } catch (InvalidUserKeyException $e) {
            return self::GUS_REGON_API_RESULT_BAD_KEY;
        } catch (\GusApi\Exception\NotFoundException $e) {
            return self::GUS_REGON_API_RESULT_NO_DATA;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
