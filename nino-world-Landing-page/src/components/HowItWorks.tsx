import { motion } from "framer-motion";

const steps = [
  { num: "1", emoji: "💚", title: "Choose your friend", desc: "Browse our little family and pick the one who speaks to your heart." },
  { num: "2", emoji: "📦", title: "Receive the adoption box", desc: "A lovingly packed box arrives with your new friend, care guide, and adoption certificate." },
  { num: "3", emoji: "🏠", title: "Welcome them home", desc: "Find a sunny spot, give them a name tag, and let the friendship begin." },
  { num: "4", emoji: "🌱", title: "Keep them happy", desc: "Water, sunlight, and a little chat. They're easy to love!" },
];

const HowItWorks = () => (
  <section className="py-16 md:py-24 px-4 bg-card">
    <div className="container mx-auto">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="text-center mb-14"
      >
        <span className="inline-block bg-sunny px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
          🌿 The Adoption Journey
        </span>
        <h2 className="font-display text-4xl md:text-6xl font-bold text-foreground">
          How Adoption Works
        </h2>
      </motion.div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-8 max-w-4xl mx-auto relative">
        {/* Connecting doodle line (desktop) */}
        <div className="hidden md:block absolute top-16 left-[12%] right-[12%] h-1 bg-primary/30 rounded-full" />

        {steps.map((s, i) => (
          <motion.div
            key={s.num}
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: i * 0.15, duration: 0.5 }}
            className="text-center relative z-10"
          >
            <motion.div
              whileHover={{ scale: 1.1, rotate: 5 }}
              className="w-20 h-20 rounded-full bg-background sticker-shadow-lg flex items-center justify-center mx-auto mb-4"
            >
              <span className="text-3xl">{s.emoji}</span>
            </motion.div>
            <span className="inline-block bg-sage px-3 py-1 rounded-full font-body font-bold text-xs text-foreground mb-2">
              Step {s.num}
            </span>
            <h3 className="font-display text-2xl font-bold text-foreground mb-2">{s.title}</h3>
            <p className="font-body text-sm text-muted-foreground leading-relaxed">{s.desc}</p>
          </motion.div>
        ))}
      </div>
    </div>
  </section>
);

export default HowItWorks;
