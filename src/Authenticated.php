<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

interface Authenticated
{
    public function images(Region $region): Authenticated\Images;
    public function ips(Region $region): Authenticated\IPs;
    public function servers(Region $region): Authenticated\Servers;
    public function tokens(): Authenticated\Tokens;
    public function users(): Authenticated\Users;
    public function volumes(Region $region): Authenticated\Volumes;
}
