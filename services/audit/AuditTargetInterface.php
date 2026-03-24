<?php

declare(strict_types=1);

namespace app\services\audit;

/**
 * Contract for audit log dispatch targets.
 *
 * Each target receives the structured audit entry and is responsible
 * for delivering it to a specific backend (database, syslog, HTTP, etc.).
 */
interface AuditTargetInterface
{
    /**
     * Send one audit entry to the target backend.
     *
     * @param array{
     *     action: string,
     *     object_type: ?string,
     *     object_id: ?int,
     *     user_id: ?int,
     *     metadata: ?string,
     *     ip_address: ?string,
     *     user_agent: ?string,
     *     created_at: int
     * } $entry
     */
    public function send(array $entry): void;
}
