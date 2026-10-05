<?php

declare(strict_types=1);

namespace GhanaSms\Enums;

enum ErrorType: string
{
    case Authentication = 'authentication';
    case InsufficientBalance = 'insufficient_balance';
    case InvalidSender = 'invalid_sender';
    case InvalidRecipient = 'invalid_recipient';
    case Unknown = 'unknown';
}
