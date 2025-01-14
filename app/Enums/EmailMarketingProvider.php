<?php

namespace App\Enums;

enum EmailMarketingProvider: string
{
    case MailChimp = 'MailChimp';
    case MailerLite = 'MailerLite';
}
