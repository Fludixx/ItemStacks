<?php

namespace ItemStacks;

use pocketmine\entity\object\ItemEntity as OriginalItemEntity;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\level\Level;
use pocketmine\level\sound\PopSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;

class ItemEntity extends OriginalItemEntity {

    public $contains = 1;
    public $canBeStacked = true;

    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        $this->contains = $this->getItem()->getCount();
    }

    public function onCollideWithPlayer(Player $player) : void{
        if($this->age > 20) {
            $item = $this->getItem();
            while($this->contains > 64) {
                $player->getInventory()->addItem($item->setCount(64));
                $this->contains -= 64;
            }
            $player->getInventory()->addItem($item->setCount($this->contains));
            $this->kill();
            $this->getLevel()->addSound(new PopSound($player), [$player]);
        }
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if($this->closed){
            return false;
        }

        $this->age += $tickDiff;
        if($this->age > 6000){
            $ev = new ItemDespawnEvent($this);
            $ev->call();
            if($ev->isCancelled()){
                $this->age = 0;
            }else{
                $this->flagForDespawn();
            }
        }

        if($this->age % 40 === 0) {
            $nearbyEntities = $this->getLevel()->getNearbyEntities(
                new AxisAlignedBB(
                    ((float)$this->x - 1), ((float)$this->y - 1), ((float)$this->z - 1),
                    ((float)$this->x + 1), ((float)$this->y + 1), ((float)$this->z + 1)
                ),
                $this
            );
            foreach ($nearbyEntities as $nearbyEntity) {
                if ($nearbyEntity instanceof self &&
                    $nearbyEntity->canBeStacked &&
                    ($nearbyEntity->contains < 64 && $this->contains < 64) &&
                    $nearbyEntity->getItem()->getId() === $this->getItem()->getId() &&
                    $nearbyEntity->getItem()->getDamage() === $this->getItem()->getDamage()) {

                    $this->contains += $nearbyEntity->contains;
                    $nearbyEntity->kill();
                    $this->item->setCount(64);
                }
            }

            if($this->getItem()->getCount() !== $this->contains) {
                $this->getLevel()->dropItem($this->asVector3(), $this->getItem()->setCount($this->contains), new Vector3(0, 0, 0));
                $this->kill();
            }
        }
        return true;
    }

}