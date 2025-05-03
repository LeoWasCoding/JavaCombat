# JavaCombat

**JavaCombat** brings authentic Java Edition-style combat mechanics to your PocketMine-MP server.

> *The one and only Java-style combat system for PocketMine.*

---

## Features

* ✅ Java-style **attack cooldowns** based on weapon type
* ✅ Support for swords & axes with **custom damage and attack speed**
* ✅ **Sweeping edge** AOE attacks when cooldown is maxed
* ✅ Aesthetic **cooldown bar** shown via action bar
* ✅ Particle effects during sweeping attacks
* ✅ Java like **critical damage** when hitting mid-air
* ✅ Fully optimized and clean codebase

---

## Supported Weapons

| Weapon          | Base Damage | Attack Speed | Sweeping Edge |
| --------------- | ----------- | ------------ | ------------- |
| Wooden Sword    | 4.0         | 1.6          | 0.5           |
| Stone Sword     | 5.0         | 1.6          | 1.0           |
| Iron Sword      | 6.0         | 1.6          | 1.0           |
| Diamond Sword   | 7.0         | 1.6          | 1.5           |
| Golden Sword    | 4.0         | 1.6          | 0.5           |
| Netherite Sword | 8.0         | 1.6          | 2.0           |
| Wooden Axe      | 7.0         | 1.0          | ❌             |
| Stone Axe       | 9.0         | 0.9          | ❌             |
| Iron Axe        | 9.0         | 0.9          | ❌             |
| Diamond Axe     | 9.0         | 0.8          | ❌             |
| Golden Axe      | 7.0         | 1.0          | ❌             |
| Netherite Axe   | 10.0        | 1.0          | ❌             |

---

## Installation

1. Download the plugin `.phar` or compile from source.
2. Place it in the `plugins/` directory of your PocketMine server.
3. Start or restart the server.

---

## Cooldown Indicator

Players receive a visual indicator in their action bar representing their current attack readiness. This mimics Java Edition's sword recharge mechanic.

* Full bar = Full damage + sweeping attack (if using a sword)
* Partial bar = Scaled-down damage

---

## Developer Info

* **Plugin Name:** JavaCombat
* **Author:** `LeoWasCoding`
* **API Version:** PocketMine-MP 5.x
* **License:** MIT

---

## Notes

* Only melee weapons listed above have cooldown effects.
* Players must be **on the ground** for sweeping attacks to trigger.
* **Critical hits** apply when attacking midair with downward motion.

---

## Contributing

issues and suggestions are much appreciated!

---
