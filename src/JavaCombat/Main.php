<?php

namespace JavaCombat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\entity\Living;
use pocketmine\world\Position;
use pocketmine\event\player\PlayerMissSwingEvent;
use pocketmine\world\particle\CriticalParticle;
use pocketmine\math\Vector3;

class Main extends PluginBase implements Listener {

    private array $lastAttackTime = [];
    /** @var bool */
    private bool $isSweeping = false;


    /** @var array<int, array{damage: float, attackSpeed: float}> */
    private array $weaponData = [
        ItemTypeIds::WOODEN_SWORD    => ["damage" => 4.0,  "attackSpeed" => 1.6],
        ItemTypeIds::STONE_SWORD     => ["damage" => 5.0,  "attackSpeed" => 1.6],
        ItemTypeIds::IRON_SWORD      => ["damage" => 6.0,  "attackSpeed" => 1.6],
        ItemTypeIds::DIAMOND_SWORD   => ["damage" => 7.0,  "attackSpeed" => 1.6],
        ItemTypeIds::GOLDEN_SWORD    => ["damage" => 4.0,  "attackSpeed" => 1.6],
        ItemTypeIds::NETHERITE_SWORD => ["damage" => 8.0,  "attackSpeed" => 1.6],

        ItemTypeIds::WOODEN_AXE      => ["damage" => 7.0,  "attackSpeed" => 1.0],
        ItemTypeIds::STONE_AXE       => ["damage" => 9.0,  "attackSpeed" => 0.9],
        ItemTypeIds::IRON_AXE        => ["damage" => 9.0,  "attackSpeed" => 0.9],
        ItemTypeIds::DIAMOND_AXE     => ["damage" => 9.0,  "attackSpeed" => 0.8],
        ItemTypeIds::GOLDEN_AXE      => ["damage" => 7.0,  "attackSpeed" => 1.0],
        ItemTypeIds::NETHERITE_AXE   => ["damage" => 10.0, "attackSpeed" => 1.0],
    ];

    /** @var array<int, float> */
    private array $sweepingEdgeData = [
        ItemTypeIds::WOODEN_SWORD    => 0.5,
        ItemTypeIds::STONE_SWORD     => 1.0,
        ItemTypeIds::IRON_SWORD      => 1.0,
        ItemTypeIds::DIAMOND_SWORD   => 1.5,
        ItemTypeIds::GOLDEN_SWORD    => 0.5,
        ItemTypeIds::NETHERITE_SWORD => 2.0,
    ];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $this->updateCooldownBar($player);
            }
        }), 2);
    }

    public function onMissSwing(PlayerMissSwingEvent $event): void {
        $player = $event->getPlayer();
        $item   = $player->getInventory()->getItemInHand();
        $typeId = $item->getTypeId();
    
        if (isset($this->weaponData[$typeId])) {
            $this->lastAttackTime[$player->getName()] = microtime(true);
        }
    }

    public function onAttack(EntityDamageByEntityEvent $event): void {
        if($this->isSweeping) {
            return;
        }
    
        $attacker = $event->getDamager();
        if (!$attacker instanceof Player) return;
    
        $item   = $attacker->getInventory()->getItemInHand();
        $typeId = $item->getTypeId();
        if (!isset($this->weaponData[$typeId])) return;
    
        $now          = microtime(true);
        $last         = $this->lastAttackTime[$attacker->getName()] ?? 0.0;
        $data         = $this->weaponData[$typeId];
        $timeElapsed  = $now - $last;
        $readyPercent = min(($now - $last) * $data["attackSpeed"], 1.0);
    
        $damage = $data["damage"] * $readyPercent;
        $event->setBaseDamage($damage);
    
        if ($readyPercent === 1.0 && isset($this->sweepingEdgeData[$typeId])) {
            $this->isSweeping = true;
            $this->performSweepingAttack($attacker, $this->sweepingEdgeData[$typeId]);
            $this->spawnSweepingEdgeParticles($attacker);
            $this->isSweeping = false;
        }
    
        if (!$attacker->isOnGround() && $attacker->getMotion()->y < 0) {
            $event->setBaseDamage($event->getBaseDamage() * 1.5);
        }
    
        $this->lastAttackTime[$attacker->getName()] = $now;
    }
    
    private function performSweepingAttack(Player $attacker, float $range): void {
        $attackerPos = $attacker->getPosition();
        $world = $attacker->getWorld();
    
        $this->isSweeping = true;
    
        foreach ($world->getEntities() as $entity) {
            if ($entity instanceof Living && $entity !== $attacker) {
                if ($entity->getPosition()->distance($attackerPos) <= $range) {
                    // Apply damage
                    $entity->attack(new EntityDamageByEntityEvent(
                        $attacker,
                        $entity,
                        EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                        5.0
                    ));
    
                    // Compute direction and apply knockback
                    $attackerVec = new Vector3($attackerPos->x, $attackerPos->y, $attackerPos->z);
                    $entityVec = new Vector3($entity->getPosition()->x, $entity->getPosition()->y, $entity->getPosition()->z);
    
                    $direction = $entityVec->subtract(
                        $attackerVec->x,
                        $attackerVec->y,
                        $attackerVec->z
                    )->normalize();
    
                    $entity->setMotion($direction->multiply(0.4)->add(0, 0.3, 0));
                }
            }
        }
    
        $this->isSweeping = false;
    }    

    private function spawnSweepingEdgeParticles(Player $attacker): void {
        $pos    = $attacker->getPosition();
        $world  = $attacker->getWorld();
        $radius = 2.0;
        for ($angle = 0; $angle < 360; $angle += 15) {
            $x = $pos->getX() + cos(deg2rad($angle)) * $radius;
            $z = $pos->getZ() + sin(deg2rad($angle)) * $radius;
            $world->addParticle(
                new Position($x, $pos->getY() + 2.0, $z, $world),
                new CriticalParticle()
            );
        }
    }

    public function onItemHeld(PlayerItemHeldEvent $event): void {
        $player = $event->getPlayer();
        $slot   = $event->getSlot();
        $item   = $event->getItem();
        $typeId = $item->getTypeId();
    
        if (isset($this->weaponData[$typeId])) {
            $this->lastAttackTime[$player->getName()] = microtime(true);
        } else {
            unset($this->lastAttackTime[$player->getName()]);
        }
    }

    private function updateCooldownBar(Player $player): void {
        $item   = $player->getInventory()->getItemInHand();
        $typeId = $item->getTypeId();
        if (!isset($this->weaponData[$typeId])) return;
    
        $data        = $this->weaponData[$typeId];
        $last        = $this->lastAttackTime[$player->getName()] ?? 0.0;
        $timeElapsed = microtime(true) - $last;
    
        $readyPercent = min($timeElapsed * $data["attackSpeed"], 1.0);
    
        if ($readyPercent >= 1.0) {
            $player->sendActionBarMessage(TextFormat::WHITE . "╺〡▬▬╾\n  ⁺");
            return;
        }
    
        $pattern    = "╺〡▬▬╾\n  ⁺";
        $totalSegs  = 6;
        $filledSegs = (int)floor($readyPercent * $totalSegs);
    
        $bar = "";
        for ($i = 0; $i < $totalSegs; $i++) {
            $char = mb_substr($pattern, $i, 1);
            if ($i < $filledSegs) {
                $bar .= TextFormat::WHITE . $char;
            } else {
                $bar .= TextFormat::GRAY . $char;
            }
        }
        
        $player->sendActionBarMessage($bar);
    }
}
