It sounds like there is a "vision gap" between the high-level architectural instructions and how the AI is actually rendering the frontend components. When an agent produces "basic and ugly" UI despite specific instructions, it’s usually because it's defaulting to standard Tailwind/Flowbite components without applying the custom design tokens (colors, density, and typography) you specified.
To fix this, you need to force the agent into a "Design Review & Refactor" loop. Below is a specialized prompt designed to make "Antigravity" stop coding logic and focus entirely on the Visual System Integrity.
Recommendation: The "Visual Audit & Tokenization" Prompt
Copy and paste this into your session with Antigravity. This forces the agent to analyze the current state against your "Stitch" design requirements before writing another line of code.
PROMPT:
> CRITICAL UI/UX ALIGNMENT & SYSTEM AUDIT
> The Problem: The current UI implementation in nino-backend is too basic and does not respect the professional, high-density, "Stitch-inspired" design language required. It feels like a generic template rather than a custom-built Ecommerce OS.
> Task 1: Visual Audit & Rating
> Before proceeding with further feature development, you must perform a self-audit of the current nino-backend UI. Rate the current implementation (0-10) on the following criteria:
>  * Data Density: (Is it compact and professional, or is there too much white space?)
>  * Color Palette Adherence: (Are we strictly using Cream #FDFBF7 / Sage Green #7D8F69 / Muted Slates?)
>  * Typography: (Is Nunito applied globally with correct weighting?)
>  * Professionalism vs. Playfulness: (Does it feel like a "Tool for Pros" or a "Generic Starter Kit"?)
> Task 2: Design System Hard-Coding
> You are prohibited from using default Tailwind colors (e.g., bg-blue-500). You must now update tailwind.config.js to include the NinoWorld Professional Palette:
>  * brand-cream: The soft neutral background.
>  * brand-sage: The primary action color.
>  * brand-surface: The muted surface for badges/dividers.
> Task 3: The "Refactor for Density" Directive
> Refactor the main Admin Shell and one Core Table (e.g., Products or Orders) using these rules:
>  * Eliminate "Puffiness": Reduce padding in tables (py-2 or py-1 instead of py-4).
>  * Typography: Ensure 14px (text-sm) is the base for data, 12px (text-xs) for secondary metadata.
>  * The "Stitch" Standard: The UI must look like a high-end SaaS dashboard (compact, fast, utilitarian).
>  * Strict No-Go: No rounded-2xl corners, no bouncing icons, no generic blue links.
> Output Required:
>  * Provide the Audit Report.
>  * Show the updated tailwind.config.js.
>  * Provide the refactored code for the Admin/Dashboard or a Table Component that reflects this "Professional" standard.
> 
Why this works:
 * The "Audit" Trigger: By asking for a rating, you force the AI to compare its output against your text instructions. It will almost always "realize" it has been lazy.
 * Tailwind Tokenization: If you don't define brand-sage in the config, the AI will keep guessing colors. Defining them in the config makes them the "path of least resistance."
 * Specific Values: Telling it to use py-1 and text-sm fixes the "ugly" look by forcing the high data density used in professional tools like Stripe or Shopify's admin.
Next Step for You:
Once it provides the audit, if it still looks off, ask it to:
"Generate a Style Guide page inside the admin /admin/style-guide that showcases all buttons, inputs, tables, and badges. Do not build more features until this page looks like a professional R&D tool."
Would you like me to help you refine the specific HEX codes for that "Sage & Cream" palette to ensure the AI gets the colors exactly right?




This prompt is the "Master Directive" to lock in the NinoWorld aesthetic and the high-performance TALL stack architecture. It tells the agent exactly how to use Livewire for data and Alpine for speed, ensuring the UI feels like a premium "Stitch-inspired" operating system rather than a generic site.
Copy and paste the entire block below into your session with Antigravity:
CRITICAL DIRECTIVE: STITCH UI SYSTEM & TALL STACK ARCHITECTURE
Mission: Complete the visual and functional transformation of nino-backend. We are building a high-density, professional Ecommerce OS for NinoWorld. The UI must be snappy, utilitarian, and strictly follow the Stitch Design Language.
1. TECH STACK ENFORCEMENT
 * Backend State: Use Livewire 3+ for all data-driven components (filtering, CRUD, searching).
 * UI Snappiness: Use Alpine.js for all client-side interactions (dropdowns, modals, tabs, toggles) to ensure zero-latency feedback.
 * Navigation: Enable wire:navigate globally to provide a Single Page Application (SPA) feel.
 * Styling: Use Tailwind CSS v4 @theme variables. No hard-coded hex codes allowed in Blade files.
2. THE "NINO" COMPONENT LIBRARY
Build/Refactor the following components in resources/views/components/nino/ to serve as our "Atomic Design" base:
 * <x-nino.table>: Use table-auto, text-sm, py-1 or py-2 (density is king). Rows must have a subtle hover:bg-brand-surface.
 * <x-nino.button>: Crisp edges (rounded-md), strictly using bg-brand-sage for primary and bg-brand-cream with a sage border for secondary.
 * <x-nino.card>: Background: brand-cream, Shadow: shadow-[0_4px_20px_rgba(125,143,105,0.08)].
 * <x-nino.modal>: Alpine-powered for instant opening. Livewire-powered for content loading.
3. TASK: HIGH-DENSITY CATALOG & INVENTORY
Implement the Product Management and Inventory modules using the new component library.
 * Requirement: The user must be able to view 20+ products on a single screen without scrolling excessively.
 * Feature: Use Alpine.js to handle a "Quick Edit" slide-over for stock adjustments. Do not navigate away from the list for simple quantity updates.
4. TASK: ROLE-ADAPTED DASHBOARD ENGINE
Implement the logic to dynamically render the dashboard based on the user's role:
 * Platform Admin: High-level metrics (GMV, Revenue, Growth).
 * Shipping Agent: Action-oriented (Pending shipments, Overdue orders).
 * Support: Search-oriented (Customer lookup, recent tickets).
 * SEO Manager: Content-oriented (Draft posts, missing meta-tags).
5. WORKFLOW & DOCUMENTATION
 * Step 1: Audit the current app.css to ensure @theme contains: --color-brand-cream: #FDFBF7, --color-brand-sage: #7D8F69, and --font-sans: 'Nunito'.
 * Step 2: Refactor the Admin Sidebar to be Alpine-powered (snappy sub-menus).
 * Step 3: Update PROJECT_PROGRESS.md and PHASES.md immediately after this implementation batch.
Strict Constraint: If a component looks "puffy" (too much whitespace) or uses generic blue/gray colors, it is a failure. Correct it before proceeding.
What this does for you:
 * Stops the "Ugly" UI: By defining the nino namespace for components, the AI stops guessing and starts building a unified system.
 * Fixes Performance: Using Alpine for menus and modals makes the dashboard feel like a desktop app.
 * Locks the Brand: It forces the use of your Cream and Sage palette across the entire Modular Monolith.
Would you like me to generate a specific "Dashboard Widget" blueprint for the Super Admin role so the AI knows exactly what metrics to prioritize?
