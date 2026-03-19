import { motion, AnimatePresence } from "framer-motion";
import { useState } from "react";

const faqs = [
  { q: "How do I care for my cactus friend?", a: "Water every 2-3 weeks, give them bright indirect light, and talk to them! They love company. Your adoption box includes a full care guide. 🌿", emoji: "🌱" },
  { q: "How long does delivery take?", a: "Nino Express delivers within 2-5 business days across Morocco. Your friend is cushioned safely for the journey! 🚚", emoji: "📦" },
  { q: "Can I adopt more than one friend?", a: "Of course! The more the merrier. They love having companions. Build your own little garden family! 💚", emoji: "🌵" },
  { q: "What if my cactus arrives damaged?", a: "We have a 7-day happy guarantee. If anything's wrong, reach out on WhatsApp and we'll make it right immediately. 💕", emoji: "🤗" },
  { q: "Do you ship outside Morocco?", a: "Currently we deliver across Morocco only, but we're growing! Join our waitlist for international adoption. 🌍", emoji: "✈️" },
];

const FAQ = () => {
  const [open, setOpen] = useState<number | null>(null);

  return (
    <section className="py-16 md:py-24 px-4">
      <div className="container mx-auto max-w-2xl">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-12"
        >
          <span className="inline-block bg-sunny px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
            ❓ Questions
          </span>
          <h2 className="font-display text-4xl md:text-5xl font-bold text-foreground">
            Curious? We've Got Answers!
          </h2>
        </motion.div>

        <div className="space-y-3">
          {faqs.map((faq, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 15 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.08 }}
              className="bg-card rounded-2xl sticker-shadow overflow-hidden"
            >
              <button
                onClick={() => setOpen(open === i ? null : i)}
                className="w-full flex items-center gap-3 p-5 text-left"
              >
                <span className="text-2xl">{faq.emoji}</span>
                <span className="font-body font-bold text-foreground flex-1">{faq.q}</span>
                <motion.span
                  animate={{ rotate: open === i ? 180 : 0 }}
                  className="text-muted-foreground text-xl"
                >
                  ▾
                </motion.span>
              </button>
              <AnimatePresence>
                {open === i && (
                  <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: "auto", opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.3 }}
                  >
                    <p className="px-5 pb-5 font-body text-muted-foreground leading-relaxed">
                      {faq.a}
                    </p>
                  </motion.div>
                )}
              </AnimatePresence>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default FAQ;
