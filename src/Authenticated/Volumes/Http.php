<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Volumes;

use Innmind\ScalewaySdk\{
    Authenticated\Volumes,
    Region,
    Token,
    Volume,
    Organization,
    Server,
    Http\Header\AuthToken,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header\ContentType,
    Header\ContentTypeValue,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Filesystem\Stream\StringStream;

final class Http implements Volumes
{
    private $fulfill;
    private $region;
    private $token;

    public function __construct(
        Transport $fulfill,
        Region $region,
        Token\Id $token
    ) {
        $this->fulfill = $fulfill;
        $this->region = $region;
        $this->token = $token;
    }

    public function create(
        string $name,
        Organization\Id $organization,
        Volume\Size $size,
        Volume\Type $type
    ): Volume {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/volumes"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                new ContentType(
                    new ContentTypeValue('application', 'json')
                )
            ),
            new StringStream(Json::encode([
                'name' => $name,
                'organization' => (string) $organization,
                'size' => $size->toInt(),
                'type' => (string) $type,
            ]))
        ));

        $volume = Json::decode((string) $response->body())['volume'];

        return new Volume(
            new Volume\Id($volume['id']),
            $volume['name'],
            new Organization\Id($volume['organization']),
            Volume\Size::of($volume['size']),
            Volume\Type::of($volume['volume_type']),
            \is_array($volume['server']) ? new Server\Id($volume['server']['id']) : null
        );
    }
}
