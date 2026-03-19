import { motion } from "framer-motion";
import ninoImg from "@/assets/nino-hero.png";
import cocoImg from "@/assets/coco-character.png";
import liliImg from "@/assets/lili-character.png";
import mimiImg from "@/assets/mimi-character.png";

const friends = [
  {
    name: "Nino",
    personality: "Cheerful & Welcoming",
    desc: "The friendliest cactus you'll ever meet. Always smiling, always warm.",
    img: ninoImg,
    bg: "bg-sage",
    vibe: "☀️ Cheerful",
    price: "89 MAD",
  },
  {
    name: "Coco",
    personality: "Playful & Energetic",
    desc: "Can't stop dancing! Coco brings sunshine and giggles everywhere.",
    img: cocoImg,
    bg: "bg-sunny",
    vibe: "🎉 Playful",
    price: "79 MAD",
  },
  {
    name: "Lili",
    personality: "Elegant & Gentle",
    desc: "A quiet dreamer with a heart of gold. Loves moonlight and soft music.",
    img: liliImg,
    bg: "bg-sky",
    vibe: "🌙 Elegant",
    price: "99 MAD",
  },
  {
    name: "Mimi",
    personality: "Tiny & Lovable",
    desc: "So small, so shy, so full of love. Mimi just wants a warm corner.",
    img: mimiImg,
    bg: "bg-blush",
    vibe: "💗 Sweet",
    price: "69 MAD",
  },
];

const cardVariants = {
  hidden: { opacity: 0, y: 40 },
  visible: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { delay: i * 0.15, duration: 0.6, ease: "easeOut" as const },
  }),
};

const MeetFriends = () => (
  <section className="py-16 md:py-24 px-4">
    <div className="container mx-auto">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="text-center mb-12"
      >
        <span className="inline-block bg-blush px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
          🌵 Adoption Passports
        </span>
        <h2 className="font-display text-4xl md:text-6xl font-bold text-foreground">
          Meet the Friends
        </h2>
        <p className="font-body text-lg text-muted-foreground mt-3 max-w-md mx-auto">
          Each one has a unique personality. Who will you bring home?
        </p>
      </motion.div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
        {friends.map((f, i) => (
          <motion.div
            key={f.name}
            custom={i}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true }}
            variants={cardVariants}
            whileHover={{ y: -8, rotate: 1 }}
            className={`${f.bg} rounded-[2.5rem] p-6 sticker-shadow-lg relative overflow-visible`}
          >
            {/* Character sticker */}
            <div className="flex justify-center -mt-16 mb-2">
              <motion.img
                src={f.img}
                alt={f.name}
                className="w-32 h-32 object-contain drop-shadow-lg"
                animate={{ y: [0, -6, 0] }}
                transition={{ duration: 3 + i * 0.3, repeat: Infinity, ease: "easeInOut" }}
              />
            </div>
            {/* Vibe badge */}
            <span className="inline-block bg-background px-3 py-1 rounded-full text-xs font-body font-bold text-foreground mb-2">
              {f.vibe}
            </span>
            <h3 className="font-display text-3xl font-bold text-foreground">{f.name}</h3>
            <p className="font-body text-sm font-bold text-foreground/70 mb-2">{f.personality}</p>
            <p className="font-body text-sm text-foreground/60 mb-4 leading-relaxed">{f.desc}</p>
            <div className="flex items-center justify-between">
              <span className="font-display text-2xl font-bold text-foreground">{f.price}</span>
              <motion.button
                whileHover={{ scale: 1.08 }}
                whileTap={{ scale: 0.95 }}
                className="bg-background text-foreground font-body font-bold px-5 py-2 rounded-full text-sm sticker-shadow"
              >
                Adopt 💚
              </motion.button>
            </div>
          </motion.div>
        ))}
      </div>
    </div>
  </section>
);

export default MeetFriends;
