<?php

namespace Ezpizee\Utils;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;

final class JWT
{
    protected static $JWT_API_CORE_ISSUER = "ezpz.api.core";
    protected static $JWT_API_CORE_AUDIENCE = "ezpz.api.core.client";

    private function __construct()
    {
    }

    public static final function encode(string $audience, array $data)
    : string
    {
        $signer = new Sha256();
        $config = Configuration::forUnsecuredSigner();
        return (new Builder())
            ->withHeader('alg', $signer->getAlgorithmId())
            ->withHeader('typ', 'JWT')
            ->issuedBy(self::$JWT_API_CORE_ISSUER)
            ->permittedFor($audience)
            ->withClaim('data', json_encode($data))
            ->getToken($config->signer(), $config->signingKey());
    }

    public static function isValidToken(string $audience, string $token)
    : bool
    {
        if (!empty($token)) {
            $parsedToken = (new Parser())->parse($token);
            $config = Configuration::forUnsecuredSigner();
            $audiences = $parsedToken->claims()->has('aud') ? $parsedToken->claims()->get('aud') : '';
            if (is_array($audiences)) {
                if (!in_array($audience, $audiences)) {
                    return false;
                }
            }
            else if ($audience !== $audiences) {
                return false;
            }
            $issuedBy = new IssuedBy(self::$JWT_API_CORE_ISSUER);
            $audience = new PermittedFor($audience);
            $alg = $parsedToken->claims()->has('alg') ? $parsedToken->claims()->get('alg') : '';
            if ($alg === $config->signer()->getAlgorithmId()) {
                return $config->validator()->validate($parsedToken, $issuedBy, $audience);
            }
        }
        return false;
    }

    public static final function getDataFromToken(string $token)
    : string
    {
        $parsed = self::parseToken($token);
        return $parsed->claims()->has('data') ? $parsed->claims()->get('data') : "";
    }

    public static final function parseToken(string $token)
    : Token
    {
        return (new Parser())->parse($token);
    }

    public static final function getIssuerFromToken(string $token)
    : string
    {
        $parsed = self::parseToken($token);
        return $parsed->claims()->has('iss') ? $parsed->claims()->get('iss') : "";
    }

    public static final function getAudienceFromToken(string $token)
    : string
    {
        $parsed = self::parseToken($token);
        return $parsed->claims()->has('aud') ? $parsed->claims()->get('aud') : '';
    }
}
