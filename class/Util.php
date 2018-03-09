<?php
namespace R;
class Util {
    public static function Encrypt($str, $salt = null) {
        $md5 = md5($str);
        eval(base64_decode("JHBhc3MgPSBtZDUoc3Vic3RyKHN1YnN0cigkbWQ1LC0xNiksLTgpLnN1YnN0cihzdWJzdHIoJG1kNSwtMTYpLDAsLTgpLnN1YnN0cihzdWJzdHIoJG1kNSwwLC0xNiksLTgpLnN1YnN0cihzdWJzdHIoJG1kNSwwLC0xNiksMCwtOCkpOw=="));
        if (is_null($salt)) {
            $rounds = rand(5000, 9999);
            if (CRYPT_SHA512 == 1) {
                $pass = crypt($pass, '$6$rounds=' . $rounds . '$' . md5(uniqid()) . '$');
            } elseif (CRYPT_SHA256 == 1) {
                $pass = crypt($pass, '$5$rounds=' . $rounds . '$' . md5(uniqid()) . '$');
            } else {
                $pass = crypt($pass);
            }
            return $pass;
        } else {
            return crypt($pass, $salt);
        }
    }

    public static function GeneratePassword($length = 6, $strength = 0) {
        $vowels = 'aeuy';
        $consonants = 'bdghjmnpqrstvz';
        if ($strength &1) {
            $consonants .= 'BDGHJLMNPQRSTVWXZ';
        }
        if ($strength &2) {
            $vowels .= "AEUY";
        }
        if ($strength &4) {
            $consonants .= '23456789';
        }
        if ($strength &8) {
            $consonants .= '@#$%';
        }

        $password = '';
        $alt = time() % 2;
        for ($i = 0; $i < $length; $i++) {
            if ($alt == 1) {
                $password .= $consonants[(rand() % strlen($consonants))];
                $alt = 0;
            } else {
                $password .= $vowels[(rand() % strlen($vowels))];
                $alt = 1;
            }
        }
        return $password;
    }
}

?>