# OTRP API Dokumentace

## Autentizace

Všechny endpointy vyžadují API klíč v hlavičce:

```
X-API-Key: <váš_api_klíč>
```

API klíče se spravují v **Management → API Klíče** (`/management/api-keys`).  
Každý klíč může mít nastavené povolené IP adresy — pokud požadavek přijde z jiné IP, vrátí se `403`.

### Chybové odpovědi autentizace

| HTTP kód | Odpověď | Popis |
|----------|---------|-------|
| `401` | `{"error": "Missing API key"}` | Chybí hlavička `X-API-Key` |
| `401` | `{"error": "Invalid API key"}` | Klíč neexistuje nebo je neaktivní |
| `403` | `{"error": "IP not allowed"}` | IP adresa volajícího není v povoleném seznamu |

---

## Endpointy

---

### 1. Access check

Zkontroluje, zda má hráč přístup na konkrétní server. Vrací QP a počet slotů postav.

Na **POST** navíc synchronizuje FiveM identifikátory hráče (license, steam, apod.) a detekuje multi-accounty.

```
GET  /api/access/:server/:discord_id
POST /api/access/:server/:discord_id
```

**Parametry URL:**

| Parametr | Typ | Povolené hodnoty | Popis |
|----------|-----|------------------|-------|
| `server` | string | `main`, `dev`, `maps` | Název serveru |
| `discord_id` | string | | Discord snowflake ID hráče |

**Request body (pouze POST, JSON):**

```json
{
  "identifiers": {
    "license": "license:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "license2": "license:yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
    "steam": "steam:110000xxxxxxxxx",
    "fivem": "fivem:xxxxxxx",
    "discord": "discord:123456789012345678",
    "ip": "ip:1.2.3.4"
  }
}
```

**Odpověď (200):**

```json
{
  "discord_id": "123456789012345678",
  "server": "main",
  "allowed": true,
  "reason": null,
  "qp": 1500,
  "chars": 3,
  "ped": true
}
```

| Pole | Typ | Popis |
|------|-----|-------|
| `qp` | int | Počet QP (QuePoints) hráče |
| `chars` | int | Počet slotů postav hráče |
| `ped` | bool | Má přístup k ped menu |

**Možné hodnoty `reason` (pokud `allowed: false`):**

| Hodnota | Popis |
|---------|-------|
| `not_found` | Hráč neexistuje v systému |
| `blacklisted` | Hráč je na denylistu |
| `banned` | Hráč má aktivní ban (+ `expires_at`) |
| `no_allowlist` | Nemá platný allowlist |

**Příklad — hráč s banem:**

```json
{
  "discord_id": "123456789012345678",
  "server": "main",
  "allowed": false,
  "reason": "banned",
  "expires_at": "2026-05-01 00:00:00",
  "qp": 500,
  "chars": 2,
  "ped": false
}
```

**Vedlejší efekty (pouze POST):**
- Nové identifikátory se uloží do DB
- Změna identifikátoru (např. nový license) se zaloguje do security logů
- Pokud jiný hráč sdílí stejný identifikátor → security log s úrovní `critical` (multi-account detekce)

**Chyby:**

| HTTP kód | Odpověď |
|----------|---------|
| `400` | `{"error": "Invalid server. Use: main, dev, maps"}` |
| `400` | `{"error": "Missing discord_id"}` |

---

### 2. Výhody hráče

Vrátí všechny výhody hráče (QP, sloty postav, RP pedy).

```
GET /api/benefits/:discord_id
```

**Parametry URL:**

| Parametr | Typ | Popis |
|----------|-----|-------|
| `discord_id` | string | Discord snowflake ID hráče |

**Odpověď (200):**

```json
{
  "discord_id": "123456789012345678",
  "qp": 2350,
  "chars": 3,
  "ped": true
}
```

| Pole | Typ | Popis |
|------|-----|-------|
| `qp` | int | Počet QP (QuePoints) |
| `chars` | int | Počet slotů postav |
| `ped` | bool | Má přístup k ped menu |

**Chyby:**

| HTTP kód | Odpověď |
|----------|---------|
| `400` | `{"error": "Missing discord_id"}` |

---

### 3. Oprávnění hráče

Vrátí admin, management a in-game oprávnění hráče.

```
GET /api/permissions/:discord_id
```

**Parametry URL:**

| Parametr | Typ | Popis |
|----------|-----|-------|
| `discord_id` | string | Discord snowflake ID hráče |

**Odpověď (200):**

```json
{
  "discord_id": "123456789012345678",
  "is_admin": true,
  "is_management": true,
  "ingame_admin": true,
  "ingame_management": false,
  "admin_permissions": {
    "admin.allowlist": true,
    "admin.allowlist.reinterview": false,
    "admin.players": true,
    "admin.players.punishments": true,
    "admin.players.access": false,
    "admin.players.appeals": false,
    "admin.ck": false,
    "admin.activity": false,
    "admin.vacation": false,
    "admin.security": false,
    "admin.qp_bonus": false,
    "admin.char_bonus": false
  },
  "management_permissions": {
    "management.form": true,
    "management.content": true,
    "management.rules": false,
    "management.blacklist": false,
    "management.appeals": false,
    "management.team": false,
    "management.cheatsheet": false,
    "management.partners": false,
    "management.homepage": false,
    "management.allowlist_stats": false,
    "management.qp": false,
    "management.chars": false,
    "management.codes": false,
    "management.api_keys": false,
    "management.settings": false
  },
  "admin_settings": {
    "admin_prefix_chat": true,
    "report_notifications": true
  }
}
```

| Pole | Typ | Popis |
|------|-----|-------|
| `is_admin` | bool | `true` pokud má alespoň jedno admin oprávnění |
| `is_management` | bool | `true` pokud má alespoň jedno management oprávnění |
| `ingame_admin` | bool | Má oprávnění `ingame.admin` (In Game admin práva) |
| `ingame_management` | bool | Má oprávnění `ingame.management` (In Game management práva) |
| `admin_settings.admin_prefix_chat` | bool | Zda má zapnutý admin prefix v chatu |
| `admin_settings.report_notifications` | bool | Zda má zapnutá oznámení o reportech |

> Vedení role mají automaticky **vše true**.

---

### 4. Vygenerovat kód

Vygeneruje nový vykupitelný kód (QP nebo sloty postav). Určeno pro Tebex nebo jiné externí integrace.

```
POST /api/codes
```

**Request body (JSON):**

```json
{
  "type": "qp",
  "amount": 500,
  "max_uses": 1,
  "note": "Tebex order #123",
  "expires_at": "2026-12-31 23:59:59"
}
```

| Pole | Typ | Povinné | Popis |
|------|-----|---------|-------|
| `type` | string | ✅ | `"qp"`, `"chars"` nebo `"ped"` (ped: amount se ignoruje) |
| `amount` | int | ✅ | Hodnota (musí být > 0) |
| `max_uses` | int | ❌ | Počet použití (výchozí: 1) |
| `note` | string | ❌ | Poznámka (max 255 znaků) |
| `expires_at` | string\|null | ❌ | Do kdy platí výhoda po uplatnění (`YYYY-MM-DD HH:MM:SS`), nebo `null` = trvalé |

**Odpověď (201):**

```json
{
  "code": "AB3K-XY7N-QW4R"
}
```

**Chyby:**

| HTTP kód | Odpověď |
|----------|---------|
| `400` | `{"error": "Invalid JSON body"}` |
| `400` | `{"error": "type must be \"qp\", \"chars\" or \"ped\""}` |
| `400` | `{"error": "amount must be a positive integer"}` |
| `400` | `{"error": "Invalid expires_at format"}` |
| `500` | `{"error": "Internal server error"}` |

---

### 5. Aktualizovat nastavení admina

Aktualizuje osobní nastavení admina (prefix v chatu, oznámení o reportech).

```
POST /api/settings/:discord_id
```

**Request body (JSON):**

```json
{
  "admin_prefix_chat": false,
  "report_notifications": true
}
```

| Pole | Typ | Povinné | Popis |
|------|-----|---------|-------|
| `admin_prefix_chat` | bool | ❌ | Zapnout/vypnout admin prefix v chatu |
| `report_notifications` | bool | ❌ | Zapnout/vypnout oznámení o reportech |

> Pole, která nejsou v requestu uvedena, zůstávají nezměněna.

**Odpověď (200):**

```json
{
  "discord_id": "123456789",
  "admin_prefix_chat": false,
  "report_notifications": true
}
```

**Chyby:**

| HTTP kód | Odpověď |
|----------|---------||
| `400` | `{"error": "Invalid JSON body"}` |
| `404` | `{"error": "User not found"}` |
| `500` | `{"error": "Internal server error"}` |

---

## Přehled endpointů

| Metoda | Endpoint | Popis |
|--------|----------|-------|
| `GET/POST` | `/api/access/:server/:discord_id` | Kontrola přístupu + sync identifikátorů (POST) |
| `GET` | `/api/benefits/:discord_id` | Výhody hráče (QP, chars, ped) |
| `GET` | `/api/permissions/:discord_id` | Admin/management/in-game oprávnění |
| `POST` | `/api/codes` | Vygenerovat vykupitelný kód |
| `POST` | `/api/settings/:discord_id` | Aktualizovat nastavení admina |
