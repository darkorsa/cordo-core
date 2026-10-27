<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Persistance\Doctrine;

use Throwable;
use League\Tactician\Middleware;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Wraps command execution inside a Doctrine ORM transaction.
 *
 * On failure the entity manager is cleared rather than closed, so long-running
 * processes (console commands, queue workers) can keep handling commands.
 * A failed flush still leaves it closed, because the UnitOfWork closes it itself.
 */
final class TransactionMiddleware implements Middleware
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * {@inheritdoc}
     */
    public function execute($command, callable $next)
    {
        $this->entityManager->beginTransaction();

        try {
            $returnValue = $next($command);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $e) {
            $this->rollback();

            throw $e;
        }

        return $returnValue;
    }

    private function rollback(): void
    {
        $connection = $this->entityManager->getConnection();

        try {
            if ($connection->isTransactionActive()) {
                $this->entityManager->rollback();
            }
        } catch (Throwable) {
            // the transaction state is unknown, so nothing in memory can be trusted;
            // swallowed so the command's own exception is the one that gets rethrown
            $this->entityManager->close();
            return;
        }

        // entities flushed in the rolled back transaction no longer match the database;
        // when nested in someone else's transaction, leave that decision to its owner
        if (!$connection->isTransactionActive() && $this->entityManager->isOpen()) {
            $this->entityManager->clear();
        }
    }
}
