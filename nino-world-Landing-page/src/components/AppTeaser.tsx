import { motion, AnimatePresence } from "framer-motion";
import { useState, useEffect } from "react";

const messages = [
  { from: "Nino", text: "Hi Yassine! I'm feeling happy today 🌞", time: "10:32" },
  { from: "You", text: "Good morning Nino! Did you get enough sun?", time: "10:33" },
  { from: "Nino", text: "Yes! The window spot is perfect 💚", time: "10:33" },
  { from: "Nino", text: "I'm a little thirsty though... 💧", time: "10:35" },
  { from: "You", text: "On it! 🚿", time: "10:35" },
  { from: "Nino", text: "Ahhh thank you! You're the best human! 🥰", time: "10:36" },
];

const AppTeaser = () => {
  const [visibleCount, setVisibleCount] = useState(2);

  useEffect(() => {
    if (visibleCount < messages.length) {
      const timer = setTimeout(() => setVisibleCount((c) => c + 1), 2500);
      return () => clearTimeout(timer);
    }
  }, [visibleCount]);

  return (
    <section className="py-16 md:py-24 px-4 bg-sage/30">
      <div className="container mx-auto">
        <div className="flex flex-col md:flex-row items-center gap-10 md:gap-16">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="flex-1"
          >
            <span className="inline-block bg-sage px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
              📱 Coming Soon
            </span>
            <h2 className="font-display text-4xl md:text-5xl font-bold text-foreground mb-4">
              Talk to Your Friend
            </h2>
            <p className="font-body text-lg text-muted-foreground leading-relaxed mb-6">
              The NinoWorld app lets your plant talk to you. Get care reminders, 
              mood updates, and sweet little messages from your cactus friend. 🌵💬
            </p>
            <div className="flex gap-3">
              <motion.button
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
                className="bg-primary text-primary-foreground font-body font-bold px-6 py-3 rounded-full sticker-shadow"
              >
                Join Waitlist ✨
              </motion.button>
            </div>
          </motion.div>

          {/* Phone mockup */}
          <motion.div
            initial={{ opacity: 0, x: 30 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            className="flex-1 flex justify-center"
          >
            <div className="bg-background rounded-[2.5rem] p-4 sticker-shadow-lg w-72 max-h-[28rem] overflow-hidden">
              <div className="bg-sage/40 rounded-2xl p-3 mb-3 text-center">
                <span className="font-display text-lg font-bold text-foreground">🌵 Nino Chat</span>
              </div>
              <div className="space-y-3 px-1">
                <AnimatePresence>
                  {messages.slice(0, visibleCount).map((msg, i) => (
                    <motion.div
                      key={i}
                      initial={{ opacity: 0, y: 10, scale: 0.9 }}
                      animate={{ opacity: 1, y: 0, scale: 1 }}
                      transition={{ type: "spring", stiffness: 260, damping: 20 }}
                      className={`flex ${msg.from === "You" ? "justify-end" : "justify-start"}`}
                    >
                      <div
                        className={`rounded-2xl px-4 py-2.5 max-w-[85%] ${
                          msg.from === "You"
                            ? "bg-primary text-primary-foreground"
                            : "bg-card text-foreground"
                        }`}
                      >
                        <p className="font-body text-sm">{msg.text}</p>
                        <p className="font-body text-[10px] opacity-50 mt-1">{msg.time}</p>
                      </div>
                    </motion.div>
                  ))}
                </AnimatePresence>
              </div>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
};

export default AppTeaser;
