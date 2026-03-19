import { motion } from "framer-motion";
import { useState } from "react";

const vibes = [
  { name: "Cheerful", emoji: "☀️", color: "bg-sage", desc: "Bright, warm, and always smiling", character: "Nino" },
  { name: "Playful", emoji: "🎉", color: "bg-sunny", desc: "Energetic, fun, and full of surprises", character: "Coco" },
  { name: "Elegant", emoji: "🌙", color: "bg-sky", desc: "Gentle, dreamy, and gracefully calm", character: "Lili" },
  { name: "Sweet", emoji: "💗", color: "bg-blush", desc: "Tiny, tender, and deeply lovable", character: "Mimi" },
];

const ChooseVibe = () => {
  const [active, setActive] = useState<number | null>(null);
  const activeBg = active !== null ? vibes[active].color : "bg-card";

  return (
    <section className={`py-16 md:py-24 px-4 transition-colors duration-500 ${activeBg}`}>
      <div className="container mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-12"
        >
          <span className="inline-block bg-background px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
            ✨ Find Your Match
          </span>
          <h2 className="font-display text-4xl md:text-6xl font-bold text-foreground">
            Choose Your Vibe
          </h2>
          <p className="font-body text-lg text-foreground/70 mt-3 max-w-md mx-auto">
            What feeling are you looking for? Let your heart decide.
          </p>
        </motion.div>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
          {vibes.map((v, i) => (
            <motion.button
              key={v.name}
              onMouseEnter={() => setActive(i)}
              onMouseLeave={() => setActive(null)}
              whileHover={{ scale: 1.08 }}
              whileTap={{ scale: 0.95 }}
              className={`aspect-square rounded-full ${v.color} sticker-shadow-lg flex flex-col items-center justify-center gap-2 transition-all ${active === i ? "ring-4 ring-background" : ""}`}
            >
              <span className="text-4xl">{v.emoji}</span>
              <span className="font-display text-xl font-bold text-foreground">{v.name}</span>
              <span className="font-body text-xs text-foreground/60 px-3 text-center">{v.desc}</span>
            </motion.button>
          ))}
        </div>

        {active !== null && (
          <motion.p
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className="text-center font-display text-2xl font-bold text-foreground mt-8"
          >
            {vibes[active].character} is your perfect match! 🌟
          </motion.p>
        )}
      </div>
    </section>
  );
};

export default ChooseVibe;
