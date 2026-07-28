<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryNoteStatus: string
{
    case Pending = 'pending';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Returned = 'returned';
}
