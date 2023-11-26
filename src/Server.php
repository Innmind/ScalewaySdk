<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\Immutable\Set;

final class Server
{
    private Server\Id $id;
    private Organization\Id $organization;
    private Server\Name $name;
    private Image\Id $image;
    private IP\Id $ip;
    private Server\State $state;
    /** @var Set<Server\Action> */
    private Set $allowedActions;
    /** @var Set<string> */
    private Set $tags;
    /** @var Set<Volume\Id> */
    private Set $volumes;

    /**
     * @param Set<Server\Action> $allowedActions
     * @param Set<string> $tags
     * @param Set<Volume\Id> $volumes
     */
    public function __construct(
        Server\Id $id,
        Organization\Id $organization,
        Server\Name $name,
        Image\Id $image,
        IP\Id $ip,
        Server\State $state,
        Set $allowedActions,
        Set $tags,
        Set $volumes,
    ) {
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

    public function ip(): IP\Id
    {
        return $this->ip;
    }

    public function state(): Server\State
    {
        return $this->state;
    }

    /**
     * @return Set<Server\Action>
     */
    public function allowedActions(): Set
    {
        return $this->allowedActions;
    }

    /**
     * @return Set<string>
     */
    public function tags(): Set
    {
        return $this->tags;
    }

    /**
     * @return Set<Volume\Id>
     */
    public function volumes(): Set
    {
        return $this->volumes;
    }
}
