<?php

namespace App\Support;

use Locale;

class InternationalDirectory
{
    /**
     * @return array<string, string>
     */
    public static function callingCodes(): array
    {
        return [
            'AE' => '+971',
            'AR' => '+54',
            'AT' => '+43',
            'AU' => '+61',
            'AZ' => '+994',
            'BD' => '+880',
            'BE' => '+32',
            'BG' => '+359',
            'BH' => '+973',
            'BR' => '+55',
            'CA' => '+1',
            'CH' => '+41',
            'CL' => '+56',
            'CN' => '+86',
            'CO' => '+57',
            'CY' => '+357',
            'CZ' => '+420',
            'DE' => '+49',
            'DK' => '+45',
            'DZ' => '+213',
            'EE' => '+372',
            'EG' => '+20',
            'ES' => '+34',
            'ET' => '+251',
            'FI' => '+358',
            'FR' => '+33',
            'GB' => '+44',
            'GH' => '+233',
            'GR' => '+30',
            'HK' => '+852',
            'HR' => '+385',
            'HU' => '+36',
            'ID' => '+62',
            'IE' => '+353',
            'IL' => '+972',
            'IN' => '+91',
            'IQ' => '+964',
            'IS' => '+354',
            'IT' => '+39',
            'JO' => '+962',
            'JP' => '+81',
            'KE' => '+254',
            'KR' => '+82',
            'KW' => '+965',
            'KZ' => '+7',
            'LB' => '+961',
            'LT' => '+370',
            'LU' => '+352',
            'LV' => '+371',
            'LY' => '+218',
            'MA' => '+212',
            'MC' => '+377',
            'MD' => '+373',
            'ME' => '+382',
            'MK' => '+389',
            'MT' => '+356',
            'MX' => '+52',
            'MY' => '+60',
            'NG' => '+234',
            'NL' => '+31',
            'NO' => '+47',
            'NZ' => '+64',
            'OM' => '+968',
            'PE' => '+51',
            'PH' => '+63',
            'PK' => '+92',
            'PL' => '+48',
            'PT' => '+351',
            'QA' => '+974',
            'RO' => '+40',
            'RS' => '+381',
            'RU' => '+7',
            'SA' => '+966',
            'SE' => '+46',
            'SG' => '+65',
            'SI' => '+386',
            'SK' => '+421',
            'SN' => '+221',
            'TH' => '+66',
            'TN' => '+216',
            'TR' => '+90',
            'TW' => '+886',
            'UA' => '+380',
            'US' => '+1',
            'VN' => '+84',
            'ZA' => '+27',
        ];
    }

    /**
     * @return array<int, array{code:string,name:string,flag:string,dial_code:string,search:string}>
     */
    public static function countries(): array
    {
        $countries = collect(self::callingCodes())
            ->map(function (string $dialCode, string $code): array {
                $name = Locale::getDisplayRegion('-'.$code, 'en') ?: $code;

                return [
                    'code' => $code,
                    'name' => $name,
                    'flag' => self::flagEmoji($code),
                    'dial_code' => $dialCode,
                    'search' => mb_strtolower($name.' '.$code.' '.$dialCode),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return $countries;
    }

    public static function dialCodeFor(string $countryCode, string $default = '+212'): string
    {
        return self::callingCodes()[strtoupper($countryCode)] ?? $default;
    }

    public static function flagEmoji(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);

        if (strlen($countryCode) !== 2) {
            return '🏳️';
        }

        $offset = 127397;
        $chars = preg_split('//u', $countryCode, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($chars)
            ->map(fn (string $char) => mb_chr(ord($char) + $offset, 'UTF-8'))
            ->implode('');
    }
}
