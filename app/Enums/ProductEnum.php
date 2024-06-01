<?php

namespace App\Enums;

enum ProductEnum: string
{
    case DIGITAL_PRODUCT = 'digital_product';
    case SKILL_SELLING = 'skill_selling';
    case TEMPLATE_HUB = 'template_hub';
    case PRINT_ON_DEMAND = 'print_on_demand';
}
