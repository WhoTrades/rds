<?php
/**
 * Simple email recipient
 *
 * @author Anton Gorlanov <antonxacc@gmail.com>
 */
declare(strict_types=1);

namespace whotrades\rds\services\notification\recipient;

use tuyakhov\notifications\NotifiableTrait;
use tuyakhov\notifications\NotifiableInterface;

class Email implements NotifiableInterface
{
    use NotifiableTrait;

    /**
     * @var string
     */
    private $email;

    /**
     * @param string $email
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
