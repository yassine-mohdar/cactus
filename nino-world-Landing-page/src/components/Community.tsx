import { motion } from "framer-motion";

const moments = [
  { emoji: "📸", text: "Yassine & Nino on a sunny morning", bg: "bg-sunny" },
  { emoji: "🌿", text: "Amina's desk garden with Coco & Lili", bg: "bg-sage" },
  { emoji: "💕", text: "Mimi found her forever home!", bg: "bg-blush" },
  { emoji: "🌞", text: "Sunday vibes with the whole family", bg: "bg-sky" },
  { emoji: "📖", text: "Reading with Nino by the window", bg: "bg-sunny" },
  { emoji: "🎉", text: "Coco's 1-year adoption anniversary!", bg: "bg-sage" },
];

const Community = () => (
  <section className="py-16 md:py-24 px-4">
    <div className="container mx-auto">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="text-center mb-12"
      >
        <span className="inline-block bg-sky px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
          🌍 NinoWorld Family
        </span>
        <h2 className="font-display text-4xl md:text-6xl font-bold text-foreground">
          Happy Moments
        </h2>
        <p className="font-body text-lg text-muted-foreground mt-3 max-w-md mx-auto">
          Join thousands of plant parents who found their green soulmate. 💚
        </p>
      </motion.div>

      <div className="grid grid-cols-2 md:grid-cols-3 gap-4 max-w-3xl mx-auto">
        {moments.map((m, i) => (
          <motion.div
            key={i}
            initial={{ opacity: 0, scale: 0.9 }}
            whileInView={{ opacity: 1, scale: 1 }}
            viewport={{ once: true }}
            transition={{ delay: i * 0.1 }}
            whileHover={{ y: -4, rotate: Math.random() > 0.5 ? 2 : -2 }}
            className={`${m.bg} rounded-[2rem] p-6 sticker-shadow text-center`}
          >
            <span className="text-4xl block mb-3">{m.emoji}</span>
            <p className="font-body text-sm font-semibold text-foreground">{m.text}</p>
          </motion.div>
        ))}
      </div>
    </div>
  </section>
);

export default Community;
