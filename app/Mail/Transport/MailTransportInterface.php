<?php

declare(strict_types=1);

namespace App\Mail\Transport;

use App\Mail\OutgoingMail;

interface MailTransportInterface
{
    public function send(OutgoingMail $mail): ?string;
}
