<?php
/**
 * Simple telegram recipient
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification\recipient;

use tuyakhov\notifications\NotifiableTrait;
use tuyakhov\notifications\NotifiableInterface;

class Telegram implements NotifiableInterface
{
    use NotifiableTrait;

    /**
     * @var int
     */
    private $chatId;

    /**
     * @param int $chatId
     */
    public function __construct(int $chatId)
    {
        $this->chatId = $chatId;
    }

    /**
     * @return array
     */
    public function viaChannels(): array
    {
        return ['telegram'];
    }

    /**
     * @return string
     */
    public function routeNotificationForTelegram(): int
    {
        return $this->chatId;
    }
}
