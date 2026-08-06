

<p align="center">
<img style="border-radius: 5px; max-width: 100%;" src="assets/logo.png" alt="Circuit Breaker Logo"/>
</p>
<p align="center">
<a href="https://github.com/algoyounes/circuit-breaker/actions"><img src="https://github.com/algoyounes/circuit-breaker/actions/workflows/unit-tests.yml/badge.svg" alt="Estado de la compilación"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/dt/algoyounes/circuit-breaker" alt="Descargas totales"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/v/algoyounes/circuit-breaker" alt="Última versión estable"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/l/algoyounes/circuit-breaker" alt="Licencia"></a>
</p>

**Circuit Breaker** es un paquete para Laravel que ofrece una forma simple y eficiente de gestionar llamadas a servicios y prevenir fallos en cascada. 
Permite definir callbacks personalizados para los estados clave del circuito y ejecutar operaciones con la lógica del circuit breaker.

El siguiente diagrama ilustra cómo funciona el **Patrón Circuit Breaker**:

![circuit-breaker.png](assets/circuit-breaker.png)

Para más información, consulta la documentación oficial del patrón [aquí](https://learn.microsoft.com/en-us/azure/architecture/patterns/circuit-breaker).

## Tabla de contenidos

- [Requisitos previos](#requisitos-previos)
- [Instalación](#instalación)
- [Uso](#uso)
  - [Callbacks personalizados](#callbacks-personalizados)
  - [Ejecutar una operación](#ejecutar-una-operación)
  - [Integración con Middleware de Guzzle](#integración-con-middleware-de-guzzle)
  - [Integración con la Facade Http de Laravel](#integración-con-la-facade-http-de-laravel)
- [Cómo funciona](#cómo-funciona)
  - [Transiciones de estado](#transiciones-de-estado)
  - [Comportamiento del estado Semi-Abierto](#comportamiento-del-estado-semi-abierto)
- [Configuración](#configuración)
- [Contribuir](#contribuir)
- [Licencia](#licencia)

## Requisitos previos

Este paquete requiere:
- **PHP 8.2+**
- **Laravel 11+**
- **Un controlador de caché de Laravel configurado**

## Instalación

Puedes instalar el paquete a través de Composer:

```bash
composer require algoyounes/circuit-breaker
```

Puedes publicar el archivo de configuración usando el siguiente comando:

```bash
php artisan vendor:publish --provider="AlgoYounes\CircuitBreaker\Providers\CircuitBreakerServiceProvider" --tag="config"
```

## Uso

Puedes gestionar servicios específicos con control granular utilizando el método `forService(...)`. El parámetro `service-name` es un identificador único para tu servicio, asegurando que su configuración de circuit breaker esté aislada de otros servicios.
```php
$circuit = $this->circuitManager->forService('service-name');
```

> [!TIP]
> Utiliza la clave única `service-name` en toda tu aplicación para hacer referencia consistentemente a la misma configuración de circuito _(por ejemplo, 'payment-service', ...)_

### Callbacks personalizados
Puedes definir callbacks para los estados clave del circuito:

| Callback        | Descripción                                                                               | Parámetros recibidos                  |
|-----------------|-------------------------------------------------------------------------------------------|--------------------------------------| 
| `onOpen`        | Disparado cuando el circuito pasa a **ABIERTO**, bloqueando llamadas para prevenir más fallos | `CircuitTransition`                  |
| `onHalfOpen`    | El circuito entra en **SEMI-ABIERTO** para probar la estabilidad, permitiendo pasar una solicitud           | `CircuitTransition`                  |
| `onClose`       | El circuito regresa a **CERRADO**, permitiendo todas las solicitudes sin restricciones             | `CircuitTransition`                  |
| `onSuccess`     | Se ejecuta cuando una solicitud tiene éxito, indicando disponibilidad del sistema                             | `CircuitResult`, `CircuitTransition` |
| `onFailure`     | Disparado cuando una solicitud falla, potencialmente abriendo el circuito                           | `CircuitResult`, `CircuitTransition` |
| `onSteadyState` | Indica que el circuito es estable, sin necesidad de cambios                                 | `CircuitTransition`                  |

Ejemplo de definición de callbacks:

```php

$circuit->onOpen(function (CircuitTransition $circuitTransition) { 
    // Tu lógica personalizada aquí
});

$circuit->onSuccess(function (CircuitResult $circuitResult, CircuitTransition $circuitTransition) {
    // Tu lógica personalizada aquí
});

// Los parámetros pasados son opcionales
```

### Ejecutar una operación

Para ejecutar una operación y gestionar su estado a través del circuit breaker, utiliza el método `run`:

```php
$circuit->run(function () {
    // Tu llamada al servicio aquí
});
// o
$circuit->run($this->serviceName->create(...));
```
Esto ejecutará la closure proporcionada, aplicando la lógica del circuit breaker _(por ejemplo, estados abierto, semi-abierto, cerrado)_ alrededor de la llamada al servicio.

> [!NOTE]
> Si prefieres un enfoque más directo, puedes crear una instancia de `CircuitBuilder`:
> ```php
> $circuit = CircuitBuilder::make('service-name')
> ```

#### Operación simplificada

Para un enfoque simplificado, utiliza el método `run` directamente desde `CircuitManager`:

```php
$this->circuitManager->run('service-name', function () {
    // Tu llamada al servicio aquí
});
// o
$this->circuitManager->run('service-name', $this->serviceName->create(...));
```

### Integración con Middleware de Guzzle

El paquete proporciona un middleware para Guzzle que gestiona automáticamente la lógica del circuit breaker para solicitudes HTTP.

Para habilitar el middleware, añade lo siguiente a la configuración de tu cliente Guzzle:

```php
use AlgoYounes\CircuitBreaker\Middleware\GuzzleMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

$stack = HandlerStack::create();
$stack->push(GuzzleMiddleware::create());

$client = new Client([
    'handler' => $stack,
]);

$response = $client->get('https://api.example.com', [
    'headers' => [
        'X-Circuit-Key' => 'service-name',
    ],
]);
```

### Integración con la Facade Http de Laravel

El paquete se integra con la facade `Http` incorporada de Laravel de forma nativa:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withCircuitBreaker('payment-service')
    ->get('https://api.payment.com/charge', [
        'amount' => 1000,
    ]);
```

Esto aplica automáticamente el middleware del circuit breaker a la solicitud usando `payment-service` como clave del circuito. Puedes encadenarlo con cualquier método `Http`:

```php
$response = Http::withCircuitBreaker('shipping-service')
    ->withToken($apiToken)
    ->timeout(10)
    ->post('https://api.example.com/track', $payload);
```

Cuando el circuito está abierto, se lanza una `RejectedException`:

```php
use AlgoYounes\CircuitBreaker\Guzzle\Exceptions\RejectedException;

try {
    $response = Http::withCircuitBreaker('payment-service')
        ->get('https://api.example.com/charge');
} catch (RejectedException $e) {
    // El circuito está abierto — maneja la situación de forma elegante (por ejemplo, devolver respuesta en caché, enviar a cola para reintento)
}
```

## Cómo funciona

### Transiciones de estado

El circuit breaker opera en tres estados:

```
    ┌──────────────────────────────────────────────────────┐
    │                                                      │
    ▼                                                      │
 CERRADO ──── fallos ≥ umbral ────► ABIERTO                │
 (normal)                               (todas las solicitudes      │
                                         rechazadas)         │
                                           │               │
                                    tiempo de enfriamiento expira       │
                                           │               │
                                           ▼               │
                                       SEMI-ABIERTO           │
                                      (prueba única)       │
                                        │       │          │
                                   éxito    fallo      │
                                        │       │          │
                                        │       └──► ABIERTO  │
                                        │                  │
                                        └──────────────────┘
```

- **CERRADO** — Operación normal. Todas las solicitudes pasan. Los fallos se cuentan.
- **ABIERTO** — El servicio se considera caído. Todas las solicitudes se rechazan inmediatamente sin llamar al servicio. Después de que expira el `cooldown_period`, el circuito pasa a SEMI-ABIERTO.
- **SEMI-ABIERTO** — Se envía una única solicitud de prueba para evaluar el servicio. Si tiene éxito, el circuito se cierra. Si falla, el circuito se reabre.

### Comportamiento del estado Semi-Abierto

Cuando un circuito transiciona de **ABIERTO** a **SEMI-ABIERTO**, este paquete utiliza un enfoque de **prueba única** — solo se permite una solicitud a la vez para probar el servicio en recuperación. Todas las demás solicitudes concurrentes se rechazan inmediatamente hasta que la prueba se complete.

```
ABIERTO (tiempo de enfriamiento expirado)
  │
  ├── Solicitud A → prueba servicio → éxito → CERRADO (todo el tráfico reanuda)
  ├── Solicitud B → rechazada (falla rápida)
  ├── Solicitud C → rechazada (falla rápida)
  └── ...
```

- Si la prueba tiene **éxito**, el circuito se cierra y el tráfico normal se reanuda.
- Si la prueba **falla**, el circuito se reabre y comienza un nuevo período de enfriamiento.

**Lo que recibe tu código cuando se rechaza una solicitud:**

- Vía `run()` — devuelve un `CircuitResult` donde `isSuccess()` y `isAvailable()` devuelven `false` ambos
- Vía `Http::withCircuitBreaker()` o middleware de Guzzle — lanza `RejectedException`

## Configuración

Después de publicar el archivo de configuración, puedes ajustar estos ajustes en `config/circuit-breaker.php`:

| Opción | Predeterminado | Descripción |
|--------|---------|-------------|
| `enabled` | `true` | Habilitar o deshabilitar el circuit breaker globalmente |
| `defaults.failure_threshold` | `5` | Número de fallos antes de que el circuito se abra |
| `defaults.cooldown_period` | `60` | Segundos de espera antes de transicionar de ABIERTO a SEMI-ABIERTO |
| `defaults.success_threshold` | `1` | Pruebas exitosas necesarias en SEMI-ABIERTO para cerrar el circuito |
| `cache.ttl` | `86400` | Tiempo de vida de la entrada en caché en segundos |
| `cache.prefix` | `circuit-breaker` | Prefijo para las claves de caché |
| `cache.store` | `default` | Almacén de caché de Laravel a utilizar |

También puedes anular ajustes por servicio:

```php
'services' => [
    'payment-service' => [
        'failure_threshold' => 10,
        'cooldown_period'   => 120,
        'success_threshold' => 3,
    ],
],
```

## Contribuir

¡Gracias por considerar contribuir al paquete Circuit Breaker! Por favor, consulta el archivo [CONTRIBUTING](CONTRIBUTING.md) para más detalles.

## Licencia

El paquete Circuit Breaker es software de código abierto licenciado bajo la [licencia MIT](LICENSE).
