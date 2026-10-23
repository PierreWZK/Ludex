<?php

namespace App\Entity;

/** Une ressource qui appartient à un utilisateur : lui seul la voit et la modifie */
interface OwnedInterface
{
    public function getOwner(): ?User;

    public function setOwner(User $owner): static;
}
