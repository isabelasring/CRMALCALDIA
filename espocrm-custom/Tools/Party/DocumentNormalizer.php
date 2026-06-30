<?php

namespace Espo\Custom\Tools\Party;

class DocumentNormalizer
{
    public static function normalize(string $document): string
    {
        $document = trim($document);

        if ($document === '') {
            return '';
        }

        return preg_replace('/[\s.\-]/', '', $document) ?? '';
    }

    /**
     * NIT colombiano: cuerpo con puntos + guion + dígito de verificación (ej. 900.123.456-7).
     */
    public static function formatNit(string $nit): string
    {
        $digits = self::normalize($nit);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) < 2) {
            return $digits;
        }

        $checkDigit = substr($digits, -1);
        $body = substr($digits, 0, -1);

        return self::formatNumberBodyWithDots($body) . '-' . $checkDigit;
    }

    private static function formatNumberBodyWithDots(string $body): string
    {
        $len = strlen($body);

        if ($len <= 3) {
            return $body;
        }

        $remainder = $len % 3;
        $parts = [];
        $offset = 0;

        if ($remainder > 0) {
            $parts[] = substr($body, 0, $remainder);
            $offset = $remainder;
        }

        while ($offset < $len) {
            $parts[] = substr($body, $offset, 3);
            $offset += 3;
        }

        return implode('.', $parts);
    }

    /**
     * @return string[]
     */
    public static function candidates(string $document): array
    {
        $document = trim($document);
        $normalized = self::normalize($document);

        $list = [];

        if ($document !== '') {
            $list[] = $document;
        }

        if ($normalized !== '' && $normalized !== $document) {
            $list[] = $normalized;
        }

        return array_values(array_unique($list));
    }
}
