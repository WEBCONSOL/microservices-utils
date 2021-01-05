<?php

namespace Ezpizee\Utils;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use RuntimeException;

class DataFormat
{
  private function __construct()
  {
  }

  public static function validTelephone(string $telephone, string $countryCode = null)
  : string
  {
    $properties = self::phoneProperties($telephone, $countryCode);
    return $properties['telephone'];
  }

  public static function phoneProperties(string $telephone, string $countryCode = null)
  : array
  {
    $telephone = "+" . self::numberOnly($telephone);
    $phoneUtil = PhoneNumberUtil::getInstance();
    try {
      $numberProto = $phoneUtil->parse($telephone, $countryCode);
      $isValid = $phoneUtil->isValidNumber($numberProto);
      if ($isValid) {
        $data = ['telephone' => $phoneUtil->format($numberProto, PhoneNumberFormat::E164)];
        $data['international'] = $phoneUtil->format($numberProto, PhoneNumberFormat::INTERNATIONAL);
        $data['national'] = $phoneUtil->format($numberProto, PhoneNumberFormat::NATIONAL);
        $data['regionCode'] = $phoneUtil->getRegionCodeForNumber($numberProto);
        $data['regionCode'] = $phoneUtil->getCountryCodeForRegion($data['regionCode']);
        return $data;
      }
      else {
        throw new RuntimeException(
          ResponseCodes::MESSAGE_ERROR_INVALID_PHONE_NUMBER, ResponseCodes::CODE_ERROR_FORBIDDEN_REQUEST);
      }
    }
    catch (NumberParseException $e) {
      throw new RuntimeException($e->getMessage(), ResponseCodes::CODE_ERROR_FORBIDDEN_REQUEST);
    }
  }

  public static function numberOnly(string $str)
  : string
  {
    return preg_replace("/[^0-9]/", "", $str);
  }

  public static function isEzpzFakePhone(string $phone)
  : bool
  {
    return StringUtil::startsWith($phone, Constants::EZPZ_FAKE_PHONE_PFX);
  }

  public static function isEzpzFakeEmail(string $email)
  : bool
  {
    return StringUtil::startsWith($email, Constants::EZPZ_FAKE_EMAIL_PFX);
  }
}
