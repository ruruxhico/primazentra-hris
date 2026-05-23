<?php

namespace App\Validation;

class AuthRules
{
    public function email_domain_accepts_mail(string $value, ?string &$error = null): bool
    {
        $domain = substr(strrchr($value, '@') ?: '', 1);

        if ($domain === '') {
            $error = 'Email domain is required.';
            return false;
        }

        $domain = strtolower(trim($domain));

        if (function_exists('idn_to_ascii')) {
            $asciiDomain = idn_to_ascii($domain, IDNA_DEFAULT);
            if ($asciiDomain !== false) {
                $domain = strtolower($asciiDomain);
            }
        }

        if ($domain === 'localhost') {
            $error = 'Email domain is invalid.';
            return false;
        }

        $domainLooksValid = (bool) preg_match('/^(?=.{1,253}$)(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,}$/i', $domain);
        if (! $domainLooksValid) {
            $error = 'Email domain format is invalid.';
            return false;
        }

        if (! $this->domainCanReceiveMail($domain)) {
            $error = 'Email domain is not configured to receive email.';
            return false;
        }

        return true;
    }

    public function strong_password(string $value, ?string &$error = null): bool
    {
        $isStrong = (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $value);

        if (! $isStrong) {
            $error = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
            return false;
        }

        return true;
    }

    private function domainCanReceiveMail(string $domain): bool
    {
        if (function_exists('dns_get_record')) {
            $mxRecords = @dns_get_record($domain, DNS_MX);
            if (is_array($mxRecords) && $mxRecords !== []) {
                foreach ($mxRecords as $record) {
                    $target = strtolower(trim((string) ($record['target'] ?? '')));
                    if ($target !== '' && $target !== '.') {
                        return true;
                    }
                }

                // Null MX target (.) means the domain does not accept email.
                return false;
            }

            $aRecords = @dns_get_record($domain, DNS_A + DNS_AAAA);
            if (is_array($aRecords) && $aRecords !== []) {
                return true;
            }
        }

        if (function_exists('checkdnsrr')) {
            if (@checkdnsrr($domain, 'MX')) {
                return true;
            }

            if (@checkdnsrr($domain, 'A') || @checkdnsrr($domain, 'AAAA')) {
                return true;
            }
        }

        return false;
    }
}
