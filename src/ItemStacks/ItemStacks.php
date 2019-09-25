<?php

namespace ItemStacks;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class ItemStacks extends PluginBase implements Listener {

    public function onEnable()
    {
        Entity::registerEntity(ItemEntity::class, true, ['Item', 'minecraft:item']);
    }

}