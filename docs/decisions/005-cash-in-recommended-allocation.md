# ADR 005: Tratamiento de `efectivo` en la distribución recomendada

## Status

**Aceptado — opción B.**

La calibración de `asset_allocation` no se tocó: sigue siendo la de Integrante A.
Lo que se agregó es el relleno con `0` de los tipos de activo que la distribución
no menciona, para que la distribución real y la recomendada siempre tengan las
mismas llaves.

Implementado en `RiskAnalysisServiceAdapter::withEveryAssetType()`, con la lista
canónica de tipos en `config('investment_rules.asset_types')`. Cubierto por
`AnalyzePortfolioToolTest::test_both_allocations_expose_the_same_asset_type_keys()`.

Queda abierto para Integrante A si más adelante quiere pasar a la opción A
(asignar un porcentaje real de efectivo por perfil) o a la C.

## Contexto

`assets.asset_type` maneja cuatro valores: `accion`, `bono`, `fondo`, `efectivo`
(ver `database/factories/AssetFactory.php`).

`config/investment_rules.php` define la distribución recomendada por perfil de
riesgo usando solo tres de ellos: `bono`, `fondo`, `accion`. **`efectivo` no
aparece en ningún perfil.**

Eso significa que el motor recomienda implícitamente 0% en efectivo para los tres
perfiles, incluido el conservador.

### Efecto observado

Salida real de `RiskAnalysisServiceAdapter::analyze()` para un usuario con perfil
`conservative` cuya única posición son $50,000 en efectivo:

```
allocation_by_asset_type            => ['efectivo' => 100.0]
diversification_score               => 0.0
concentration_warning               => true
recommended_allocation_by_asset_type => ['bono' => 80, 'fondo' => 20]
```

Tres consecuencias concretas:

1. **Las dos distribuciones no son comparables.** El componente A2UI (y el LLM)
   reciben un bucket `efectivo` en la distribución actual que no tiene contraparte
   en la recomendada. No hay forma de expresar "tu efectivo está bien" ni
   "tienes de más": simplemente no existe el concepto.
2. **`PortfolioService::createForUser()` nunca genera efectivo.** Un portafolio
   creado por la app es 0% efectivo por construcción, sin importar el perfil.
3. **Contradice el resto del modelo de dominio.** `FinancialProfile` tiene una
   columna `savings` y `FinancialGoal` contempla `goal_type = 'emergencia'`, así
   que el proyecto ya reconoce el ahorro líquido — pero el motor de distribución
   lo ignora.

### Nota aparte: efectivo no es un símbolo cotizable

`EloquentPortfolioService` intenta obtener un precio de mercado para el `symbol`
de todo holding, incluido el de efectivo. Contra TwelveData real eso va a fallar
siempre y marcar el holding como no valuado (`priced_with_live_market_data =>
false`). Con `MockMarketDataProvider` no se nota porque el mock cotiza cualquier
símbolo. Vale la pena decidir esto junto con lo anterior.

## Opciones

### A. Agregar `efectivo` a cada perfil en `investment_rules.php`

Ej. conservador 10% efectivo / 70% bono / 20% fondo. Los porcentajes de cada
perfil deben seguir sumando 100.

- A favor: hace comparables las dos distribuciones sin tocar código; refleja la
  práctica estándar de mantener un fondo de emergencia.
- En contra: cambia los números que Integrante A calibró con referencias de
  Banxico/IPC. **Es una decisión de dominio, no de implementación.**

### B. Dejar la recomendación como está y hacer explícito el 0%

Rellenar con `efectivo => 0` al construir la respuesta, para que ambas
distribuciones tengan las mismas llaves.

- A favor: no toca la calibración; resuelve el problema de comparabilidad.
- En contra: sigue afirmando que lo correcto es 0% efectivo, lo cual es una
  postura financiera discutible para un perfil conservador.

### C. Sacar el efectivo del cálculo de distribución

Tratarlo como una categoría aparte (liquidez / fondo de emergencia) que no
compite con los instrumentos de inversión.

- A favor: es lo más correcto conceptualmente — el efectivo no es una apuesta de
  inversión; también arregla lo del símbolo no cotizable.
- En contra: es el cambio más grande; toca el adapter, el componente A2UI y
  probablemente `concentration_warning`.

## Decisión

**Opción B.** Resuelve el problema de comparabilidad sin que nadie más que
Integrante A toque su calibración, que es justo la línea que no queríamos cruzar
antes del demo.

La **C** sigue siendo la correcta a mediano plazo — el efectivo no es una apuesta
de inversión y además arreglaría lo del símbolo no cotizable — pero no cabe en el
tiempo restante del hackathon.

Lo que sí queda pendiente de conversación: con la opción B, un usuario 100% en
efectivo sigue recibiendo una recomendación de 0% efectivo. Ya es *legible*
(antes ni siquiera aparecía la categoría), pero sigue siendo una postura
financiera discutible para un perfil conservador. Esa parte es de Integrante A.

## Quién decide

Integrante A (M2). Los porcentajes de `asset_allocation` y la metodología de
riesgo son suyos; este documento levantó el hueco y sus consecuencias, y la
opción B se eligió precisamente por no invadir esa decisión.
