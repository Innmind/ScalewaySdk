<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Images;

use Innmind\ScalewaySdk\{
    Authenticated\Images,
    Token,
    Image,
    Organization,
    Region,
    Http\Header\AuthToken,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header\LinkValue,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Http implements Images
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

    /**
     * {@inheritdoc}
     */
    public function list(): SetInterface
    {
        $url = Url::fromString("https://cp-{$this->region}.scaleway.com/images");
        $images = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token)
                )
            ));

            $images = \array_merge(
                $images,
                Json::decode((string) $response->body())['images']
            );
            $next = null;

            if ($response->headers()->has('Link')) {
                $next = $response
                    ->headers()
                    ->get('Link')
                    ->values()
                    ->filter(static function(LinkValue $link): bool {
                        return $link->relationship() === 'next';
                    });

                if ($next->size() === 1) {
                    $next = $url
                        ->withPath($next->current()->url()->path())
                        ->withQuery($next->current()->url()->query());
                    $url = $next;
                }
            }
        } while ($next instanceof UrlInterface);

        $set = Set::of(Image::class);

        foreach ($images as $image) {
            $set = $set->add($this->decode($image));
        }

        return $set;
    }

    public function get(Image\Id $id): Image
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/images/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $image = Json::decode((string) $response->body())['image'];

        return $this->decode($image);
    }

    private function decode(array $image): Image
    {
        return new Image(
            new Image\Id($image['id']),
            new Organization\Id($image['organization']),
            $image['name'],
            Image\Architecture::of($image['arch']),
            $image['public']
        );
    }
}
