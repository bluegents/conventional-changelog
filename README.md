# Conventional Changelog

Een eenvoudige bibliotheek om een conventionele changelog te genereren op basis van conventionele commit berichten.

## Installatie

```bash
composer require bluegents/conventional-changelog
```

## Gebruik

### Basis gebruik

```bash
# Genereer een changelog voor alle commits
vendor/bin/changelog

# Of als je het globaal hebt geïnstalleerd
changelog
```

### Commando opties

```bash
# Genereer een changelog vanaf een specifieke commit of tag
vendor/bin/changelog --from=v1.0.0

# Genereer een changelog tot een specifieke commit of tag (standaard: HEAD)
vendor/bin/changelog --to=v2.0.0

# Specificeer een uitvoerbestand (standaard: CHANGELOG.md)
vendor/bin/changelog --output=HISTORY.md
# of
vendor/bin/changelog -o HISTORY.md

# Specificeer een versienummer (anders wordt het automatisch bepaald)
vendor/bin/changelog --release=1.2.0
# of
vendor/bin/changelog -r 1.2.0

# Gebruik een configuratiebestand
vendor/bin/changelog --config=changelog-config.json
# of
vendor/bin/changelog -c changelog-config.json

# Toon de uitvoer in de console in plaats van naar een bestand te schrijven
vendor/bin/changelog --dry-run

# Genereer een changelog voor meerdere releases (volledige geschiedenis)
vendor/bin/changelog --multi-release
# of
vendor/bin/changelog -m
```

### Configuratie

Je kunt een JSON-configuratiebestand maken om het gedrag van de changelog generator aan te passen:

```json
{
  "types": ["feat", "fix", "docs", "style", "refactor", "perf", "test", "build", "ci", "chore"],
  "show_breaking": true,
  "output_file": "CHANGELOG.md"
}
```

- `types`: Een array van commit types die in de changelog moeten worden opgenomen (elk type wordt weergegeven met een bijpassend icoon)
- `show_breaking`: Of breaking changes apart moeten worden weergegeven (boolean)
- `output_file`: Het pad naar het uitvoerbestand

### Conventionele commits

Deze tool werkt met [conventionele commits](https://www.conventionalcommits.org/). Commit berichten moeten de volgende structuur hebben:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

Bijvoorbeeld:
```
feat(api): voeg nieuwe endpoint toe voor gebruikersauthenticatie

- Implementeert JWT authenticatie
- Voegt refresh tokens toe

BREAKING CHANGE: verwijdert de oude authenticatiemethode
```

## Git Workflow en Branches

### Welke branch gebruiken voor changelog generatie

Bij het gebruik van verschillende Git workflows is het belangrijk om de juiste branch te gebruiken voor het genereren van een changelog:

#### Git Flow (main, develop, feature, release, hotfix, support)
- **main/master branch**: Gebruik deze branch voor het genereren van changelogs voor officiële releases. Deze branch bevat de stabiele, productie-klare code.
- **release branches**: Gebruik deze branches voor het genereren van changelogs voor aankomende releases tijdens de testfase.
- **develop branch**: Gebruik deze branch voor het genereren van changelogs voor interne ontwikkelversies.
- **feature/hotfix/support branches**: Deze branches zijn meestal niet geschikt voor het genereren van changelogs, omdat ze vaak onvolledige of tijdelijke wijzigingen bevatten.

#### Eenvoudigere workflows
- **Bij main en develop**: Gebruik main voor officiële releases en develop voor interne ontwikkelversies.
- **Bij main, develop en feature**: Gebruik main voor officiële releases, develop voor interne ontwikkelversies, en genereer geen changelogs van feature branches.

### Praktisch advies
1. **Zorg dat je op de juiste branch staat**: Controleer altijd eerst op welke branch je staat met `git branch` voordat je een changelog genereert.
2. **Voor officiële releases**: Genereer changelogs altijd vanaf de main/master branch.
3. **Voor ontwikkelversies**: Genereer changelogs vanaf de develop branch om wijzigingen te documenteren die nog niet in productie zijn.
4. **Gebruik tags**: Maak gebruik van tags om releases te markeren en gebruik deze tags bij het genereren van changelogs met de `--from` en `--to` opties.

## Testen

### Normale tests uitvoeren

```bash
composer test
```

## Code kwaliteitstools

### Laravel Pint

Laravel Pint is een PHP code style fixer gebaseerd op PHP-CS-Fixer. Het wordt gebruikt om de code stijl consistent te houden.

```bash
# Code stijl controleren zonder wijzigingen aan te brengen
composer pint:test

# Code stijl corrigeren
composer pint
```
