<?php
declare(strict_types=1);

namespace App\Validation;

final class Validator
{
    private const MAX_NAME_LENGTH = 255;
    private const MAX_EMAIL_LENGTH = 255;
    private const MAX_ADDRESS_LENGTH = 1000;
    private const MAX_PHONE_LENGTH = 64;
    private const MAX_LABEL_LENGTH = 64;
    private const MIN_PHONE_LENGTH = 3;

    public static function required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || trim((string)($data[$field] ?? '')) === '') {
                $errors[$field] = 'required';
            }
        }
        return $errors;
    }

    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && strlen($email) <= self::MAX_EMAIL_LENGTH;
    }

    public static function name(string $name): bool
    {
        $trimmed = trim($name);
        return strlen($trimmed) > 0 && strlen($trimmed) <= self::MAX_NAME_LENGTH;
    }

    public static function address(string $address): bool
    {
        $trimmed = trim($address);
        return strlen($trimmed) > 0 && strlen($trimmed) <= self::MAX_ADDRESS_LENGTH;
    }

    public static function phone(string $phone): bool
    {
        $trimmed = trim($phone);
        $length = strlen($trimmed);
        
        return $length >= self::MIN_PHONE_LENGTH
            && $length <= self::MAX_PHONE_LENGTH
            && preg_match('/^[\d\s\-\+\(\)]+$/', $trimmed) === 1;
    }

    public static function phoneLabel(?string $label): bool
    {
        if ($label === null || $label === '') {
            return true;
        }
        
        return strlen($label) <= self::MAX_LABEL_LENGTH;
    }

    public static function createContact(array $data): array
    {
        $errors = [];

        if (!isset($data['name']) || trim((string)$data['name']) === '') {
            $errors['name'] = 'required';
        } elseif (!self::name((string)$data['name'])) {
            $errors['name'] = 'invalid';
        }

        if (!isset($data['email']) || trim((string)$data['email']) === '') {
            $errors['email'] = 'required';
        } elseif (!self::email((string)$data['email'])) {
            $errors['email'] = 'invalid';
        }

        if (!isset($data['address']) || trim((string)$data['address']) === '') {
            $errors['address'] = 'required';
        } elseif (!self::address((string)$data['address'])) {
            $errors['address'] = 'invalid';
        }

        return $errors;
    }

    public static function createPhones(array $phonesData): array
    {
        $errors = [];
        
        if (!is_array($phonesData) || empty($phonesData)) {
            $errors['phones'] = 'At least one phone number is required';
            return $errors;
        }

        $hasValid = false;
        foreach ($phonesData as $ph) {
            if (is_array($ph)) {
                $number = isset($ph['number']) ? trim((string)$ph['number']) : '';
                if ($number !== '') {
                    if (self::phone($number)) {
                        $hasValid = true;
                    } else {
                        $errors['phones'] = 'One or more phone numbers are invalid';
                    }
                }
                
                if (!self::phoneLabel($ph['label'] ?? null)) {
                    $errors['phones'] = 'One or more phone labels are invalid';
                }
            }
        }

        if (!$hasValid && !isset($errors['phones'])) {
            $errors['phones'] = 'At least one phone number is required';
        }

        return $errors;
    }
}
