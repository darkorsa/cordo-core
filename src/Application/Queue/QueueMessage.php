<?php

namespace Cordo\Core\Application\Queue;

class QueueMessage implements QueueMessageInterface
{
    public $fired = 0;

    public function getName()
    {
        return get_class($this);
    }

    public function fire(): void
    {
        $this->fired++;
    }

    public function fired(): int
    {
        return $this->fired;
    }
}
