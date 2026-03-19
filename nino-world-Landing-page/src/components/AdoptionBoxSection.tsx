import { motion } from "framer-motion";
import adoptionBox from "@/assets/adoption-box.png";

const includes = [
  { emoji: "🌵", label: "Your cactus friend" },
  { emoji: "📜", label: "Adoption certificate" },
  { emoji: "📖", label: "Care guide booklet" },
  { emoji: "🏷️", label: "Name tag sticker" },
  { emoji: "📱", label: "QR to NinoWorld app" },
  { emoji: "💌", label: "Welcome letter" },
];

const AdoptionBoxSection = () => (
  <section className="py-16 md:py-24 px-4">
    <div className="container mx-auto">
      <div className="flex flex-col md:flex-row items-center gap-10 md:gap-16">
        <motion.div
          initial={{ opacity: 0, x: -40 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          className="flex-1 flex justify-center"
        >
          <motion.img
            src={adoptionBox}
            alt="NinoWorld adoption box with cactus, certificate and stickers"
            className="w-72 md:w-96 drop-shadow-2xl"
            animate={{ y: [0, -8, 0] }}
            transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
          />
        </motion.div>

        <motion.div
          initial={{ opacity: 0, x: 40 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          className="flex-1"
        >
          <span className="inline-block bg-blush px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
            📦 Unboxing Magic
          </span>
          <h2 className="font-display text-4xl md:text-5xl font-bold text-foreground mb-4">
            The Adoption Box
          </h2>
          <p className="font-body text-lg text-muted-foreground leading-relaxed mb-8">
            Every adoption comes in a lovingly crafted box. It's not just packaging — 
            it's the beginning of a beautiful friendship. 💕
          </p>

          <div className="grid grid-cols-2 gap-3">
            {includes.map((item, i) => (
              <motion.div
                key={item.label}
                initial={{ opacity: 0, y: 15 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: i * 0.08 }}
                className="bg-card rounded-2xl px-4 py-3 sticker-shadow flex items-center gap-3"
              >
                <span className="text-xl">{item.emoji}</span>
                <span className="font-body text-sm font-semibold text-foreground">{item.label}</span>
              </motion.div>
            ))}
          </div>
        </motion.div>
      </div>
    </div>
  </section>
);

export default AdoptionBoxSection;
