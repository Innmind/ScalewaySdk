<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\Immutable\SetInterface;
use function Innmind\Immutable\assertSet;

final class Server
{
    private $id;
    private $organization;
    private $name;
    private $image;
    private $ip;
    private $state;
    private $allowedActions;
    private $tags;
    private $volumes;

    public function __construct(
        Server\Id $id,
        Organization\Id $organization,
        Server\Name $name,
        Image\Id $image,
        ?IP\Id $ip,
        Server\State $state,
        SetInterface $allowedActions,
        SetInterface $tags,
        SetInterface $volumes
    ) {
        assertSet(Server\Action::class, $allowedActions, 5);
        assertSet('string', $tags, 6);
        assertSet(Volume\Id::class, $volumes, 7);

        $this->id = $id;
        $this->organization = $organization;
        $this->name = $name;
        $this->image = $image;
        $this->ip = $ip;
        $this->state = $state;
        $this->allowedActions = $allowedActions;
        $this->tags = $tags;
        $this->volumes = $volumes;
    }

    public function id(): Server\Id
    {
        return $this->id;
    }

    public function organization(): Organization\Id
    {
        return $this->organization;
    }

    public function name(): Server\Name
    {
        return $this->name;
    }

    public function image(): Image\Id
    {
        return $this->image;
    }

    public function attachedToAnIP(): bool
    {
        return $this->ip instanceof IP\Id;
    }

    public function ip(): IP\Id
    {
        return $this->ip;
    }

    public function state(): Server\State
    {
        return $this->state;
    }

    /**
     * @return SetInterface<Server\Action>
     */
    public function allowedActions(): SetInterface
    {
        return $this->allowedActions;
    }

    /**
     * @return SetInterface<string>
     */
    public function tags(): SetInterface
    {
        return $this->tags;
    }

    /**
     * @return SetInterface<Volume\Id>
     */
    public function volumes(): SetInterface
    {
        return $this->volumes;
    }
}
