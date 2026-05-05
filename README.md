# True Duel

MVP jugable de combate 1v1 por turnos con flujo completo en Flutter y fallback offline local cuando la API no esta disponible.

## Estado MVP

- Flujo jugable end-to-end en Flutter: inicio -> cola -> combate por turnos -> resultado.
- Roster MVP de 4 campeones seleccionables.
- Pool MVP de 10 modificadores aplicables en partida (se seleccionan 3 por combate).
- Defensa con cargas jugable (acumula hasta 2 y mitiga golpes siguientes).
- Recompensas post-partida visibles (monedas, gemas y delta MMR).
- Modo API con fallback recomendado a modo offline para pruebas locales.

## Ejecutar local (Flutter)

Requisitos:
- Flutter SDK estable instalado en host o usar Docker.
- Android SDK + emulador Android (si se ejecuta en host).

Comandos:

```bash
cd mobile/flutter
flutter pub get
flutter run
```

Por defecto arranca en modo `Offline local` (jugable sin backend).

### Forzar modo API

Si quieres usar backend real/simulado, lanza con defines:

```bash
cd mobile/flutter
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8080 --dart-define=API_TOKEN=dev-token
```

## Generar APK debug (instalable)

Desde `mobile/flutter`:

```bash
flutter build apk --debug
```

APK esperado:

`mobile/flutter/build/app/outputs/flutter-apk/app-debug.apk`

Instalacion manual en dispositivo/emulador conectado:

```bash
adb install -r build/app/outputs/flutter-apk/app-debug.apk
```

## Validacion rapida recomendada

```bash
cd mobile/flutter
flutter analyze
flutter test
```

Backend de contratos MVP:

```bash
cd backend/symfony
php tests/run.php
```
