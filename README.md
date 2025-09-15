# Instrukcja instalacji local_aitools + aitoolsub_valuemapdoc

## Nowe funkcjonalności v1.1

### 🎯 Kontrola dostępu przez kohorty
- **Segmentacja użytkowników** - różne narzędzia dla różnych grup
- **Pilotażowe wdrożenia** - testowanie z wybranymi użytkownikami
- **Licencjonowanie** - premium narzędzia dla płatnych kont
- **Postupne rollout** - kontrolowane wprowadzanie funkcjonalności

### 🔧 Jak to działa
1. **Brak kohort** = dostęp dla wszystkich użytkowników
2. **Przypisane kohorty** = dostęp tylko dla członków tych kohort
3. **Wystarczy jedna** = użytkownik musi być w co najmniej jednej kohorcie

## Migracja z wersji 1.0

### Automatyczna migracja
- Istniejące instalacje będą działać bez zmian
- Domyślnie wszyscy użytkownicy mają dostęp (brak kohort = brak ograniczeń)
- Można postupnie wprowadzać ograniczenia kohortowe

### Zalecane kroki po aktualizacji
1. **Uaktualnij** plugin do wersji 1.1
2. **Przetestuj** czy wszystko działa jak wcześniej
3. **Utwórz kohorty** dla grup użytkowników
4. **Stopniowo wprowadź** ograniczenia kohortowe
5. **Monitoruj** dostęp i feedback użytkowników

## Przykłady implementacji

### Scenariusz 1: Pilotaż przed pełnym wdrożeniem
```php
// 1. Utwórz kohortę pilotażową
$pilot_cohort = new stdClass();
$pilot_cohort->name = 'ValueMapDoc Pilot';
$pilot_cohort->idnumber = 'vmd_pilot_2025';
$pilot_cohort->description = 'Grupa pilotażowa do testowania ValueMapDoc AI Tools';

// 2. Dodaj wybranych użytkowników do kohorty
// (przez interfejs lub API)

// 3. Skonfiguruj ograniczenie
cohort_manager::add_cohort_restriction('aitoolsub_valuemapdoc', $pilot_cohort->id);
```

### Scenariusz 2: Dostęp według działów
```php
// Różne kohorty dla różnych działów
$sales_cohort = 'Sales Team';           // ID: 1
$marketing_cohort = 'Marketing Team';   // ID: 2
$leadership_cohort = 'Leadership';      // ID: 3

// ValueMapDoc tylko dla sprzedaży i leadership
cohort_manager::add_cohort_restriction('aitoolsub_valuemapdoc', 1);
cohort_manager::add_cohort_restriction('aitoolsub_valuemapdoc', 3);

// Hipotetyczny plugin analytics dla wszystkich działów
cohort_manager::add_cohort_restriction('aitoolsub_analytics', 1);
cohort_manager::add_cohort_restriction('aitoolsub_analytics', 2);
cohort_manager::add_cohort_restriction('aitoolsub_analytics', 3);
```

### Scenariusz 3: Model freemium
```php
// Podstawowe narzędzia dla wszystkich (brak ograniczeń)
// Premium narzędzia tylko dla płacących

$premium_cohort = get_cohort_by_idnumber('premium_users');
cohort_manager::add_cohort_restriction('aitoolsub_premium_ai', $premium_cohort->id);

// Sprawdzenie w kodzie narzędzia
if (!cohort_manager::has_cohort_access('aitoolsub_premium_ai')) {
    show_upgrade_prompt();
    return;
}
```

## API Documentation

### Cohort Manager Class

#### Podstawowe metody
```php
// Sprawdź dostęp użytkownika
bool has_cohort_access(string $subplugin, int $userid = 0)

// Dodaj ograniczenie kohortowe
bool add_cohort_restriction(string $subplugin, int $cohortid)

// Usuń ograniczenie kohortowe  
bool remove_cohort_restriction(string $subplugin, int $cohortid)

// Wyczyść wszystkie ograniczenia
bool clear_cohort_restrictions(string $subplugin)
```

#### Metody informacyjne
```php
// Pobierz kohorty przypisane do subpluginu
array get_subplugin_cohorts(string $subplugin)

// Pobierz wszystkie dostępne kohorty
array get_all_cohorts()

// Pobierz statystyki dostępu
array get_access_statistics(string $subplugin)

// Pobierz użytkowników z dostępem
array get_users_with_access(string $subplugin, int $limit = 0)
```

#### Przykład użycia w subpluginie
```php
class plugin implements \local_aitools\plugin_interface {
    
    public function has_access() {
        global $USER;
        
        // Sprawdź podstawowe uprawnienia
        if (!has_capability('mod/valuemapdoc:view', context_system::instance())) {
            return false;
        }
        
        // Sprawdzenie kohort jest automatyczne w manager::get_plugins()
        // ale można dodać tutaj dodatkową logikę
        
        return true;
    }
    
    public function get_dashboard_blocks() {
        // Kohorty są już sprawdzone, więc tu zwracamy bloki normalnie
        return [...];
    }
}
```

## Monitorowanie i analytics

### Metryki do śledzenia
1. **Adoption rate** - % użytkowników z dostępem, którzy używają narzędzia
2. **Usage frequency** - jak często każda kohorta używa narzędzi
3. **Feature usage** - które funkcje są najczęściej używane przez kohortę
4. **Feedback scores** - oceny użytkowników z różnych kohort

### Przykładowe zapytania SQL
```sql
-- Liczba użytkowników z dostępem do subpluginu
SELECT COUNT(DISTINCT cm.userid) as users_with_access
FROM {cohort_members} cm
JOIN {local_aitools_cohorts} ac ON ac.cohortid = cm.cohortid
WHERE ac.subplugin = 'aitoolsub_valuemapdoc';

-- Top 5 kohort według liczby użytkowników
SELECT c.name, COUNT(cm.userid) as member_count
FROM {cohort} c
JOIN {cohort_members} cm ON cm.cohortid = c.id
JOIN {local_aitools_cohorts} ac ON ac.cohortid = c.id
WHERE ac.subplugin = 'aitoolsub_valuemapdoc'
GROUP BY c.id, c.name
ORDER BY member_count DESC
LIMIT 5;
```

## Security considerations

### Bezpieczeństwo dostępu
1. **Walidacja uprawnień** - zawsze sprawdzaj podstawowe capabilities
2. **Podwójna weryfikacja** - kohorty + standardowe uprawnienia Moodle
3. **Logging dostępu** - loguj próby dostępu dla audytu
4. **Cache invalidation** - wyczyść cache po zmianach kohort

### Zalecenia implementacyjne
```php
// DOBRE - podwójna weryfikacja
public function has_access() {
    // 1. Podstawowe uprawnienia
    if (!has_capability('mod/valuemapdoc:view', context_system::instance())) {
        return false;
    }
    
    // 2. Dodatkowe sprawdzenia (kohorty sprawdza manager)
    return true;
}

// ZŁYPRZYJĄŁEM - tylko kohorty
public function has_access() {
    // To jest sprawdzane automatycznie - nie implementuj tutaj
    return cohort_manager::has_cohort_access('aitoolsub_valuemapdoc');
}
```

## Troubleshooting Guide

### Problem: Użytkownik nie widzi narzędzi mimo uprawnień
```php
// Debug script - dodaj do rozwoju
$userid = 123;
$subplugin = 'aitoolsub_valuemapdoc';

echo "=== Debug cohort access ===\n";
echo "User ID: $userid\n";
echo "Subplugin: $subplugin\n";

// Sprawdź podstawowe uprawnienia
$contexts = get_contexts_with_capability_for_user($userid, 'mod/valuemapdoc:view');
echo "Has basic capability: " . (!empty($contexts) ? 'YES' : 'NO') . "\n";

// Sprawdź kohorty użytkownika
$user_cohorts = $DB->get_records('cohort_members', ['userid' => $userid]);
echo "User cohorts: " . implode(', ', array_keys($user_cohorts)) . "\n";

// Sprawdź wymagane kohorty
$required_cohorts = cohort_manager::get_subplugin_cohorts($subplugin);
echo "Required cohorts: " . implode(', ', array_keys($required_cohorts)) . "\n";

// Sprawdź dostęp
$has_access = cohort_manager::has_cohort_access($subplugin, $userid);
echo "Has cohort access: " . ($has_access ? 'YES' : 'NO') . "\n";
```

### Problem: Performance przy dużej liczbie kohort
```php
// Optymalizacja - cache wyników
class cohort_manager {
    private static $access_cache = [];
    
    public static function has_cohort_access($subplugin, $userid = 0) {
        $cache_key = $subplugin . '_' . $userid;
        
        if (isset(self::$access_cache[$cache_key])) {
            return self::$access_cache[$cache_key];
        }
        
        $result = self::calculate_access($subplugin, $userid);
        self::$access_cache[$cache_key] = $result;
        
        return $result;
    }
}
```

## Roadmap funkcjonalności

### Planowane rozszerzenia v1.2
- **Czasowe ograniczenia** - dostęp w określonym czasie
- **Quota użytkowników** - limit użytkowników per kohorta
- **Hierarchiczne kohorty** - dziedziczenie dostępu
- **Integration z external systems** - LDAP, SSO

### Planowane rozszerzenia v1.3
- **Advanced analytics** - szczegółowe raporty użycia
- **A/B testing framework** - różne wersje dla różnych kohort
- **Notification system** - powiadomienia o dostępie
- **Bulk operations** - masowe operacje na kohortach

## Best Practices

### 1. Naming conventions
```php
// Kohorty
'vmd_pilot_2025'           // valuemapdoc pilot
'sales_team_emea'          // regional sales team  
'premium_users_tier1'      // premium tier 1

// Subpluginy
'aitoolsub_valuemapdoc'    // prefix + plugin name
'aitoolsub_analytics'      // consistent naming
```

### 2. Lifecycle management
```php
// Rozpoczęcie pilotażu
1. Utwórz kohortę pilotażową (małą grupę)
2. Dodaj ograniczenie kohortowe
3. Monitoruj użycie i feedback
4. Stopniowo rozszerzaj grupę

// Końcowe wdrożenie
1. Dodaj wszystkich użytkowników do kohorty
2. Monitoruj performance
3. Usuń ograniczenia kohortowe (= dostęp dla wszystkich)
4. Usuń niepotrzebne kohorty
```

### 3. Communication strategy
```
Przed wprowadzeniem ograniczeń:
- Powiadom użytkowników o zmianie
- Wyjaśnij powody ograniczeń
- Podaj timeline pełnego wdrożenia
- Zapewnij kanał feedback'u

Po wprowadzeniu:
- Monitoruj reakcje użytkowników
- Zbieraj feedback
- Komunikuj progress i plany
- Szybko reaguj na problemy
```

## Wsparcie

### Kontakt
- **Issues**: GitHub issues w repozytorium mod_valuemapdoc
- **Documentation**: Wiki w repozytorium
- **Community**: Forum Moodle - Local plugins

### Wkład w rozwój
1. Fork repozytorium
2. Utwórz feature branch
3. Zaimplementuj funkcjonalność
4. Dodaj testy
5. Stwórz pull request

---

**Aktualizacja**: Instrukcja dla local_aitools v1.1.0 z obsługą kohort  
**Data**: 15 września 2025  
**Compatibility**: Moodle 4.0+, mod_valuemapdoc 2025080106+ji local_aitools + aitoolsub_valuemapdoc

## Nowe funkcjonalności v1.1

### 🎯 Kontrola dostępu przez kohorty
- **Segmentacja użytkowników** - różne narzędzia dla różnych grup
- **Pilotażowe wdrożenia** - testowanie z wybranymi użytkownikami
- **Licencjonowanie** - premium narzędzia dla płatnych kont
- **Postupne rollout** - kontrolowane wprowadzanie funkcjonalności

### 🔧 Jak to działa
1. **Brak kohort** = dostęp dla wszystkich użytkowników
2. **Przypisane kohorty** = dostęp tylko dla członków tych kohort
3. **Wystarczy jedna** = użytkownik musi być w co najmniej jednej kohorcie

## Struktura plików

Kompletna implementacja składa się z dwóch pluginów:

### 1. Główny plugin: `local_aitools`
```
/local/aitools/
├── version.php                          # Konfiguracja wersji + kohorty
├── lib.php                             # Hooks nawigacji  
├── index.php                           # Dashboard AI Tools
├── settings.php                        # Ustawienia administratora
├── db/
│   ├── subplugins.php                  # Rejestracja subpluginów
│   ├── access.php                      # Uprawnienia
│   ├── install.xml                     # Tabela kohort
│   └── upgrade.php                     # Aktualizacje DB
├── admin/
│   └── cohorts.php                     # Zarządzanie kohortami
├── classes/
│   ├── plugin_interface.php           # Interface dla subpluginów
│   ├── manager.php                     # Zarządca subpluginów + kohorty
│   ├── cohort_manager.php              # Zarządca kohort
│   └── output/
│       └── renderer.php                # Renderer główny
├── templates/
│   ├── dashboard.mustache              # Szablon dashboardu
│   └── cohort_management.mustache      # Zarządzanie kohortami
├── lang/en/
│   └── local_aitools.php              # Stringi językowe + kohorty
├── amd/src/
│   └── dashboard.js                    # JavaScript dashboardu
└── styles/
    └── dashboard.css                   # Style dashboardu
```

### 2. Subplugin: `aitoolsub_valuemapdoc`
```
/local/aitools/plugins/valuemapdoc/
├── version.php                         # Konfiguracja subpluginu
├── classes/
│   ├── plugin.php                      # Implementacja interface + kohorty
│   ├── external/
│   │   └── get_user_content_global.php # Serwis AJAX
│   └── output/
│       └── renderer.php               # Renderer subpluginu
├── templates/
│   ├── dashboard_summary.mustache     # Blok podsumowania
│   ├── quick_stats.mustache          # Blok statystyk
│   └── my_content.mustache           # Strona treści
├── lang/en/
│   └── aitoolsub_valuemapdoc.php     # Stringi subpluginu
├── db/
│   └── services.php                   # Serwisy AJAX
├── amd/src/
│   └── content_manager.js            # JavaScript managera treści
├── styles/
│   └── content.css                   # Style treści
└── my_content.php                    # Główne narzędzie treści
```

## Kroki instalacji

### Krok 1: Instalacja głównego pluginu
1. Skopiuj folder `local_aitools/` do `[moodle]/local/aitools/`
2. Przejdź do **Administracja witryny → Powiadomienia**
3. Kliknij **Uaktualnij bazę danych Moodle**
4. Zostanie utworzona tabela `local_aitools_cohorts`

### Krok 2: Instalacja subpluginu ValueMapDoc
1. Skopiuj folder `valuemapdoc/` do `[moodle]/local/aitools/plugins/valuemapdoc/`
2. Przejdź ponownie do **Administracja witryny → Powiadomienia**
3. Kliknij **Uaktualnij bazę danych Moodle**

### Krok 3: Konfiguracja dostępu przez kohorty
1. Przejdź do **Administracja witryny → Pluginy → Local plugins → AI Tools Dashboard**
2. Wybierz subplugin (np. **ValueMapDoc**)
3. Kliknij **"Configure Cohort Access"**
4. Dodaj kohorty które mają mieć dostęp do narzędzia

### Krok 4: Weryfikacja uprawnień
1. Przejdź do **Administracja witryny → Użytkownicy → Uprawnienia → Zdefiniuj role**
2. Sprawdź czy uprawnienia `local/aitools:view` są przypisane odpowiednim rolom
3. Sprawdź czy `local/aitools:manage` jest przypisane do administratorów

### Krok 5: Czyszczenie cache
1. **Administracja witryny → Rozwój → Purge caches**
2. Lub użyj CLI: `php admin/cli/purge_caches.php`

## 🎛️ Zarządzanie kohortami

### Dostęp do ustawień
**Administracja witryny → Pluginy → Local plugins → AI Tools Dashboard → Subplugins Management**

### Scenariusze użycia

#### 1. Pilotażowe wdrożenie
```
Kohorta: "Pilot ValueMapDoc"
Członkowie: 10 wybranych sprzedawców
Cel: Testowanie funkcjonalności przed pełnym wdrożeniem
```

#### 2. Dostęp działowy
```
Kohorta: "Sales Team"
Członkowie: Wszyscy sprzedawcy
Cel: Narzędzia sprzedażowe tylko dla zespołu sprzedaży
```

#### 3. Licencjonowanie premium
```
Kohorta: "Premium Users"
Członkowie: Użytkownicy z płatnym kontem
Cel: Zaawansowane narzędzia AI dla płacących klientów
```

#### 4. Postupne wprowadzanie
```
Tydzień 1: Kohorta "Early Adopters" (20 osób)
Tydzień 3: Kohorta "Department Leaders" (50 osób)  
Tydzień 6: Kohorta "All Sales" (200 osób)
```

## Dostęp do narzędzi

### Główne menu
Po instalacji w głównej nawigacji Moodle pojawi się link **"AI Tools"** (tylko dla uprawnionych)

### Bezpośredni dostęp
- Dashboard AI Tools: `[twoja-domena]/local/aitools/`
- Moje treści ValueMapDoc: `[twoja-domena]/local/aitools/plugins/valuemapdoc/my_content.php`

## Funkcjonalności

### Dashboard AI Tools
- **Statystyki z kohortami** - liczba użytkowników z dostępem
- **Bloki dashboardu** - tylko od dostępnych subpluginów
- **Katalog narzędzi** - filtrowany według dostępu kohort
- **Zarządzanie kohortami** - dla administratorów

### Kontrola dostępu przez kohorty
- **Automatyczne filtrowanie** - użytkownicy widzą tylko dostępne narzędzia
- **Elastyczna konfiguracja** - różne kohorty dla różnych subpluginów
- **Statystyki dostępu** - podgląd liczby użytkowników z dostępem
- **Łatwe zarządzanie** - dodawanie/usuwanie kohort przez interfejs

### Narzędzie "Moje treści"
- **Widok globalny** - wszystkie treści użytkownika z całej platformy
- **Grupowanie** - według kursów → aktywności → dokumenty
- **Wyszukiwanie** - po nazwach i treści dokumentów
- **Filtrowanie** - po typach szablonów i statusach
- **Akcje** - podgląd i edycja dokumentów
- **Statystyki** - podsumowanie aktivności

## Wymagania

### Moodle
- **Wersja**: 4.0+ (2022041900)
- **PHP**: 7.4+

### Zależności
- `mod_valuemapdoc` w wersji 2025080106 lub nowszej
- `local_aitools` w wersji 2025091501 lub nowszej (z obsługą kohort)

## Rozwiązywanie problemów

### Plugin nie pojawia się w menu
1. Sprawdź uprawnienia użytkownika (`local/aitools:view`)
2. **NOWE**: Sprawdź czy użytkownik należy do odpowiedniej kohorty
3. Wyczyść cache Moodle
4. Sprawdź czy użytkownik jest zalogowany

### Użytkownik nie widzi konkretnego narzędzia
1. **Sprawdź kohorty**: Czy użytkownik należy do kohorty z dostępem?
2. W ustawieniach subpluginu sprawdź przypisane kohorty
3. Sprawdź membership użytkownika w **Administracja → Użytkownicy → Kohorty**

### Błąd "Class not found"
1. Sprawdź czy wszystkie pliki zostały skopiowane
2. Uruchom `php admin/cli/purge_caches.php`
3. Sprawdź logi błędów w `[moodle]/admin/tool/log/`

### Brak treści w dashboardzie
1. Sprawdź czy użytkownik ma treści w mod_valuemapdoc
2. **NOWE**: Sprawdź dostęp przez kohorty do subpluginu
3. Sprawdź uprawnienia do kursów z aktywnościami valuemapdoc
4. Sprawdź logi w **Administracja witryny → Raporty → Logi**

### Problemy z AJAX
1. Sprawdź czy serwisy są zarejestrowane w `db/services.php`
2. Sprawdź uprawnienia do wywoływania serwisów
3. Sprawdź konsole przeglądarki (F12)

### Problemy z kohortami
1. Sprawdź czy tabela `local_aitools_cohorts` została utworzona
2. Sprawdź czy kohorty istnieją w **Administracja → Użytkownicy → Kohorty**
3. Sprawdź membership użytkownika w kohortach
4. Sprawdź logi dostępu w kodzie (dodaj `error_log()` w `cohort_manager.php`)

## Rozwój i rozszerzenia

### Dodawanie nowych subpluginów z obsługą kohort
1. Utwórz folder w `/local/aitools/plugins/[nazwa]/`
2. Implementuj `\local_aitools\plugin_interface`
3. W `has_access()` sprawdź podstawowe uprawnienia (kohorty sprawdza manager)
4. Zarejestruj w `version.php` z prefiksem `aitoolsub_`

### Dodawanie nowych bloków dashboardu
W metodzie `get_dashboard_blocks()` subpluginu:
```php
return [
    'block_key' => [
        'title' => 'Tytuł bloku',
        'template' => 'nazwa_template',
        'weight' => 10,          // Kolejność
        'size' => 'large',       // large/medium/small
        'data' => [...]          // Dane dla template
    ]
];
```

### Dodawanie nowych narzędzi
W metodzie `get_tools()` subpluginu:
```php
return [
    'tool_key' => [
        'title' => 'Nazwa narzędzia',
        'description' => 'Opis narzędzia',
        'url' => '/path/to/tool.php',
        'icon' => 'fa-icon-name',
        'category' => 'sales'     // sales/content/analytics/general
    ]
];
```

### Programowe zarządzanie kohortami
```php
use local_aitools\cohort_manager;

// Sprawdź dostęp
$has_access = cohort_manager::has_cohort_access('aitoolsub_valuemapdoc', $userid);

// Dodaj ograniczenie
cohort_manager::add_cohort_restriction('aitoolsub_valuemapdoc', $cohortid);

// Usuń ograniczenie
cohort_manager::remove_cohort_restriction('aitoolsub_valuemapdoc', $cohortid);

// Wyczyść wszystkie ograniczenia (= dostęp dla wszystkich)
cohort_manager::clear_cohort_restrictions('aitoolsub_valuemapdoc');

// Pobierz statystyki
$stats = cohort_manager::get_access_statistics('aitoolsub_valuemapdoc');
```

## Wsparcie

W przypadku problemów:
1. Sprawdź logi Moodle
2. Włącz debug mode: `$CFG->debug = E_ALL; $CFG->debugdisplay = 1;`
3. **NOWE**: Sprawdź kohorty użytkownika i subpluginu
4. Skontaktuj się z zespołem deweloperskim mod_valuemapdocja instalacji local_aitools + aitoolsub_valuemapdoc

## Struktura plików

Kompletna implementacja składa się z dwóch pluginów:

### 1. Główny plugin: `local_aitools`
```
/local/aitools/
├── version.php                          # Konfiguracja wersji
├── lib.php                             # Hooks nawigacji  
├── index.php                           # Dashboard AI Tools
├── db/
│   ├── subplugins.php                  # Rejestracja subpluginów
│   └── access.php                      # Uprawnienia
├── classes/
│   ├── plugin_interface.php           # Interface dla subpluginów
│   ├── manager.php                     # Zarządca subpluginów
│   └── output/
│       └── renderer.php                # Renderer główny
├── templates/
│   └── dashboard.mustache              # Szablon dashboardu
├── lang/en/
│   └── local_aitools.php              # Stringi językowe
├── amd/src/
│   └── dashboard.js                    # JavaScript dashboardu
└── styles/
    └── dashboard.css                   # Style dashboardu
```

### 2. Subplugin: `aitoolsub_valuemapdoc`
```
/local/aitools/plugins/valuemapdoc/
├── version.php                         # Konfiguracja subpluginu
├── classes/
│   ├── plugin.php                      # Implementacja interface
│   ├── external/
│   │   └── get_user_content_global.php # Serwis AJAX
│   └── output/
│       └── renderer.php               # Renderer subpluginu
├── templates/
│   ├── dashboard_summary.mustache     # Blok podsumowania
│   ├── quick_stats.mustache          # Blok statystyk
│   └── my_content.mustache           # Strona treści
├── lang/en/
│   └── aitoolsub_valuemapdoc.php     # Stringi subpluginu
├── db/
│   └── services.php                   # Serwisy AJAX
├── amd/src/
│   └── content_manager.js            # JavaScript managera treści
├── styles/
│   └── content.css                   # Style treści
└── my_content.php                    # Główne narzędzie treści
```

## Kroki instalacji

### Krok 1: Instalacja głównego pluginu
1. Skopiuj folder `local_aitools/` do `[moodle]/local/aitools/`
2. Przejdź do **Administracja witryny → Powiadomienia**
3. Kliknij **Uaktualnij bazę danych Moodle**

### Krok 2: Instalacja subpluginu ValueMapDoc
1. Skopiuj folder `valuemapdoc/` do `[moodle]/local/aitools/plugins/valuemapdoc/`
2. Przejdź ponownie do **Administracja witryny → Powiadomienia**
3. Kliknij **Uaktualnij bazę danych Moodle**

### Krok 3: Weryfikacja uprawnień
1. Przejdź do **Administracja witryny → Użytkownicy → Uprawnienia → Zdefiniuj role**
2. Sprawdź czy uprawnienia `local/aitools:view` są przypisane odpowiednim rolom
3. Domyślnie są przypisane do: user, student, teacher, editingteacher, manager

### Krok 4: Czyszczenie cache
1. **Administracja witryny → Rozwój → Purge caches**
2. Lub użyj CLI: `php admin/cli/purge_caches.php`

## Dostęp do narzędzi

### Główne menu
Po instalacji w głównej nawigacji Moodle pojawi się link **"AI Tools"**

### Bezpośredni dostęp
- Dashboard AI Tools: `[twoja-domena]/local/aitools/`
- Moje treści ValueMapDoc: `[twoja-domena]/local/aitools/plugins/valuemapdoc/my_content.php`

## Funkcjonalności

### Dashboard AI Tools
- **Statystyki** - liczba aktywnych pluginów, narzędzi, bloków
- **Bloki dashboardu** - interaktywne widżety od subpluginów
- **Katalog narzędzi** - pogrupowane według kategorii
- **Zarządzanie** - dla administratorów

### Narzędzie "Moje treści"
- **Widok globalny** - wszystkie treści użytkownika z całej platformy
- **Grupowanie** - według kursów → aktywności → dokumenty
- **Wyszukiwanie** - po nazwach i treści dokumentów
- **Filtrowanie** - po typach szablonów i statusach
- **Akcje** - podgląd i edycja dokumentów
- **Statystyki** - podsumowanie aktivności

## Wymagania

### Moodle
- **Wersja**: 4.0+ (2022041900)
- **PHP**: 7.4+

### Zależności
- `mod_valuemapdoc` w wersji 2025080106 lub nowszej
- `local_aitools` w wersji 2025091500 lub nowszej

## Rozwiązywanie problemów

### Plugin nie pojawia się w menu
1. Sprawdź uprawnienia użytkownika (`local/aitools:view`)
2. Wyczyść cache Moodle
3. Sprawdź czy użytkownik jest zalogowany

### Błąd "Class not found"
1. Sprawdź czy wszystkie pliki zostały skopiowane
2. Uruchom `php admin/cli/purge_caches.php`
3. Sprawdź logi błędów w `[moodle]/admin/tool/log/`

### Brak treści w dashboardzie
1. Sprawdź czy użytkownik ma treści w mod_valuemapdoc
2. Sprawdź uprawnienia do kursów z aktywnościami valuemapdoc
3. Sprawdź logi w **Administracja witryny → Raporty → Logi**

### Problemy z AJAX
1. Sprawdź czy serwisy są zarejestrowane w `db/services.php`
2. Sprawdź uprawnienia do wywoływania serwisów
3. Sprawdź konsole przeglądarki (F12)

## Rozwój i rozszerzenia

### Dodawanie nowych subpluginów
1. Utwórz folder w `/local/aitools/plugins/[nazwa]/`
2. Implementuj `\local_aitools\plugin_interface`
3. Zarejestruj w `version.php` z prefiksem `aitoolsub_`

### Dodawanie nowych bloków dashboardu
W metodzie `get_dashboard_blocks()` subpluginu:
```php
return [
    'block_key' => [
        'title' => 'Tytuł bloku',
        'template' => 'nazwa_template',
        'weight' => 10,          // Kolejność
        'size' => 'large',       // large/medium/small
        'data' => [...]          // Dane dla template
    ]
];
```

### Dodawanie nowych narzędzi
W metodzie `get_tools()` subpluginu:
```php
return [
    'tool_key' => [
        'title' => 'Nazwa narzędzia',
        'description' => 'Opis narzędzia',
        'url' => '/path/to/tool.php',
        'icon' => 'fa-icon-name',
        'category' => 'sales'     // sales/content/analytics/general
    ]
];
```

## Wsparcie

W przypadku problemów:
1. Sprawdź logi Moodle
2. Włącz debug mode: `$CFG->debug = E_ALL; $CFG->debugdisplay = 1;`
3. Skontaktuj się z zespołem deweloperskim mod_valuemapdoc