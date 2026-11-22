# EVALOOP — DESIGN.md

> **Status:** Approved design direction — Fresh Editorial, version 1.1 (University MVP)  
> **Applies to:** Laravel Blade + Tailwind CSS 4 web application and the Flutter Android companion  
> **Purpose:** Shared visual and interaction standards for web pages and Android screens.  
> **Important:** This document specifies the *target design*. It does not claim the current application already implements every detail.

## 0. Design decision: do not reinvent the brand per page

Evaloop is a campus event **discovery + operations + institutional learning** product. Its visual identity is **Fresh Editorial**: bright, light, approachable, credible, and intentionally composed. The user approved the Ice Blue / White / Soft Aqua / Ocean Teal direction after rejecting Deep Navy/Coral and an Electric Blue/Violet startup gradient.

**Non-negotiables:**
- Keep **one shared product identity** across all modules. Each page gets a *slight local accent and distinct layout*, **not a separate theme**.
- **Final type system: Inter for interface text, Geist for display headings**, with system sans fallbacks. Manrope is retained only for existing logo/poster artwork.
- **Base styling: Tailwind CSS 4**; use theme tokens, existing Blade components, and scoped CSS when necessary. No new CSS framework, animation library, React, Vue, Inertia, or Livewire.
- Prefer editorial composition over generic dashboard cards. Layout and hierarchy are more important than decorative effects.
- The **Sxodim-inspired portrait/event poster cards** in Event Radar are already approved. Keep their composition and distinctive poster artwork. Do not recolor every poster to teal.
- Navigation follows a clear, compact **Linear-inspired** information hierarchy; Event Radar search/filter follows a compact **CreateUnity-inspired** discovery experience. These are references, not templates to copy.
- **Do not use Deep Navy or Coral as hero/brand palette.** Dark slate for legible text and isolated neutral contrast is allowed. Red/orange remain available **only for semantic danger/warning states**.
- No fake event data, no misleading success feedback, no disclosure of private data, and no changes to Laravel role/authorization rules for cosmetic reasons.

---

## 1. Master palette — brand and neutral tokens

### 1.1 Brand foundation (shared by every page)

| Token | Hex | Primary role |
|---|---|---|
| `ev-ice` | `#F0F8FA` | Discovery hero, editorial backgrounds |
| `ev-white` | `#FFFFFF` | Cards, forms, search, popovers |
| `ev-canvas` | `#F7FAFB` | General application background |
| `ev-primary` | `#0F766E` | Main CTA, active controls, interactive focus |
| `ev-primary-hover` | `#115E59` | Main CTA hover/pressed |
| `ev-primary-soft` | `#E4F5F1` | Selected rows, active chips, subtle emphasis |
| `ev-aqua` | `#B9E6E5` | Graphic accents and supporting illustrations |
| `ev-mist` | `#E3F2F5` | Soft separation and gradient stops |
| `ev-ink` | `#253344` | Headings / high-contrast text |
| `ev-muted` | `#5F7180` | Metadata / supporting copy |
| `ev-border` | `#D9E5E8` | Dividers, input/card borders |
| `ev-subtle` | `#EEF5F6` | Nested secondary surface |

**Use of color:** approximately 70% white/neutral surfaces, 20% light ice/aqua support, 10% strong accent is an *informal balance heuristic*, not a strict measured rule. The page must not become an all-teal dashboard.

### 1.2 Supporting accents (the SAME family, not different brands)

These are reserved for carefully differentiated modules. Only the designated page or an intentionally related component may use them as the **page accent**:

| Token | Hex | Allowed use |
|---|---|---|
| `ev-blue` | `#286F91` | Dashboard and My Tasks emphasis |
| `ev-blue-soft` | `#EAF4F9` | Dashboard / task background tint |
| `ev-cyan` | `#287E8B` | Meeting Hub visual timeline |
| `ev-cyan-soft` | `#E5F5F6` | Meeting timeline / decisions |
| `ev-legacy` | `#49718C` | Legacy lineage visual links |
| `ev-legacy-soft` | `#EDF4F8` | Legacy edition panels |

**Rule:** Teal remains the globally understood primary action color. The blue/cyan/legacy tones are for page rhythm, labels, diagram connectors, illustrations, and secondary emphasis—not a new palette for every button.

### 1.3 Semantic colors (never repurpose as decoration)

| Meaning | Main | Soft surface | Usage |
|---|---|---|---|
| Success / Approved | `#167347` | `#EAF7EE` | Completed task, approved lesson |
| Warning / Potential collision | `#986000` | `#FFF4D8` | Venue/audience conflict, caution |
| Error / Destructive | `#B42318` | `#FEF0EE` | Invalid action, cancel/destructive |
| Neutral / Archived | `#667788` | `#EEF2F5` | Cancelled/old items where appropriate |
| Info | `#286F91` | `#EAF4F9` | Informational time overlap, guidance |

Use `ev-info`, `ev-warning`, `ev-success`, `ev-neutral`, and `ev-danger` with their matching `-soft` surfaces. Use `ev-neutral-ink` (#526476) for small text on Neutral Soft. Published events and active work use info, completed work and approved evaluations use success, and drafts and cancelled events use neutral. A completion meter shows the fraction of tasks done with a single fill. Keep overdue attention separate from completion.

Never communicate status using color alone. Display explicit text and/or icon; verify contrast in real rendering. An informational `time_overlap` must not appear as a severe venue collision.

---

## 2. Palette by page — authoritative map

**Interpretation:** Each row defines a *local accent*, *surface treatment*, and *layout character*. All pages still use the master text/border/buttons and the Geist/Inter type system. Do not build multiple independent themes.

| Page / module | Background & surfaces | Page accent / gradient | Layout personality | Avoid |
|---|---|---|---|---|
| **Public Event Radar** (`/events`) | Ice `#F0F8FA` → white | Teal `#0F766E`, Aqua `#B9E6E5`; very soft Ice→White hero | Asymmetric editorial hero, real featured event, integrated search, compact filters, **approved poster grid** | Generic SaaS heading→long search→tabs; all-teal posters |
| **Public Event Detail** | White with Mist `#E3F2F5` contextual band | Primary teal, source poster's own visual | Split hero: shared Radar poster left, event identity/date/time/location right; description + organizer below; stacks on narrow containers; public-only information, no ticketing | Turning details into multiple unrelated cards |
| **Login / Register** | White + Ice background, subtle Aqua geometry | Teal CTA; no high-saturation gradient | Focused, calm auth panel, efficient form, minimal distractions | Massive illustrated hero, tiny labels, decorative login UI |
| **Dashboard** (`/dashboard`) | Canvas `#F7FAFB`, white work surfaces | Blue `#286F91` + pale Blue `#EAF4F9` for priorities | Personal worklist, event context, upcoming meetings, concise progress | Four meaningless KPI cards, oversized blue hero |
| **University Administration** | White / Canvas, Mist dividers | Teal as action; Blue for neutral admin context | Dense-but-readable directories, scoped creation/role actions | Making admin look like marketing/discovery landing page |
| **Organization Directory & Members** | White / Canvas, soft aqua selective surfaces | Teal `#0F766E`, soft Aqua `#B9E6E5` | Membership directory, grouped roles, context actions, series history | Colored card for every member, duplicated metadata |
| **Event Workspace** | White / Canvas, Ice contextual masthead | Teal primary + restrained Mist | State-aware command center: compact shared-poster masthead/subnav; draft/published prioritize work + meetings, completed prioritizes approved learning + archives, cancelled clearly preserves archives; series links require workspace access | Reusing public Event Radar hero in private workspace |
| **Collision Review** | White with `ev-subtle` and contextual bands | Info Blue for `time_overlap`, Amber for venue/audience warning | Compare events side by side or stack responsively; state explicit reasons | Red alarm for all overlaps; status conveyed only by color |
| **Meeting Hub & Decisions** | White / Canvas, `#E5F5F6` along timeline | Cyan `#287E8B` | Agenda timeline, chronological navigation, decisions visible as outcomes | Generic CRUD table as main narrative |
| **Task Tracking & My Tasks** | White / Canvas, Blue-soft for selected/attention rows | Blue `#286F91` as secondary, Teal for action | Compact issue list, status/PIC/deadline grouped, measured progress | Big cards for every task, colorful status everywhere |
| **Legacy Insights & Approval** | Ice / White, Legacy-soft `#EDF4F8` for lineage | Legacy Blue `#49718C` connectors + Teal action | Previous edition → problem → recommendation → approval → current task | Generic testimonials, auto-created tasks, decorative timeline |
| **Legacy-to-Action form** | White with source summary in Legacy-soft | Teal CTA, Legacy Blue for source reference | Origin lesson visibly connected to new task form and status | Hiding `source_lesson_id` relationship or displaying fake progress |
| **404 / 403 / 419 / 429 states** | White / Mist, no unnecessary decoration | Semantic color for reason, Teal for safe recovery | Brief explanation + permitted next step | Generic scary error graphics, exposing private details |

### 2.1 Per-page implementation boundaries

- Apply local accents to **specific contexts**, e.g. page eyebrow, selected item, timeline rail, contextual band, or section illustration. **Do not change global CTA from teal to another accent on each page.**
- An event's poster may use independent curated/fallback artwork (for a distinctive discovery grid); it must remain readable and recognizable as a simulated fixture if applicable.
- For small pages, the local accent may be almost invisible. Cohesion beats visual noise.
- A module's status is a **semantic** concern: approved is success green, collision warning is amber, destructive is red—even when the page accent is blue.

---

## 3. Gradients — restrained editorial depth

Approved, light-only gradients:

```css
/* Hero/discovery — quiet, not a neon background */
background: linear-gradient(130deg, #E3F2F5 0%, #F0F8FA 56%, #FFFFFF 100%);

/* Contextual illustration/featured panel — soft aqua */
background: linear-gradient(145deg, #B9E6E5 0%, #E3F2F5 58%, #F7FAFB 100%);

/* Supporting inner panel */
background: linear-gradient(180deg, #FFFFFF 0%, #F0F8FA 100%);
```

Usage by page:
- **Radar:** one editorial hero gradient and/or featured panel; keep search white and CTA solid teal.
- **Dashboard:** only a restrained contextual band behind the top work priority, not every widget.
- **Workspace:** Ice masthead shading to separate event context from action lists.
- **Meetings:** mostly flat surfaces; use cyan tint in timeline rail, not multi-color gradients.
- **Tasks:** flat white/blue-soft selected rows; **no gradient in the task list**.
- **Legacy:** subtle transition from historical/source area to current/action area (Ice → White); avoid graphic noise around long text.
- **Organizations:** mostly neutral, limited tint in group headers.

**Disallowed:** neon blue-violet gradients; full-page dark navy; coral spotlight blocks; gradient-filled buttons everywhere; blurred blobs behind all headings; glassmorphism that reduces contrast.

---

## 4. Final typography — Geist display + Inter interface

This decision supersedes all previous Manrope/Sora/Plus Jakarta UI directions.

| Role | Typeface | Size / weight | Rhythm |
|---|---|---|---|
| Page titles, workspace/event/organization mastheads, editorial headline | Geist | fluid28–32px /600 | 1.3 leading, -.02em tracking |
| Section headings | Geist | fluid18–22px /600 | 1.3–1.4 leading, restrained tracking |
| Event/project card headings | Geist | fluid16–18px /600 | 1.3–1.45 leading |
| Body/descriptions | Inter |14–15px /400 | 1.6 leading; comfortable reading width |
| Sidebar/navigation | Inter |14px /500 | Sentence-case action labels, explicit active state |
| Buttons/forms | Inter |14px /500–600 | Native focus, validation and pending states retained |
| Tables/task tracker rows | Inter |14–15px /400–600 | Task names are interface text, not display headings |
| Metadata/breadcrumbs/captions | Inter |12–13px /400–500 | Never use faint tiny text for essential information |

**Single owner/source:** `backend/resources/css/typography.css` declares font faces, Tailwind4 tokens (`font-sans`, `font-interface`, `font-display`, `font-artwork`) and role utilities (`type-page`, `type-section`, `type-card`, `type-interface`, `type-metadata`). It is imported once by app.css. Semantic defaults cover existing headings; local modules reference central size/weight/leading/tracking tokens instead of defining competing scales or fonts. Do not add per-page font declarations/imports. Do not apply display typography to navigation, task rows or table cells merely because they contain a heading tag.

Fonts are local variable WOFF2 normal400–700, Latin/Latin-ext, sourced from official Google Fonts distribution, with OFL/source files under resources/fonts/{inter,geist}. Use `font-display:swap`, system sans fallback and preload the Latin UI/display files on real pages. Verify actual downloaded faces in browser, not merely a CSS family declaration. No runtime font CDN/package.

**Artwork exception:** Existing Evaloop wordmark and approved typographic/geometric posters retain their original Manrope face/weights/sizes/tracking via `font-artwork`. Existing Manrope assets remain only for that exception. Do not restyle logo/poster graphics while changing interface typography. Avoid800–900 UI weights and aggressive negative tracking.

### Writing consistency

- Indonesian UI copy; sentence case for actions/section labels: `Buat event`, `Buka workspace`, `Event organisasi`, `Daftar workspace`.
- Preserve established feature/menu names: `Event Radar`, `Meeting Hub`, `Legacy Insights`, `Event Series`, `Tugas Saya`, `Event Saya`, `Organisasi Saya`.
- Use `Tugas & progres` consistently for the task module; do not mix it with Task Tracking in visible UI.
- Use `Tenggat` and `Lewat tenggat` consistently instead of mixing Deadline/Overdue labels.
- Task status labels: `Belum dimulai`, `Sedang dikerjakan`, `Selesai`. Database values remain todo/in_progress/done.
- Event status labels: `Draft`, `Dipublikasikan`, `Selesai`, `Dibatalkan`; publication does not imply completion.
- Keep explicit labels for actions, consistent WITA timestamps and existing validation/flash semantics. Do not rewrite user/database content or rename routes/input fields for copy polish.

---

## 5. Tailwind CSS 4 implementation contract

**Do not blindly paste the example into `app.css`.** First inspect existing tokens, CSS cascade, and component usage. Migrate aliases carefully to avoid breaking old views. The example illustrates token usage.

```css
/* backend/resources/css/app.css — integrate with the existing Tailwind v4 entrypoint */
@theme {
  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
  --font-display: "Geist", ui-sans-serif, system-ui, sans-serif;

  --color-ev-ice: #F0F8FA;
  --color-ev-white: #FFFFFF;
  --color-ev-canvas: #F7FAFB;
  --color-ev-primary: #0F766E;
  --color-ev-primary-hover: #115E59;
  --color-ev-primary-soft: #E4F5F1;
  --color-ev-aqua: #B9E6E5;
  --color-ev-mist: #E3F2F5;
  --color-ev-ink: #253344;
  --color-ev-muted: #5F7180;
  --color-ev-border: #D9E5E8;
  --color-ev-subtle: #EEF5F6;
  --color-ev-blue: #286F91;
  --color-ev-blue-soft: #EAF4F9;
  --color-ev-cyan: #287E8B;
  --color-ev-cyan-soft: #E5F5F6;
  --color-ev-legacy: #49718C;
  --color-ev-legacy-soft: #EDF4F8;
}

/* Optional page-scoped overrides — not a competing global theme */
.ev-page {
  --ev-page-accent: var(--color-ev-primary);
  --ev-page-tint: var(--color-ev-primary-soft);
}
.ev-page--dashboard { --ev-page-accent: var(--color-ev-blue); --ev-page-tint: var(--color-ev-blue-soft); }
.ev-page--meetings { --ev-page-accent: var(--color-ev-cyan); --ev-page-tint: var(--color-ev-cyan-soft); }
.ev-page--tasks { --ev-page-accent: var(--color-ev-blue); --ev-page-tint: var(--color-ev-blue-soft); }
.ev-page--legacy { --ev-page-accent: var(--color-ev-legacy); --ev-page-tint: var(--color-ev-legacy-soft); }
```

**Example Tailwind usage:** `bg-ev-canvas`, `text-ev-ink`, `border-ev-border`, `bg-ev-primary hover:bg-ev-primary-hover`, `bg-[var(--ev-page-tint)]`, `text-[var(--ev-page-accent)]`. Adapt to existing project lint/build conventions.

### 5.1 Shared tokens and page-specific styles

Keep color, typography, focus, and reusable control rules in `app.css` and shared Blade components. Place module-specific composition near the relevant view and scope its styles to that module. Review sidebar, forms, and other consumers when changing a shared token or control.

---

## 6. Components, motion, and states

- **Buttons:** primary uses solid teal with white accessible label; secondary is neutral/outlined; ghost for low-priority navigation; danger is semantic red for destructive actions.
- **Search:** white field, visible neutral border, teal focus/CTA; do not use oversized inputs or reintroduce full-width admin search template without editorial context.
- **Filters:** white chips, soft teal selected state; preserve real GET params, reset, keyboard behavior, and dropdown positioning.
- **Forms:** group fields by meaning, show validation near the fields, keep native CSRF/action semantics and old input. Do not use fake loading/success.
- **Cards:** use variety based on the function—portrait discovery cards, compact task rows, timeline meeting entries, origin-to-action legacy panels. No identical generic card used everywhere.
- **Motion:** restrained ~120–180ms hover and ~90–140ms press; transitions for background/border/transform/opacity as needed; no mass `transition-all`, no animation dependency. Preserve `prefers-reduced-motion` support.
- **Accessibility:** visible `focus-visible`, keyboard navigation, Escape/focus restoration for menus, readable labels, meaningful feedback, no color-only status.

### Responsive quality gate

- Inspect **320, 390, 768, 1024, 1280, 1440, 1920 px** where possible and zoom 125%/200% in a real browser where available.
- Reflow according to **available container width**, not viewport alone. Use Grid/Flex, `minmax`, `clamp`, `min-width: 0`, and container queries where useful.
- Public Radar may show 1 / 2 / 3 poster columns depending on usable width; **do not force 3 narrow columns at 1024 px** if cards become difficult to read.
- Internal pages adapt to sidebar width. Tables with unavoidable wide fields may scroll within a labelled container, not overflow the entire document.
- Do not use fixed content height, transform-based page scaling, or global overflow hiding as a workaround.
- Browser build/test success is not visual validation: evaluate actual screenshots if available; otherwise report unverified visual QA explicitly.

---

## 7. Page-specific composition guidance (beyond color)

### Event Radar — the signature public discovery view

**Asymmetric editorial hero**: left headline/search; right **real public featured event** (if available), using a light Ice/Aqua highlight. Then one coherent discovery toolbar and approved poster grid. Preserve search/filter GET behavior, publication/privacy rules, history categorization, and the fixture display policy below. If no upcoming featured event exists, collapse elegantly—**do not fabricate one**.

### Dashboard — act, don't just count

Prioritize *what needs my attention* over four generic KPI boxes. Show contextual event, next meeting, tasks and progress based on role. Blue accents highlight navigation/priority; CTA stays teal. University admin sees administrative priorities, not mock committee work.

### Organization & University Admin — clear stewardship

Readable membership directory, role grouping, series/edition relationships, contextual create/edit operations. Mostly neutral surfaces with teal interaction. Do not use giant marketing gradients or duplicate members in ornamental cards.

### Event Workspace — operational command center

Event/edition/organization/status masthead; nav for Meetings, Tasks, Collision, Committee, Legacy; task/meeting preview near the top. Ice context band, teal controls, calm content surfaces. Distinguish event privacy from public discovery.

### Collision — explain potential, not certainty

Show the subject event and candidate comparison with time, venue, and audience reasoning. `time_overlap` alone is informational; shared venue and/or audience can generate stronger warnings. Exclude private/cancelled/draft as specified by existing backend logic. Do not exaggerate risk with decorative red alarms.

### Meeting Hub — time and decisions

Agenda timeline with time alignment; detail page prominently shows decisions and actions. Soft cyan timeline rails and headings, white content. Keep linked tasks contextual if data provides them; do not invent links.

### Task Tracking — controlled density

Compact rows with strong task title, role/PIC, status, due date and source lesson. Use consistent progress representation. Blue-soft selected/priority context; semantic status colors stay distinct. Never create visual state that implies an update succeeded before Laravel confirms.

### Legacy — the product's differentiator

Visually relate **older edition → approved lesson → recommendation → new-edition task → progress**. Use a subtle Legacy Blue source rail, Teal for the explicit *Buat Tindak Lanjut* action, white for reading-heavy insight panels. Do not leak unapproved evaluations or imply tasks were auto-generated.

---

## 8. Engineering and final acceptance

1. Review the relevant routes, views, and current CSS and JavaScript before editing.
2. Follow central `@theme` tokens and per-page palette map; do not hardcode a random new primary color inside each module.
3. Keep page-specific styles scoped to the affected module and reuse shared controls where practical.
4. Preserve **all** routes, CRUD forms, filters, CSRF, authentication, policies, MySQL schema, task/lesson relationships, and existing events.
5. Do not add a new frontend framework, CSS library, animation library, or dependency for styling.
6. Do not run destructive database operations or change seeded data just to improve the screenshots.
7. Run relevant Blade build, scoped regressions, and browser checks when available. If unavailable, provide concrete URLs and say visual tests were not performed.
8. Document meaningful before/after changes and remaining limitations. Avoid arbitrary test-count goals.
9. **Git remains local. Do NOT commit, push, pull, reset, change remotes, or deploy until explicitly instructed by the project owner.**
10. Never claim production-readiness or competition compliance merely because visuals and tests pass.

### Definition of done per page

- [ ] Uses approved shared tokens, Geist/Inter, and one appropriate local accent.
- [ ] Has a recognizable information hierarchy and page-specific composition, not generic cards.
- [ ] Has a working primary action and accurate status/feedback.
- [ ] Adapts to narrow containers and touch without truncation/overflow.
- [ ] Preserves page functionality, permissions, existing filters/forms and data privacy.
- [ ] Uses semantic warning/error/success colors correctly and accessibly.
- [ ] Was inspected visually if the environment allows; otherwise manual review is identified as outstanding.

---

**North star:** *Different pages, one Evaloop.* A fresh event discovery product outside, calm operational tools inside, and a memorable legacy-to-action narrative tying the whole experience together.

### Fixture presentation

Display surfaces hide `[SIMULASI]` prefixes and Demo badges. Storage, seeders, and documentation retain fixture identifiers, and form inputs preserve the stored values. Treat fixtures as simulated data in demonstrations and verification.


## 9. Web navigation and workspace overview

- **App navigation.** The sidebar groups Dashboard, Event Radar, Kegiatan Saya, and Tugas Saya under **Navigasi utama**, with Organisasi Saya under **Pengelolaan**. The mobile web trigger is labelled **Menu**. Labels and active states identify the current destination.
- **Workspace tabs.** The event navigation presents Ringkasan, Jadwal & Bentrokan, Meeting Hub, Tugas & Progres, Legacy Insights, and Panitia. Jadwal & Bentrokan follows the manage policy, and tab labels remain visible on narrow screens when icons are hidden.
- **Workspace overview.** The masthead presents title, organizer, edition, status, and schedule. Management views surface schedule conflicts and lessons without follow-up. Members see their own open tasks on active events and inherited lessons within their workspace permissions.
- **Legacy Insights.** The dashboard introduces the relationship between evaluation, inherited learning, and follow-up. The source view presents problem and recommendation ahead of attachments and follow-up. Supported images appear inline through the protected preview route. Where the optional draft is available, users can review or edit its text before saving through the task form.
- **Page transitions.** A thin progress indicator appears after 150 ms for same-origin navigation and form submissions in the current tab. It resets on page return and respects reduced-motion preferences. Native browser navigation, form validation, and server-confirmed feedback remain authoritative.

## 10. Flutter Android

Android uses Material 3 with the shared Fresh Editorial palette from `mobile/lib/app/evaloop_theme.dart`. The background uses Canvas (`#F7FAFB`), the navigation drawer uses Ice (`#F0F8FA`), and primary controls use Ocean Teal (`#0F766E`). Inter is the interface font, Geist is used for headings, and Manrope is used for the Evaloop wordmark.

- **Navigation.** Event Radar opens for guests. Signed-in navigation uses a native drawer for dashboard, Tugas Saya, Event Saya, Event Radar, and profile. Use the native back stack for event, task, meeting, and lesson details.
- **Discovery and work.** Radar shows filters, public event posters, and details. Workspace surfaces schedule, committee, and task progress. Keep Meeting Hub tasks read-only and put status updates in Tugas Saya with an explicit confirmation.
- **Legacy and private media.** Show source edition, problem, recommendation, and linked follow-up. Private images use the authenticated preview route. Show the **Buat Tindak Lanjut** action only when the backend grants permission.
- **States and verification.** Keep loading, empty, error, and confirmation states distinct. Check drawer, forms, filtering, and detail screens at narrow phone widths, with keyboard open and text scaling enabled. Record emulator and device results separately from web browser checks.
