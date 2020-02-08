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
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;

final class Http implements Images
{
    private Transport $fulfill;
    private Region $region;
    private Token\Id $token;

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
    public function list(): Set
    {
        $url = Url::of("https://cp-{$this->region->toString()}.scaleway.com/images");
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
                Json::decode($response->body()->toString())['images'],
            );
            $next = null;

            if ($response->headers()->contains('Link')) {
                $next = $response
                    ->headers()
                    ->get('Link')
                    ->values()
                    ->filter(static function(LinkValue $link): bool {
                        return $link->relationship() === 'next';
                    });

                if ($next->size() === 1) {
                    $next = $url
                        ->withPath(first($next)->url()->path())
                        ->withQuery(first($next)->url()->query());
                    $url = $next;
                }
            }
        } while ($next instanceof Url);

        $set = Set::of(Image::class);

        foreach ($images as $image) {
            $set = ($set)($this->decode($image));
        }

        return $set;
    }

    public function get(Image\Id $id): Image
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/images/{$id->toString()}"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $image = Json::decode($response->body()->toString())['image'];

        return $this->decode($image);
    }

    private function decode(array $image): Image
    {
        return new Image(
            new Image\Id($image['id']),
            new Organization\Id($image['organization']),
            new Image\Name($image['name']),
            Image\Architecture::of($image['arch']),
            $image['public']
        );
    }
}
