<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Internal\EventStoreTransactionConnection;

class EventStoreTransaction
{
    private int $transactionId;
    private ?UserCredentials $userCredentials;
    private EventStoreTransactionConnection $connection;
    private bool $isRolledBack = false;
    private bool $isCommitted = false;

    public function __construct(
        int $transactionId,
        ?UserCredentials $userCredentials,
        EventStoreTransactionConnection $connection
    ) {
        $this->transactionId = $transactionId;
        $this->userCredentials = $userCredentials;
        $this->connection = $connection;
    }

    public function transactionId(): int
    {
        return $this->transactionId;
    }

    public function commit(): WriteResult
    {
        if ($this->isRolledBack) {
            throw new \RuntimeException('Cannot commit a rolledback transaction');
        }

        if ($this->isCommitted) {
            throw new \RuntimeException('Transaction is already committed');
        }

        return $this->connection->commitTransaction($this, $this->userCredentials);
    }

    /**
     * @param list<EventData> $events
     */
    public function write(array $events = []): void
    {
        if ($this->isRolledBack) {
            throw new \RuntimeException('Cannot commit a rolledback transaction');
        }

        if ($this->isCommitted) {
            throw new \RuntimeException('Transaction is already committed');
        }

        $this->connection->transactionalWrite($this, $events, $this->userCredentials);
    }

    public function rollback(): void
    {
        if ($this->isCommitted) {
            throw new \RuntimeException('Transaction is already committed');
        }

        $this->isRolledBack = true;
    }
}
