<?php

namespace Cordo\Core\Application\Queue;

use Bernard\Message;

interface QueueMessageInterface extends Message
{
    public function getName();

    public function fire(): void;

    public function fired(): int;
}
