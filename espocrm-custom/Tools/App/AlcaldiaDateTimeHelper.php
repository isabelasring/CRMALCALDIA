<?php

namespace Espo\Custom\Tools\App;

/**
 * Fecha y hora oficiales del CRM Alcaldía: Bogotá, hora militar (24 h).
 *
 * EspoCRM guarda campos datetime en UTC. Siempre use espoStorageNowString()
 * al escribir en entidades; use formatDisplay* al mostrar.
 */
class AlcaldiaDateTimeHelper
{
    public const TIME_ZONE = AlcaldiaLocaleDefaults::TIME_ZONE;

    /** Formato EspoCRM (moment.js): DD.MM.YYYY */
    public const ESPO_DATE_FORMAT = AlcaldiaLocaleDefaults::DATE_FORMAT;

    /** Formato EspoCRM (moment.js): HH:mm — hora militar */
    public const ESPO_TIME_FORMAT = AlcaldiaLocaleDefaults::TIME_FORMAT;

    /** Almacenamiento datetime en BD (UTC) */
    public const PHP_ESPO_STORAGE_DATETIME = 'Y-m-d H:i:s';

    /** Almacenamiento date en BD */
    public const PHP_STORAGE_DATE = 'Y-m-d';

    /** Pantalla CRM en Bogotá (equivalente a DD.MM.YYYY) */
    public const PHP_DISPLAY_DATE = 'd.m.Y';

    /** Pantalla CRM en Bogotá con hora militar */
    public const PHP_DISPLAY_DATETIME = 'd.m.Y H:i';

    /** Documentos Word / Excel ciudadanos */
    public const PHP_DOCUMENT_DATE = 'd/m/Y';

    public const PHP_DOCUMENT_DATETIME = 'd/m/Y H:i';

    public static function timeZone(): \DateTimeZone
    {
        return new \DateTimeZone(self::TIME_ZONE);
    }

    public static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', self::timeZone());
    }

    /**
     * Valor para campos datetime de EspoCRM (UTC).
     */
    public static function espoStorageNowString(): string
    {
        return self::now()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(self::PHP_ESPO_STORAGE_DATETIME);
    }

    public static function storageDateString(): string
    {
        return self::now()->format(self::PHP_STORAGE_DATE);
    }

    /**
     * Texto legible en hora Bogotá (nombres, etiquetas).
     */
    public static function labelNowDateTime(): string
    {
        return self::now()->format(self::PHP_DISPLAY_DATETIME);
    }

    /**
     * @deprecated Use espoStorageNowString() for entity datetime fields.
     */
    public static function storageNowString(): string
    {
        return self::espoStorageNowString();
    }

    public static function toEspoStorageDateTime(mixed $value): ?string
    {
        $parsed = self::parseFromEspo($value);

        if (!$parsed) {
            return null;
        }

        return $parsed
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(self::PHP_ESPO_STORAGE_DATETIME);
    }

    public static function formatDisplayDate(mixed $value): string
    {
        $parsed = self::parseFromEspo($value);

        return $parsed
            ? $parsed->setTimezone(self::timeZone())->format(self::PHP_DISPLAY_DATE)
            : '';
    }

    public static function formatDisplayDateTime(mixed $value): string
    {
        $parsed = self::parseFromEspo($value);

        return $parsed
            ? $parsed->setTimezone(self::timeZone())->format(self::PHP_DISPLAY_DATETIME)
            : '';
    }

    public static function formatDocumentDate(mixed $value): string
    {
        $parsed = self::parseFromEspo($value);

        return $parsed
            ? $parsed->setTimezone(self::timeZone())->format(self::PHP_DOCUMENT_DATE)
            : '';
    }

    public static function formatDocumentDateTime(mixed $value): string
    {
        $parsed = self::parseFromEspo($value);

        return $parsed
            ? $parsed->setTimezone(self::timeZone())->format(self::PHP_DOCUMENT_DATETIME)
            : '';
    }

    public static function documentNowDate(): string
    {
        return self::now()->format(self::PHP_DOCUMENT_DATE);
    }

    public static function documentNowDateTime(): string
    {
        return self::now()->format(self::PHP_DOCUMENT_DATETIME);
    }

    private static function parseFromEspo(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($string, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            try {
                return new \DateTimeImmutable($string, self::timeZone());
            } catch (\Exception) {
                return null;
            }
        }
    }
}
