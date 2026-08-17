<?php

declare(strict_types=1);

namespace App\Application\Missions\Support;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Analyse et calcule un sous-ensemble volontairement borné de RRULE (RFC 5545) : suffisant
 * pour les besoins réels d'une Mission récurrente (session hebdomadaire, revue mensuelle,
 * tâche quotidienne) sans prétendre à une conformité RFC 5545 complète.
 *
 * Grammaire supportée : FREQ=DAILY|WEEKLY|MONTHLY;INTERVAL=<n>;BYDAY=<MO,TU,...>;COUNT=<n>;UNTIL=<YYYYMMDDTHHMMSSZ>
 * - FREQ est obligatoire.
 * - INTERVAL est optionnel (défaut 1, entier >= 1).
 * - BYDAY n'est accepté que pour FREQ=WEEKLY (liste de MO/TU/WE/TH/FR/SA/SU).
 * - COUNT et UNTIL sont optionnels et mutuellement exclusifs.
 * - UNTIL est interprété explicitement en UTC (suffixe Z, RFC 5545 §3.3.5).
 *
 * Fail-closed : toute clé non listée ci-dessus, ou toute clé répétée de façon ambiguë,
 * rejette la règle entière (422 côté service) plutôt que de l'ignorer silencieusement.
 */
final class RecurrenceRule
{
    private const WEEKDAY_ORDER = ['MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7];
    private const SUPPORTED_KEYS = ['FREQ', 'INTERVAL', 'BYDAY', 'COUNT', 'UNTIL'];

    /** @param list<string> $byDay */
    private function __construct(
        public readonly string $freq,
        public readonly int $interval,
        public readonly array $byDay,
        public readonly ?int $count,
        public readonly ?DateTimeImmutable $until,
    ) {}

    public static function parse(string $raw): self
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new InvalidArgumentException('La règle de récurrence est requise.');
        }

        $parts = [];
        foreach (explode(';', $raw) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            if (! str_contains($segment, '=')) {
                throw new InvalidArgumentException("Segment de règle invalide : « {$segment} ».");
            }
            [$key, $value] = explode('=', $segment, 2);
            $key = strtoupper(trim($key));

            if (! in_array($key, self::SUPPORTED_KEYS, true)) {
                throw new InvalidArgumentException("Clé RRULE non prise en charge : « {$key} ». Cette règle n’est pas appliquée silencieusement en l’ignorant.");
            }
            if (isset($parts[$key])) {
                throw new InvalidArgumentException("Clé RRULE répétée de façon ambiguë : « {$key} ».");
            }

            $parts[$key] = strtoupper(trim($value));
        }

        $freq = $parts['FREQ'] ?? null;
        if (! in_array($freq, ['DAILY', 'WEEKLY', 'MONTHLY'], true)) {
            throw new InvalidArgumentException('FREQ doit être DAILY, WEEKLY ou MONTHLY.');
        }

        $interval = 1;
        if (isset($parts['INTERVAL'])) {
            if (! ctype_digit($parts['INTERVAL']) || (int) $parts['INTERVAL'] < 1) {
                throw new InvalidArgumentException('INTERVAL doit être un entier positif.');
            }
            $interval = (int) $parts['INTERVAL'];
        }

        $byDay = [];
        if (isset($parts['BYDAY'])) {
            if ($freq !== 'WEEKLY') {
                throw new InvalidArgumentException('BYDAY n’est pris en charge que pour FREQ=WEEKLY.');
            }
            foreach (explode(',', $parts['BYDAY']) as $day) {
                $day = trim($day);
                if (! isset(self::WEEKDAY_ORDER[$day])) {
                    throw new InvalidArgumentException("Jour BYDAY invalide : « {$day} ».");
                }
                $byDay[] = $day;
            }
            $byDay = array_values(array_unique($byDay));
        }

        if (isset($parts['COUNT']) && isset($parts['UNTIL'])) {
            throw new InvalidArgumentException('COUNT et UNTIL sont mutuellement exclusifs.');
        }

        $count = null;
        if (isset($parts['COUNT'])) {
            if (! ctype_digit($parts['COUNT']) || (int) $parts['COUNT'] < 1) {
                throw new InvalidArgumentException('COUNT doit être un entier positif.');
            }
            $count = (int) $parts['COUNT'];
        }

        $until = null;
        if (isset($parts['UNTIL'])) {
            if (! str_ends_with($parts['UNTIL'], 'Z')) {
                throw new InvalidArgumentException('UNTIL doit se terminer par Z (UTC explicite, RFC 5545 §3.3.5).');
            }
            // Le \Z du format ne fait que consommer le caractère littéral « Z » : le
            // fuseau UTC est donc passé explicitement en troisième argument plutôt que
            // supposé implicitement depuis le fuseau par défaut de l'application.
            $until = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $parts['UNTIL'], new DateTimeZone('UTC'));
            if ($until === false) {
                throw new InvalidArgumentException('UNTIL doit être au format AAAAMMJJTHHMMSSZ.');
            }
        }

        return new self($freq, $interval, $byDay, $count, $until);
    }

    /**
     * Prochaine occurrence strictement après $after, dans le fuseau de $after. Pour
     * MONTHLY, $anchorDay conserve l'ancre nominale de la récurrence (le jour du mois
     * voulu, ex. 31) indépendamment d'un éventuel clamp subi par une occurrence
     * intermédiaire (ex. 28 en février) — sans elle, l'ancre dériverait mois après mois.
     */
    public function nextOccurrenceAfter(DateTimeImmutable $after, ?int $anchorDay = null): DateTimeImmutable
    {
        return match ($this->freq) {
            'DAILY' => $after->modify("+{$this->interval} day"),
            'WEEKLY' => $this->nextWeekly($after),
            'MONTHLY' => $this->nextMonthly($after, $anchorDay ?? (int) $after->format('j')),
        };
    }

    /** COUNT/UNTIL empêchent-ils de générer une occurrence de plus après $alreadyGenerated occurrences ? */
    public function isExhaustedAfter(int $alreadyGenerated, DateTimeImmutable $candidate): bool
    {
        if ($this->count !== null && $alreadyGenerated >= $this->count) {
            return true;
        }

        return $this->until !== null && $candidate > $this->until;
    }

    private function nextWeekly(DateTimeImmutable $after): DateTimeImmutable
    {
        if ($this->byDay === []) {
            return $after->modify("+{$this->interval} week");
        }

        $targets = collect($this->byDay)->map(fn (string $d): int => self::WEEKDAY_ORDER[$d])->sort()->values();
        $afterDow = (int) $after->format('N');

        foreach ($targets as $dow) {
            if ($dow > $afterDow) {
                return $after->modify('+'.($dow - $afterDow).' day');
            }
        }

        $daysToNextCycleMonday = 7 * $this->interval - $afterDow + 1;

        return $after->modify('+'.($daysToNextCycleMonday + $targets->first() - 1).' day');
    }

    private function nextMonthly(DateTimeImmutable $after, int $anchorDay): DateTimeImmutable
    {
        $target = $after->modify('first day of this month')->modify("+{$this->interval} month");
        $daysInTarget = (int) $target->format('t');
        $clampedDay = min($anchorDay, $daysInTarget);

        return $target->modify('+'.($clampedDay - 1).' day')
            ->setTime((int) $after->format('H'), (int) $after->format('i'), (int) $after->format('s'));
    }
}
