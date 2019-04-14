<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

interface Marketplace
{
    public function images(): Marketplace\Images;
}
