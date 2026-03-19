# 🎨 NinoWorld Graphic Charter & Mobile Spec

This document contains all technical details necessary to recreate the NinoWorld experience on mobile (iOS/Android).

---

## 🌈 Color Palette (HSL)

| Role | HSL Value | Description |
|------|-----------|-------------|
| **Background** | `36 45% 98%` | Primary background (Cream) |
| **Foreground** | `25 20% 25%` | Primary text/ink |
| **Primary** | `142 30% 85%` | Sage green highlight |
| **Secondary** | `350 80% 92%` | Blush pink accent |
| **Accent** | `45 90% 88%` | Sunny yellow highlight |
| **Sky** | `195 60% 92%` | Soft blue background |
| **Card** | `36 40% 96%` | Inset card background |
| **Muted** | `36 30% 94%` | Subtle borders/dividers |

---

## 🔡 Typography

### Fonts
- **Display (Headings)**: `Gaegu` (Cursive/Handwritten style)
- **Body (Text)**: `Nunito` (Rounded Sans-Serif)

### Hierarchy
- **H1**: `font-display`, `text-5xl` (Mobile) / `text-7xl` (Desktop), `font-bold`
- **H2**: `font-display`, `text-4xl` (Mobile) / `text-6xl` (Desktop), `font-bold`
- **Utility Banners**: `font-body`, `text-xs`, `uppercase`, `tracking-widest`, `font-bold`

---

## 📐 Spacing & Layout
- **Container Padding**: `1.5rem` (24px)
- **Section Rounding**: 
  - Mobile: `2rem` (32px)
  - Full/Desktop: `4rem` (64px)
- **Grid Gap**: `1.5rem` (24px) standard

---

## ✨ Visual Effects

### Sticker Shadows
To achieve the "Sticker" look, use a layered border + shadow:
- **Base Shadow**: `box-shadow: 0 0 0 4px #FFFFFF, 0 8px 20px rgba(0,0,0,0.06);` (White stroke + soft blur)
- **Large Shadow**: `box-shadow: 0 0 0 6px #FFFFFF, 0 12px 30px rgba(0,0,0,0.08);`

### Border Radii
- `sm`: 20px
- `md`: 22px
- `lg`: 24px (`1.5rem`)
- `xl`: 40px (`2.5rem`)
- `2xl`: 64px (`4rem`)

---

## 🏃 Animation Specs

### Interaction (Springs)
Used for button taps and hover scales:
- **Type**: `spring`
- **Stiffness**: `260`
- **Damping**: `20`
- **Standard Scale**: `1.05` (Hover) / `0.95` (Tap)

### Ambient Loops
- **Floating Mascot**: 
  - Keyframes: `y: [0, -12, 0]`
  - Duration: `4s`
  - Easing: `easeInOut`
- **Sparkle**:
  - Keyframes: `opacity: [1, 0.5, 1], scale: [1, 0.8, 1]`
  - Duration: `2s`

---

## 📦 Asset Registry

### Characters
- `nino-hero.png`: The lead mascot (Nino).
- `coco-character.png`: Playful vibe.
- `lili-character.png`: Elegant vibe.
- `mimi-character.png`: Sweet vibe.

### Elements
- `adoption-box.png`: Unboxing visual.
- `nino-truck.png`: Delivery visual.
- **Floating Doodles**: `✨`, `🌿`, `⭐`, `💚`, `🍃`, `🌸` (Render as vector/SVG or high-res emojis).

---

## 🛠️ Mobile Implementation Tips
1.  **React Native**: Mapping `rem` to `24` (or responsive scale factors).
2.  **SwiftUI**: Use `RoundedRectangle(cornerRadius: 32)` with multiple `.shadow()` modifiers for the sticker effect.
3.  **Flutter**: Use `BoxShadow` with `spreadRadius` for the white stroke.
