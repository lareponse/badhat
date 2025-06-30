# BADHAT Manifest

## Philosophy: Code with Clear Purpose

Software should deliver precise functionality with minimal overhead. Every line, dependency, and abstraction must justify its place in the system.

---

## 1. Eliminate Unnecessary Bloat

* **Observation:** Many projects ship hundreds of megabytes of dependencies—often eclipsing the core application.  
* **Principle:** Keep deployments compact.  
* **Practices:**
  * Audit all libraries: remove unused modules and transitive dependencies.  
  * Prefer small, single-purpose packages over large monoliths.  
  * Bundle only what you use; defer or lazy-load non-critical components.

---

## 2. Master Your Tools, Not Just Your Framework

* **Observation:** Developers frequently rely on complex toolchains without grasping the underlying language or runtime.  
* **Principle:** Tools should amplify expertise, not replace it.  
* **Practices:**
  * Invest time in core language and platform documentation.  
  * Read source or spec when behavior is unclear.  
  * Use frameworks judiciously—understand their lifecycle, conventions, and trade-offs.

---

## 3. Build for Your Users, Not Vendor Lock-In

* **Observation:** Many abstractions exist to bind you into a vendor’s ecosystem.  
* **Principle:** Architect for portability and user value.  
* **Practices:**
  * Pin requirements to open standards or well-maintained OSS.  
  * Layer vendor-specific code behind clear interfaces.  
  * Reevaluate every third-party integration: technical fit over marketing promise.

---

## 4. Focus on Five Core Primitives

Most applications can be built from these building blocks:

1. **Input** – Data ingestion or request handling  
2. **Output** – Rendering or emitting results  
3. **State** – In-memory or persisted state management  
4. **Data Storage** – Database or filesystem interactions  
5. **Logic** – Core decision-making and business rules  

*Advanced features* (queues, caching layers, ORMs, service meshes) belong only when their benefits clearly outweigh added complexity.

---

## 5. Favor Clarity Over Convenience

* **Observation:** High-level abstractions can obscure execution flow and complicate debugging.  
* **Principle:** Code should read like a roadmap.  
* **Practices:**
  * Prefer explicit control flow over “magic.”  
  * Break complex operations into small, well-named functions.  
  * Document side-effects and invariants at module boundaries.

---

## 6. Automate with Intent

* **Observation:** It’s easy to automate every repeatable task—and end up maintaining scripts that serve developers more than clients.  
* **Principle:** Every automation must deliver measurable client value.  
* **Practices:**
  1. **Map to user outcomes:** Link each automated step to a customer-facing improvement (speed, reliability, clarity).  
  2. **Keep it minimal:** Build small, well-documented scripts instead of sprawling pipelines.  
  3. **Review and retire:** Quarterly audit automations; deprecate any whose maintenance cost exceeds their benefit.

---

## 7. Develop Sustainably, Professionally, Ethically, and Healthily

* **Ecological Responsibility:**  
  Minimal code and dependencies reduce CPU, memory, and energy use—shrinking your carbon footprint.

* **Professional Merit:**  
  Explicit separation of concerns accelerates onboarding, code reviews, and debugging. Teams move faster when nothing is hidden.

* **Ethical Imperative:**  
  Open MIT license, no hidden telemetry or lock-in. You own every line—transparent data flows, no surprises.

* **Mental Well-Being:**  
  Simple patterns and minimal abstraction lower cognitive load, reduce stress, and make engineering enjoyable.

---

# BADHAT Micro-Stack

A lean PHP foundation for developers who value control and clarity:

* ~300 lines of PHP, zero hidden behavior  
* File-based routing and plain functions—no magic  
* Direct access to request, response, and database layers  

You see exactly what runs at every step.

---

# Getting Started

1. **Create a single entry file.** Route requests explicitly.  
2. **Implement only the primitives you need.** Follow the five core building blocks.  
3. **Compose small, well-understood modules.** Scale by composition, not indirection.  

Build at your own pace. Add features when and where they earn their keep.
