<?php

namespace App\Services\Stats;

class UniqueViewerKeyBuilder
{
    private const string NON_EMPTY_STRING_SQL = "IS NOT NULL AND %s <> ''";

    public function build(string $tableAlias): string
    {
        $visitorIdPresent = sprintf(self::NON_EMPTY_STRING_SQL, $tableAlias.'.visitor_id');
        $fingerprintPresent = sprintf(self::NON_EMPTY_STRING_SQL, $tableAlias.'.fingerprint');
        $sessionIdPresent = sprintf(self::NON_EMPTY_STRING_SQL, $tableAlias.'.session_id');

        return "(
            CASE
              WHEN {$tableAlias}.user_id IS NOT NULL THEN CONCAT('U:', {$tableAlias}.user_id)
              WHEN {$tableAlias}.visitor_id {$visitorIdPresent} THEN CONCAT('V:', {$tableAlias}.visitor_id)
              WHEN {$tableAlias}.fingerprint {$fingerprintPresent} THEN CONCAT('F:', {$tableAlias}.fingerprint)
              WHEN {$tableAlias}.session_id {$sessionIdPresent} THEN CONCAT('S:', {$tableAlias}.session_id)
              ELSE CONCAT('I:', COALESCE({$tableAlias}.ip_address, ''))
            END
        )";
    }
}
